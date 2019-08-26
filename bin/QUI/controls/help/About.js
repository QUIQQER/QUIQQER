/**
 * Help Window
 *
 * @module controls/help/About
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/help/About', [

    'qui/controls/windows/Popup',
    'Locale',
    'Mustache',

    'text!controls/help/About.de.html',
    'text!controls/help/About.en.html',

    'css!controls/help/About.css'

], function (QUIPopup, QUILocale, Mustache, templateDe, templateEn) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/help/About',

        Binds: [
            '$onOpen',
            '$onCreate'
        ],

        options: {
            maxHeight      : 400,
            maxWidth       : 600,
            title          : QUILocale.get('quiqqer/system', 'menu.help.about.text'),
            closeButtonText: QUILocale.get('quiqqer/system', 'close')
        },

        initialize: function (options) {
            this.parent(options);
            this.addEvents({
                onOpen  : this.$onOpen,
                onCreate: this.$onCreate
            });
        },

        /**
         * event: on create
         */
        $onCreate: function () {
            this.$Buttons.getElement('button').removeClass('btn-red');
        },

        /**
         * event: on open
         */
        $onOpen: function () {
            var template;

            this.getContent().addClass('quiqqer-about-window');

            switch (QUILocale.getCurrent()) {
                case 'de':
                    template = templateDe;
                    break;

                default:
                    template = templateEn;
            }

            this.getContent().set('html', Mustache.render(template, {
                version: QUIQQER_VERSION,
                hash   : QUIQQER_HASH,
                logo   : URL_BIN_DIR + 'quiqqer_logo.png',
                year   : new Date().getFullYear()
            }));
        }
    });
});
