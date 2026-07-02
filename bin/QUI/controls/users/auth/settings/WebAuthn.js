define('controls/users/auth/settings/WebAuthn', [

    'qui/controls/Control',
    'Ajax',
    'Locale',
    'controls/users/auth/WebAuthnUtils'

], function (QUIControl, QUIAjax, QUILocale, WebAuthnUtils) {
    'use strict';

    const lg = 'quiqqer/core';

    return new Class({
        Extends: QUIControl,
        Type: 'controls/users/auth/settings/WebAuthn',

        Binds: [
            '$onImport',
            '$createPasskey',
            '$deletePasskey'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const button = this.getElm().querySelector('[name="create-passkey"]');
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
                button.addEventListener('click', this.$createPasskey);
            }

            Array.from(this.getElm().querySelectorAll('[name="delete-passkey"]')).forEach((deleteButton) => {
                deleteButton.addEventListener('click', this.$deletePasskey);
            });
        },

        $createPasskey: function (event) {
            event.preventDefault();
            event.stopPropagation();

            const userUuid = this.getElm().getAttribute('data-user-uuid') || '';
            const button = this.getElm().querySelector('[name="create-passkey"]');
            const name = this.getElm().querySelector('[name="credential-name"]');
            const credentialName = name ? name.value : '';

            button.disabled = true;

            new Promise((resolve, reject) => {
                QUIAjax.get('ajax_users_authenticator_webauthn_beginRegistration', resolve, {
                    userUuid: userUuid,
                    name: credentialName,
                    onError: reject
                });
            }).then((options) => {
                return navigator.credentials.create(
                    WebAuthnUtils.prepareCreateOptions(options)
                );
            }).then((credential) => {
                return new Promise((resolve, reject) => {
                    QUIAjax.post('ajax_users_authenticator_webauthn_finishRegistration', resolve, {
                        userUuid: userUuid,
                        name: credentialName,
                        attestation: JSON.encode(WebAuthnUtils.serializeAttestation(credential)),
                        onError: reject
                    });
                });
            }).then(() => {
                button.disabled = false;
                this.fireEvent('completed');
            }).catch((err) => {
                button.disabled = false;

                if (window.console) {
                    console.error(err);
                }
            });
        },

        $deletePasskey: function (event) {
            event.preventDefault();
            event.stopPropagation();

            const userUuid = this.getElm().getAttribute('data-user-uuid') || '';
            const button = event.target.nodeName === 'BUTTON' ? event.target : event.target.closest('button');
            button.disabled = true;

            new Promise((resolve, reject) => {
                QUIAjax.post('ajax_users_authenticator_webauthn_deleteCredential', resolve, {
                    userUuid: userUuid,
                    id: button.getAttribute('data-id'),
                    onError: reject
                });
            }).then(() => {
                const entry = button.closest('.quiqqer-webauthn-credential');

                if (entry && entry.parentNode) {
                    entry.parentNode.removeChild(entry);
                }
            }).catch((err) => {
                button.disabled = false;

                if (window.console) {
                    console.error(err);
                }
            });
        }
    });
});
