/**
 * quiqqer mail config
 *
 * @module controls/system/settings/Mail
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 */
define('controls/system/settings/Mail', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUIButton, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/system/settings/Config',

        Binds: [
            '$onImport',
            'testMailSettings'
        ],

        initialize: function (Panel) {
            this.$Panel = Panel;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Panel   = this.$Panel,
                Content = Panel.getContent();

            var Table = Content.getElement('table:last-child');

            new QUIButton({
                text     : QUILocale.get('quiqqer/quiqqer', 'test.mail.button'),
                textimage: 'fa fa-envelope-o icon-envelope-alt',
                events   : {
                    onClick: this.testMailSettings
                },
                styles   : {
                    'float'        : 'right',
                    'margin-bottom': 20
                }
            }).inject(Table, 'after');
        },

        /**
         * Test current mail settings
         */
        testMailSettings: function (Button) {
            var Panel   = this.$Panel,
                Content = Panel.getContent(),
                Form    = Content.getElement('form');

            var params = {
                SMTPServer: Form.elements['mail.SMTPServer'].value,
                SMTPPort  : Form.elements['mail.SMTPPort'].value,
                SMTPUser  : Form.elements['mail.SMTPUser'].value,
                SMTPPass  : Form.elements['mail.SMTPPass'].value,
                SMTPSecure: Form.elements['mail.SMTPSecure'].value
            };

            Button.setAttribute(
                'textimage',
                'icon-spinner icon-spin fa fa-spinner fa-spin'
            );

            QUIAjax.get('ajax_system_mailTest', function () {
                Button.setAttribute(
                    'textimage',
                    'fa fa-envelope-o icon-envelope-alt'
                );
            }, {
                params : JSON.encode(params),
                onError: function (Error) {

                    QUI.getMessageHandler().then(function (MH) {
                        MH.addError(
                            Error.getMessage()
                        );
                    });

                    Button.setAttribute(
                        'textimage',
                        'fa fa-envelope-o icon-envelope-alt'
                    );
                }
            });
        }
    });
});
