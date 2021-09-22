/**
 * @module controls/installation/MailSMTP
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/installation/MailSMTP', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',

    'css!controls/installation/MailSMTP.css'

], function (QUI, QUIControl) {
    "use strict";

    let noSMTP  = null;
    let useSMTP = null;

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/installation/MailSMTP',

        Binds: [
            '$onImport',
            'useSmtp',
            'useNoSmtp',
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const Buttons = this.getElm().getElements('.quiqqer-setup-mailSmtp-btns');

            this.getElm().getElement('div').addClass('quiqqer-setup-mailSmtp');

            Buttons.getElement('[name="use-smtp"]').addEvent('click', this.useSmtp);
            Buttons.getElement('[name="no-smtp"]').addEvent('click', this.useNoSmtp);
        },

        next: function () {
            if (noSMTP) {
                return true;
            }

            // check smtp


            return false;
        },

        useSmtp: function () {
            const Buttons = this.getElm().getElement('.quiqqer-setup-mailSmtp-btns');
            const Form    = this.getElm().getElement('form');

            Buttons.setStyle('position', 'relative');
            Form.elements["use-smtp"].value = 1;

            moofx(Buttons).animate({
                left   : -10,
                opacity: 0
            }, {
                duration: 300,
                callback: function () {
                    Buttons.setStyle('display', 'none');

                    Form.setStyle('opacity', 0);
                    Form.setStyle('left', -10);
                    Form.setStyle('display', null);

                    moofx(Form).animate({
                        left   : 0,
                        opacity: 1
                    }, {
                        duration: 300,
                        callback: function () {

                        }
                    });
                }
            });
        },

        useNoSmtp: function () {
            noSMTP = true;
            this.getAttribute('Wizard').next();
        },

        checkSMTPServer: function () {
            return new Promise(function (resolve) {
                QUIAjax.get('', function () {
                    resolve();
                }, {
                    'package': 'quiqqer/quiqqer'
                });
            });
        }
    });
});
