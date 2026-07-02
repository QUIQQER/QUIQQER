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
            '$login'
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

                if (message) {
                    message.innerHTML = QUILocale.get(lg, 'quiqqer.webauthn.error.unsupported');
                }

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
                message.innerHTML = '';
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

                if (message) {
                    message.innerHTML = typeof err.getMessage === 'function'
                        ? err.getMessage()
                        : QUILocale.get(lg, WebAuthnUtils.getErrorLocaleKey(err));
                }

                if (window.console) {
                    console.error(err);
                }
            });
        }
    });
});
