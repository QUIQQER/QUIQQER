/**
 * @module controls/users/auth/GlobalAuthenticatorSettings
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('controls/users/auth/GlobalAuthenticatorSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax'

], function (QUI, QUIControl, QUIAjax) {
    "use strict";

    return new Class({
        Type: '',
        Extends: QUIControl,

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            console.log(this.getElm());
        }
    });
});