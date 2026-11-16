<?php
declare(strict_types=1);

namespace Blueskytechco\Themeoption\Plugin\Catalog;

use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;

/**
 * Hands the already-loaded product to the image block so the theme's
 * image_with_borders.phtml does not reload every product in a listing
 * through the repository (one full EAV load per product tile).
 */
class ImageFactoryPlugin
{
    public function afterCreate(
        ImageFactory $subject,
        Image $result,
        Product $product,
        string $imageId,
        ?array $attributes = null
    ): Image {
        $result->setData('product', $product);
        return $result;
    }
}
