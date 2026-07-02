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
            '$setBusy',
            '$clearMessage',
            '$showMessage'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$running = false;
            this.$messageTimer = null;

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

            if (this.$running) {
                return;
            }

            const form = this.getElm().closest('form');
            const button = this.getElm().querySelector('[name="passkey-login"]');
            const assertion = this.getElm().querySelector('[name="assertion"]');
            const message = this.getElm().querySelector('[data-name="message"]');

            if (message) {
                this.$clearMessage();
            }

            this.$setBusy(true);

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
                this.$setBusy(false);

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

        $setBusy: function (busy) {
            const button = this.getElm().querySelector('[name="passkey-login"]');

            this.$running = busy;

            if (!button) {
                return;
            }

            if (busy) {
                button.disabled = true;
                button.setAttribute('disabled', 'disabled');
                return;
            }

            button.disabled = false;
            button.removeAttribute('disabled');
        },

        $clearMessage: function () {
            const message = this.getElm().querySelector('[data-name="message"]');

            if (this.$messageTimer) {
                window.clearTimeout(this.$messageTimer);
                this.$messageTimer = null;
            }

            if (!message) {
                return;
            }

            const messageNode = message.querySelector('.quiqqer-webauthn-login-message-entry');

            if (!messageNode) {
                message.innerHTML = '';
                return;
            }

            messageNode.classList.remove('is-visible');

            window.setTimeout(() => {
                if (messageNode.parentNode) {
                    messageNode.parentNode.removeChild(messageNode);
                }
            }, 180);
        },

        $showMessage: function (text, autoHide) {
            const message = this.getElm().querySelector('[data-name="message"]');

            if (!message) {
                return;
            }

            if (this.$messageTimer) {
                window.clearTimeout(this.$messageTimer);
                this.$messageTimer = null;
            }

            message.innerHTML = '<div class="messages-message message-error quiqqer-webauthn-login-message-entry">'
                + text
                + '</div>';

            const messageNode = message.querySelector('.quiqqer-webauthn-login-message-entry');

            window.requestAnimationFrame(() => {
                if (messageNode) {
                    messageNode.classList.add('is-visible');
                }
            });

            if (autoHide) {
                this.$messageTimer = window.setTimeout(() => {
                    this.$clearMessage();
                }, 5000);
            }
        }
    });
});
