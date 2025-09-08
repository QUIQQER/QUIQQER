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
                            Zwei-Faktor-Authentifizierung einrichten?
                        </h1>
                        <p>
                            Um deine Anmeldung noch sicherer zu machen, kannst du eine Zwei-Faktor-Authentifizierung aktivieren. 
                            Bitte wähle eine Methode aus und aktiviere sie, um fortzufahren.
                        </p>
                    </div>
                </section>
                <div style="display: flex; gap: 1rem; flex-direction: column">
                    <button name="setup-secondary-authenticator" class="btn btn-primary w-full">
                        <span class="fa fa-shield-alt"></span>
                        <span>Zwei-Faktor-Authentifizierung einrichten</span>
                    </button>
                    
                    <button name="no-setup" class="btn btn-secondary w-full">
                        <span>Jetzt nicht</span>
                    </button>
                </div>
            `;

            container.querySelector('[name="no-setup"]').addEventListener('click', () => {
                this.cancel();
            });

            container.querySelector(
                '[name="setup-secondary-authenticator"]'
            ).addEventListener('click', () => {
                this.Loader.show();
                window.location = '/profile/user/2fa';
            });

            this.Loader.hide();
        }
    });
});
