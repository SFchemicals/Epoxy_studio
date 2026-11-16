<?php

namespace Nwdthemes\Revslider\Helper;

class Options extends \Magento\Framework\App\Helper\AbstractHelper {

    protected $_optionFactory;

    /**
     * Per-request cache of option values by handle (the same handles are read several times per page).
     *
     * @var array<string, mixed>
     */
    private array $optionCache = [];

    /**
     *	Constructor
     */

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Nwdthemes\Revslider\Model\OptionFactory $optionFactory
    ) {
        $this->_optionFactory = $optionFactory;

        parent::__construct($context);
    }


    /**
     *	Get option
     *
     *	@param	string	Handle
     *	@param	string	Default value
     *	@return	string	Option value
     */

    public function getOption($handle, $default = false) {
        if (!array_key_exists($handle, $this->optionCache)) {
            $this->optionCache[$handle] = $this->loadOption($handle);
        }
        return $this->optionCache[$handle][0] ? $this->optionCache[$handle][1] : $default;
    }

    /**
     * Read an option from the database as [found, value].
     *
     * @param string $handle
     * @return array{0: bool, 1: mixed}
     */
    private function loadOption($handle) {
        $collection = $this->_optionFactory->create()
            ->getCollection()
            ->addFieldToFilter('handle', $handle)
            ->setPageSize(1);
        if ($collection->count()) {
            $option = $collection->getFirstItem();
            $value = $option->getData('option');
            if ((strpos($value, 'a:') !== false
                    || strpos($value, 's:') !== false
                    || strpos($value, 'O:') !== false
                    || strpos($value, 'i:') !== false
                    || strpos($value, 'b:') !== false)
                && (($unserializedValue = @unserialize($value)) !== false || $value == 'b:0;')) {
                $value = $unserializedValue;
            } elseif ((strpos($value, 'a:') !== false
                    || strpos($value, 's:') !== false
                    || strpos($value, 'O:') !== false
                    || strpos($value, 'i:') !== false
                    || strpos($value, 'b:') !== false)
                && ($unserializedValue = @unserialize($this->_fixSerializedString($value))) !== false) {
                // save fixed serialized string
                $this->updateOption($handle, $unserializedValue);
                $value = $unserializedValue;
            }
            return [true, $value];
        } else {
            return [false, null];
        }
    }

    /**
     * Update option
     *
     * @param string $handle
     * @param string value
     */

    public function updateOption($handle, $value = '') {
        unset($this->optionCache[$handle]);
        $collection = $this->_optionFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter('handle', $handle)
            ->setPageSize(1);
        if ($collection->count()) {
            $option = $collection->getFirstItem();
        } else {
            $option = $this->_optionFactory
                ->create()
                ->setData('handle', $handle);
        }
        $option
            ->setData('option', is_string($value) ? $value : serialize($value))
            ->save();
    }

    /**
     * Delete option
     *
     * @param string $handle
     */

    public function deleteOption($handle) {
        unset($this->optionCache[$handle]);
        $collection = $this->_optionFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter('handle', $handle)
            ->setPageSize(1);
        if ($collection->count()) {
            $collection
                ->getFirstItem()
                ->delete();
        }
    }

    /**
     * Fix strings length in serialized string
     *
     * @param string $serializedString
     * @return string
     */
    protected function _fixSerializedString($serializedString) {
        $fixedString = preg_replace_callback(
            '/s:([0-9]+):\"(.*?)\";/',
            function ($matches) {
                return "s:".strlen($matches[2]).':"'.$matches[2].'";';
            },
            $serializedString
        );
        return $fixedString;
    }

}