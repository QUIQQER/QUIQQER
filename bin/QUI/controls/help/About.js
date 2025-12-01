/**
 * Help Window
 *
 * @module controls/help/About
 */
define('controls/help/About', [

    'qui/controls/windows/Popup',
    'Locale',
    'Ajax',
    'Mustache',

    'text!controls/help/About.de.html',
    'text!controls/help/About.en.html',
    'css!controls/help/About.css'

], function (QUIPopup, QUILocale, QUIAjax, Mustache, templateDe, templateEn) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type: 'controls/help/About',

        Binds: [
            '$onOpen',
            '$onCreate'
        ],

        options: {
            maxHeight: 440,
            maxWidth: 600,
            title: QUILocale.get('quiqqer/core', 'menu.help.about.text'),
            closeButtonText: QUILocale.get('quiqqer/core', 'close')
        },

        initialize: function (options) {
            this.parent(options);
            this.addEvents({
                onOpen: this.$onOpen,
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
            this.Loader.show();

            QUIAjax.get('ajax_system_systemInfo', (data) => {
                let template;
                this.getContent().addClass('quiqqer-about-window');

                switch (QUILocale.getCurrent()) {
                    case 'de':
                        template = templateDe;
                        break;

                    default:
                        template = templateEn;
                }

                this.getContent().set('html', Mustache.render(template, {
                    version: data.version,
                    hash: data.hash,
                    logo: URL_BIN_DIR + 'quiqqer_logo.png',
                    year: new Date().getFullYear(),
                    database: data.database,
                    php_version: data.php_version
                }));

                this.Loader.hide();
            }, {
                'package': 'quiqqer/core'
            });

        }
    });
});
