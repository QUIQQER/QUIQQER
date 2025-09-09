define('controls/users/auth/ShowSecondaryAuthenticatorWindow', [

    'qui/QUI',
    'qui/controls/windows/Popup',
    'Locale',

    'css!controls/users/auth/ShowSecondaryAuthenticatorWindow.css'

], function (QUI, QUIPopup, QUILocale) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIPopup,
        Type: 'controls/users/auth/ShowSecondaryAuthenticatorWindow',

        options: {
            authenticator: false // enable a specific authenticator
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                maxHeight: 600,
                maxWidth: 600,
                buttons: false,
                closeable: false,
                backgroundClosable: false,
                autoclose: false
            });

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        $onOpen: function () {
            this.Loader.show();
            const container = this.getContent();

            container.classList.add('default-content');
            container.innerHTML = `
                <section>
                    <div style="text-align: center">
                        <span class="fa fa-shield-alt" style="margin: 2rem 0; font-size: 4rem;"></span>    
                        <h1 style="font-size: 2rem">
                            ${QUILocale.get(lg, 'quiqqer.window.show.2fa.info.title')}
                        </h1>
                        
                        ${QUILocale.get(lg, 'quiqqer.window.show.2fa.info.description')}
                    </div>
                </section>
                <div style="display: flex; gap: 1rem; flex-direction: column">
                    <button name="setup-secondary-authenticator" class="btn btn-primary w-full">
                        <span class="fa fa-shield-alt"></span>
                        <span>${QUILocale.get(lg, 'quiqqer.window.show.2fa.info.button.2fa')}</span>
                    </button>
                    
                    <button name="no-setup" class="btn btn-secondary w-full">
                        <span>${QUILocale.get(lg, 'quiqqer.window.show.2fa.info.button.not')}</span>
                    </button>
                </div>
            `;

            container.querySelector('[name="no-setup"]').addEventListener('click', () => {
                this.cancel();
            });

            container.querySelector(
                '[name="setup-secondary-authenticator"]'
            ).addEventListener('click', () => {
                QUIAjax.get('package_quiqqer_frontend-users_ajax_frontend_login_getLoginRedirect', (url) => {
                    this.Loader.show();

                    if (url) {
                        window.location = url;
                        return;
                    }

                    window.location = '/';
                }, {
                    'package': 'quiqqer/frontend-users',
                    project: QUIQQER_PROJECT.name,
                    onError: () => {
                        window.location = '/';
                    }
                });
            });

            this.Loader.hide();
        }
    });
});
