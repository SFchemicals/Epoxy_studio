define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/translate',
    'priceUtils',
    'priceBox',
    'jquery-ui-modules/widget',
    'jquery/jquery.parsequery',
    'fotoramaVideoEvents'
], function ($, _, mageTemplate, $t, priceUtils) {
    'use strict';

    return function (widget) {

        $.widget('mage.configurable', widget, {
            _initializeOptions: function () {
                var options = this.options,
                    gallery = $(options.mediaGallerySelector);
                var priceBoxOptions = null;
                try {
                    // 2.4.9: price box scoped to this product (quickview / multiple products on page)
                    var $priceBox = this._getPriceBoxElement ? this._getPriceBoxElement() : $();
                    if (!$priceBox.length) {
                        $priceBox = $(this.options.priceHolderSelector);
                    }
                    priceBoxOptions = $priceBox.priceBox('option').priceConfig;
                } catch(err) {
                    console.log(err); 
                }
    
                if (priceBoxOptions && priceBoxOptions.optionTemplate) {
                    options.optionTemplate = priceBoxOptions.optionTemplate;
                }
    
                if (priceBoxOptions && priceBoxOptions.priceFormat) {
                    options.priceFormat = priceBoxOptions.priceFormat;
                }
                options.optionTemplate = mageTemplate(options.optionTemplate);
                options.tierPriceTemplate = $(this.options.tierPriceTemplateSelector).html();
    
                var $scoped = this.element.parents(this.options.selectorProduct).find(options.superSelector);
                options.settings = options.spConfig.containerId ?
                    $(options.spConfig.containerId).find(options.superSelector) :
                    ($scoped.length ? $scoped : $(options.superSelector));
    
                options.values = options.spConfig.defaultValues || {};
                options.parentImage = $('[data-role=base-image-container] img').attr('src');
    
                this.inputSimpleProduct = this.element.find(options.selectSimpleProduct);
    
                gallery.data('gallery') ?
                    this._onGalleryLoaded(gallery) :
                    gallery.on('gallery:loaded', this._onGalleryLoaded.bind(this, gallery));
            }
        });

        return $.mage.configurable;
    }
});
