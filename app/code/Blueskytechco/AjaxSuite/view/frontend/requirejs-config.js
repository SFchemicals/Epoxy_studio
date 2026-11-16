var config = {
    map: {
        '*': {
            'blueskytechco/ajaxsuite': 'Blueskytechco_AjaxSuite/js/ajaxsuite',
            'Blueskytechco_AjaxSuite/js/model/ajaxsuite-popup':'Blueskytechco_AjaxSuite/js/model/ajaxsuite-popup',
            'Magento_Customer/template/authentication-popup':'Blueskytechco_AjaxSuite/template/authentication-popup',
            'Magento_Customer/js/model/authentication-popup':'Blueskytechco_AjaxSuite/js/model/authentication-popup'
        }
    },
    config: {
        mixins: {
            'Magento_Customer/js/view/authentication-popup': {
                'Blueskytechco_AjaxSuite/js/view/authentication-popup-mixin': true
            }
        }
    }
};
