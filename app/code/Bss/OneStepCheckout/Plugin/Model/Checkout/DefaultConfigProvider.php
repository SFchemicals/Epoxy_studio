<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category  BSS
 * @package   Bss_OneStepCheckout
 * @author    Extension Team
 * @copyright Copyright (c) 2023-2023 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\OneStepCheckout\Plugin\Model\Checkout;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Bss\OneStepCheckout\Helper\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;

class DefaultConfigProvider
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var mixed
     */
    protected $getProductSalableQty;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * Construct.
     *
     * @param CheckoutSession $checkoutSession
     * @param StockRegistryInterface $stockRegistry
     * @param Config $configHelper
     * @param Manager $moduleManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        StockRegistryInterface $stockRegistry,
        Config $configHelper,
        Manager $moduleManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->stockRegistry = $stockRegistry;
        $this->configHelper = $configHelper;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Add saleable qty to checkout config
     *
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param array|mixed $result
     * @return array|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfig($subject, $result)
    {
        if ($this->configHelper->isEnabled()) {
            $this->addDataQty($result);
        }

        return $result;
    }

    /**
     * Add data QTY product.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addDataQty(&$data)
    {
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $quoteItems = $this->checkoutSession->getQuote()->getItemsCollection();
            foreach ($quoteItems as $quoteItem) {
                if (in_array($quoteItem->getProductType(), ["configurable", "bundle", "grouped"])) {
                    continue;
                }
                $product = $quoteItem->getProduct();
                $stockItem = $this->stockRegistry->getStockItem(
                    $product->getId(),
                    $product->getStore()->getWebsiteId()
                );

                if ($this->moduleManager->isEnabled("Magento_Inventory")) {
                    if (!$this->getProductSalableQty) {
                        $this->getProductSalableQty =
                            \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\InventorySales\Model\GetProductSalableQty');
                    }

                    $salableQty = $this->getProductSalableQty->execute($product->getSku(), $stockItem->getStockId());
                } else {
                    if ($stockItem->getBackorders() == 0) {
                        $salableQty = $stockItem->getQty() - $stockItem->getMinQty();
                    } else {
                        $salableQty = $stockItem->getQty();
                    }
                }

                $id = $quoteItem->getParentItemId() ?: $quoteItem->getItemId();
                $data['saleableQty'][$id] = $salableQty;
                $data['backorders'][$id] = $stockItem->getBackorders();
                $data['isManageStock'][$id] = $stockItem->getManageStock();
            }
        }
        return $data;
    }
}
