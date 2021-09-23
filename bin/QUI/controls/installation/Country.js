/**
 * @module controls/installation/Country
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/installation/Country', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/countries/bin/controls/Select',

    'css!controls/installation/Country.css'

], function (QUI, QUIControl, CountrySelect) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/installation/Country',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            this.getElm().getElement('div').addClass('quiqqer-setup-country');

            new CountrySelect({
                events: {
                    onLoad: function (Instance) {
                        Instance.$Select.name = 'quiqqer-country';
                    }
                }
            }).inject(
                this.getElm().getElement('form')
            );
        }
    });
});
