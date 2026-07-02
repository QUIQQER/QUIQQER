define('controls/users/auth/WebAuthnLogin', [

    'qui/controls/Control',
    'Ajax',
    'Locale',
    'controls/users/auth/WebAuthnUtils'

], function (QUIControl, QUIAjax, QUILocale, WebAuthnUtils) {
    'use strict';

    const lg = 'quiqqer/core';

    return new Class({
        Extends: QUIControl,
        Type: 'controls/users/auth/WebAuthnLogin',

        Binds: [
            '$onImport',
            '$login',
            '$clearMessage',
            '$showMessage'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const button = this.getElm().querySelector('[name="passkey-login"]');
            const message = this.getElm().querySelector('[data-name="message"]');

            if (!WebAuthnUtils.isSupported()) {
                if (button) {
                    button.disabled = true;
                }

                this.$showMessage(QUILocale.get(lg, 'quiqqer.webauthn.error.unsupported'), false);

                return;
            }

            if (button) {
                button.addEventListener('click', this.$login);
            }
        },

        $login: function (event) {
            event.preventDefault();
            event.stopPropagation();

            const form = this.getElm().closest('form');
            const button = this.getElm().querySelector('[name="passkey-login"]');
            const assertion = this.getElm().querySelector('[name="assertion"]');
            const message = this.getElm().querySelector('[data-name="message"]');

            if (message) {
                this.$clearMessage();
            }

            button.disabled = true;

            new Promise((resolve, reject) => {
                QUIAjax.get('ajax_users_authenticator_webauthn_beginLogin', resolve, {
                    onError: reject
                });
            }).then((options) => {
                return navigator.credentials.get(
                    WebAuthnUtils.prepareGetOptions(options)
                );
            }).then((credential) => {
                assertion.value = JSON.encode(WebAuthnUtils.serializeAssertion(credential));
                form.dispatchEvent(new Event('submit', {cancelable: true, bubbles: true}));
            }).catch((err) => {
                button.disabled = false;

                this.$showMessage(
                    typeof err.getMessage === 'function'
                        ? err.getMessage()
                        : QUILocale.get(lg, WebAuthnUtils.getErrorLocaleKey(err)),
                    true
                );

                if (window.console) {
                    console.error(err);
                }
            });
        },

        $clearMessage: function () {
            const message = this.getElm().querySelector('[data-name="message"]');

            if (message) {
                message.innerHTML = '';
            }
        },

        $showMessage: function (text, autoHide) {
            const message = this.getElm().querySelector('[data-name="message"]');

            if (!message) {
                return;
            }

            message.innerHTML = '<div class="messages-message message-error">' + text + '</div>';

            if (autoHide) {
                window.setTimeout(() => {
                    this.$clearMessage();
                }, 5000);
            }
        }
    });
});
