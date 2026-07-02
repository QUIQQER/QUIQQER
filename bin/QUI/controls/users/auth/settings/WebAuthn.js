define('controls/users/auth/settings/WebAuthn', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'controls/users/auth/WebAuthnUtils'

], function (QUI, QUIControl, QUIAjax, QUILocale, WebAuthnUtils) {
    'use strict';

    const lg = 'quiqqer/core';

    return new Class({
        Extends: QUIControl,
        Type: 'controls/users/auth/settings/WebAuthn',

        Binds: [
            '$onImport',
            '$createPasskey',
            '$deletePasskey',
            '$refresh',
            '$setLoading',
            '$getWindow'
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
            const message = this.getElm().querySelector('[data-name="message"]');
            const credentialName = name ? name.value : '';

            if (message) {
                message.innerHTML = '';
            }

            this.$setLoading(true);
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
                return this.$refresh();
            }).then(() => {
                this.fireEvent('completed');
            }).catch((err) => {
                button.disabled = false;
                this.$setLoading(false);

                if (message) {
                    message.innerHTML = typeof err.getMessage === 'function'
                        ? err.getMessage()
                        : QUILocale.get(lg, WebAuthnUtils.getErrorLocaleKey(err));
                }

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
            this.$setLoading(true);
            button.disabled = true;

            new Promise((resolve, reject) => {
                QUIAjax.post('ajax_users_authenticator_webauthn_deleteCredential', resolve, {
                    userUuid: userUuid,
                    id: button.getAttribute('data-id'),
                    onError: reject
                });
            }).then(() => {
                return this.$refresh();
            }).catch((err) => {
                button.disabled = false;
                this.$setLoading(false);

                if (window.console) {
                    console.error(err);
                }
            });
        },

        $refresh: function () {
            const container = this.getElm().parentNode;
            const userUuid = this.getElm().getAttribute('data-user-uuid') || '';
            const Win = this.$getWindow();

            if (Win && Win.Loader) {
                Win.Loader.show();
            }

            return new Promise((resolve, reject) => {
                QUIAjax.get('ajax_users_authenticator_settings', (html) => {
                    container.innerHTML = html;
                    QUI.parse(container).then(() => {
                        if (Win && Win.Loader) {
                            Win.Loader.hide();
                        }

                        resolve();
                    }).catch((err) => {
                        if (Win && Win.Loader) {
                            Win.Loader.hide();
                        }

                        reject(err);
                    });
                }, {
                    uid: userUuid,
                    authenticator: 'QUI\\Users\\Auth\\WebAuthn',
                    onError: (err) => {
                        if (Win && Win.Loader) {
                            Win.Loader.hide();
                        }

                        reject(err);
                    }
                });
            });
        },

        $setLoading: function (loading) {
            const Win = this.$getWindow();

            if (!Win || !Win.Loader) {
                return;
            }

            if (loading) {
                Win.Loader.show();
                return;
            }

            Win.Loader.hide();
        },

        $getWindow: function () {
            const popup = this.getElm().closest('.qui-window-popup');

            if (!popup) {
                return null;
            }

            const quiId = popup.getAttribute('data-quiid');

            if (!quiId) {
                return null;
            }

            return QUI.Controls.getById(quiId);
        }
    });
});
