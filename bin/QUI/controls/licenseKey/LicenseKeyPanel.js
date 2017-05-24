/**
 * LicenseKeyPanel JavaScript Control
 *
 * Panel for showing QUIQQER license key information
 *
 * @module controls/licenseKey/LicenseKeyPanel
 * @author www.pcsg.de (Patrick Müller)
 *
 * @require qui/controls/desktop/Panel
 * @require controls/licenseKey/LicenseKey
 * @require css!controls/licenseKey/LicenseKeyPanel.css
 */
define('controls/licenseKey/LicenseKeyPanel', [

    'qui/controls/desktop/Panel',
    'controls/licenseKey/LicenseKey',

    'css!controls/licenseKey/LicenseKeyPanel.css'

], function (QUIPanel, LicenseKey) {
    "use strict";

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/licenseKey/LicenseKeyPanel',

        Binds: [
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Event: onImport
         */
        $onInject: function () {
            this.$Elm.addClass('quiqqer-licensekey-panel');
            new LicenseKey().inject(this.getContent());
        }
    });
});
