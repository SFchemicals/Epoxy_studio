/**
 * Magento 2.4.7+ only builds the auth modal when guest checkout is disabled.
 * The theme opens it from the header Sign In link / wishlist (.trigger-auth-popup),
 * so always create it.
 */
define([], function () {
    'use strict';

    return function (Component) {
        return Component.extend({
            setModalElement: function (element) {
                this.createPopup(element);
            }
        });
    };
});
