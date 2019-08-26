/**
 * The Quiqqer Dashboard
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/help/Dashboard
 *
 * @deprecated
 */
define('controls/help/Dashboard', [

    'qui/QUI',
    'qui/controls/desktop/Panel'

], function (QUI, QUIPanel) {
    "use strict";

    /**
     * @class controls/help/Dashboard
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/help/Dashboard',

        Binds: [
            '$onCreate',
            '$openSitePanel',
            'refreshLastMessages'
        ],

        options: {
            icon             : URL_BIN_DIR + '16x16/quiqqer.png',
            title            : 'QUIQQER Dashboard',
            displayNoTaskText: true
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: function () {
                    (function () {
                        this.destroy();
                    }).delay(500);
                }.bind(this)
            });
        },

        /**
         * Create the project panel body
         */
        $onCreate: function () {

        }
    });
});
