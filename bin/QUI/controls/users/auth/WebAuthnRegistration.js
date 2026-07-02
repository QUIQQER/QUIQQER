define('controls/users/auth/WebAuthnRegistration', [

    'qui/controls/Control',
    'package/quiqqer/frontend-users/bin/Registration',
    'Ajax',
    'Locale',
    'controls/users/auth/WebAuthnUtils'

], function (QUIControl, Registration, QUIAjax, QUILocale, WebAuthnUtils) {
    'use strict';

    const lg = 'quiqqer/core';

    return new Class({
        Extends: QUIControl,
        Type: 'controls/users/auth/WebAuthnRegistration',

        Binds: [
            '$onImport',
            '$register'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const button = this.getElm().querySelector('[name="register-passkey"]');
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
                button.addEventListener('click', this.$register);
            }
        },

        $register: function (event) {
            event.preventDefault();
            event.stopPropagation();

            const container = this.getElm();
            const button = container.querySelector('[name="register-passkey"]');
            const username = container.querySelector('[name="username"]');
            const credentialName = container.querySelector('[name="credentialName"]');
            const attestation = container.querySelector('[name="attestation"]');

            if (!username.value.trim()) {
                username.focus();
                return;
            }

            button.disabled = true;

            new Promise((resolve, reject) => {
                QUIAjax.get('ajax_users_authenticator_webauthn_beginUserRegistration', resolve, {
                    username: username.value.trim(),
                    displayName: username.value.trim(),
                    name: credentialName.value.trim(),
                    onError: reject
                });
            }).then((options) => {
                return navigator.credentials.create(
                    WebAuthnUtils.prepareCreateOptions(options)
                );
            }).then((credential) => {
                attestation.value = JSON.encode(WebAuthnUtils.serializeAttestation(credential));

                return Registration.register('QUI\\Registration\\WebAuthn\\Registrar', {
                    username: username.value.trim(),
                    credentialName: credentialName.value.trim(),
                    attestation: attestation.value
                });
            }).catch((err) => {
                button.disabled = false;

                if (window.console) {
                    console.error(err);
                }
            });
        }
    });
});
