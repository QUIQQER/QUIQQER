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
            var self    = this,
                Panel   = this.$Panel,
                Content = Panel.getContent();

            var Table = Content.getElement('table:last-child');

            // ssl select
            var Select = Content.getElement('[name="mail.SMTPSecure"]');

            Select.addEvent('change', function () {
                if (this.value == 'ssl') {
                    self.showSSLOptions();
                    return;
                }

                self.hideSSLOptions();
            });

            if (Select.value == 'ssl') {
                self.showSSLOptions();
            }

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
         * Show SSL options
         */
        showSSLOptions: function () {
            var Panel       = this.$Panel,
                Content     = Panel.getContent(),
                Select      = Content.getElement('[name="mail.SMTPSecure"]'),
                ParentTable = Select.getParent('tbody'),
                ParentTr    = Select.getParent('tr');

            var newRow = new Element('tr', {
                'class': 'ssl-option-row',
                styles : {
                    'float' : 'left',
                    height  : 0,
                    overflow: 'hidden',
                    position: 'relative'
                }
            });

            var VerifyPeer      = newRow.clone();
            var VerifyPeerName  = newRow.clone();
            var AllowSelfSigned = newRow.clone();

            var evenCssClass = ParentTr.hasClass('even');

            VerifyPeer.set({
                html: '<td>' +
                      '  <p>' +
                      '      <label class="checkbox-label hasCheckbox">' +
                      '           <input type="checkbox" name="mail.SMTPSecureSSL_verify_peer" />' +
                      QUILocale.get('quiqqer/quiqqer', 'mail.settings.verify_peer') +
                      '      </label>' +
                      '  </p>' +
                      '</td>'
            });

            VerifyPeerName.set({
                html: '<td>' +
                      '  <p>' +
                      '      <label class="checkbox-label hasCheckbox">' +
                      '           <input type="checkbox" name="mail.SMTPSecureSSL_verify_peer_name" />' +
                      QUILocale.get('quiqqer/quiqqer', 'mail.settings.verify_peer_name') +
                      '      </label>' +
                      '  </p>' +
                      '</td>'
            });

            AllowSelfSigned.set({
                html: '<td>' +
                      '  <p>' +
                      '      <label class="checkbox-label hasCheckbox">' +
                      '           <input type="checkbox" name="mail.SMTPSecureSSL_allow_self_signed" />' +
                      QUILocale.get('quiqqer/quiqqer', 'mail.settings.allow_self_signed') +
                      '      </label>' +
                      '  </p>' +
                      '</td>'
            });

            VerifyPeer.addClass(evenCssClass ? 'odd' : 'even');
            VerifyPeerName.addClass(evenCssClass ? 'even' : 'odd');
            AllowSelfSigned.addClass(evenCssClass ? 'odd' : 'even');

            VerifyPeer.inject(ParentTable);
            VerifyPeerName.inject(ParentTable);
            AllowSelfSigned.inject(ParentTable);

            var config = Panel.$config;

            if (config.mail) {
                if ("SMTPSecureSSL_allow_self_signed" in config.mail) {
                    AllowSelfSigned.getElement('input').checked = parseInt(config.mail.SMTPSecureSSL_allow_self_signed);
                }

                if ("SMTPSecureSSL_verify_peer" in config.mail) {
                    VerifyPeer.getElement('input').checked = parseInt(config.mail.SMTPSecureSSL_verify_peer);
                }

                if ("SMTPSecureSSL_verify_peer_name" in config.mail) {
                    VerifyPeerName.getElement('input').checked = parseInt(config.mail.SMTPSecureSSL_verify_peer_name);
                }
            }

            moofx(VerifyPeer).animate({
                height: VerifyPeer.getScrollSize().y
            }, {
                duration: 250,
                equation: 'cubic-bezier(.17,.67,.6,1.24)'
            });

            moofx(VerifyPeerName).animate({
                height: VerifyPeerName.getScrollSize().y
            }, {
                duration: 250,
                equation: 'cubic-bezier(.17,.67,.6,1.24)'
            });

            moofx(AllowSelfSigned).animate({
                height: AllowSelfSigned.getScrollSize().y
            }, {
                duration: 250,
                equation: 'cubic-bezier(.17,.67,.6,1.24)'
            });
        },

        /**
         * Hide SSL options
         */
        hideSSLOptions: function () {
            var Panel   = this.$Panel,
                Content = Panel.getContent(),
                rows    = Content.getElements('.ssl-option-row');

            if (!rows.length) {
                return;
            }

            moofx(rows).animate({
                height  : 0,
                opacity : 0,
                overflow: 'hidden',
                position: 'relative'
            }, {
                duration: 250,
                callback: function () {
                    rows.destroy();
                }
            });
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

            if (Form.elements['mail.SMTPSecure'].value == 'ssl') {
                params.SMTPSecureSSL_verify_peer       = Form.elements['mail.SMTPSecureSSL_verify_peer'].checked ? 1 : 0;
                params.SMTPSecureSSL_verify_peer_name  = Form.elements['mail.SMTPSecureSSL_verify_peer_name'].checked ? 1 : 0;
                params.SMTPSecureSSL_allow_self_signed = Form.elements['mail.SMTPSecureSSL_allow_self_signed'].checked ? 1 : 0;
            }

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
