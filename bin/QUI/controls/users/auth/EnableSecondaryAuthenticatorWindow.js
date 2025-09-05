define('controls/users/auth/EnableSecondaryAuthenticatorWindow', [

    'qui/QUI',
    'qui/controls/windows/Popup',
    'Ajax',
    'Locale',

    'css!controls/users/auth/EnableSecondaryAuthenticatorWindow.css'

], function (QUI, QUIPopup, QUIAjax, QUILocale) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIPopup,
        Type: 'controls/users/auth/EnableSecondaryAuthenticatorWindow',

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                maxHeight: 800,
                maxWidth: 600,
                buttons: false,
                closeable: false,
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
                <section data-name="authenticator-list">
                    <div style="text-align: center">
                        <span class="fa fa-shield-alt" style="margin: 2rem 0; font-size: 4rem;"></span>    
                        <h1 style="font-size: 2rem">
                            Zwei-Faktor-Authentifizierung erforderlich
                        </h1>
                        <p>
                            Um deine Anmeldung noch sicherer zu machen, musst du eine Zwei-Faktor-Authentifizierung aktivieren. 
                            Bitte wähle eine Methode aus und aktiviere sie, um fortzufahren.
                        </p>
                    </div>
                    <div data-name="enable-secondary-authenticators"></div>
                </section>
                <section data-name="authenticator-settings" style="opacity: 0;"></section>
            `;

            QUIAjax.get('ajax_users_authenticator_getSecondaryAuthenticators', (list) => {
                list.forEach((authenticator) => {
                    console.log(authenticator);

                    const authNode = document.createElement('div')

                    authNode.classList.add('enable-secondary-authenticators-authenticator');
                    authNode.setAttribute('data-name', 'enable-secondary-authenticators-authenticator');

                    authNode.innerHTML = `
                        <h2>${authenticator.frontend.title}</h2>
                        <p>${authenticator.frontend.description}</p>
                        <button class="btn btn-primary">
                            Aktivieren
                        </button>
                    `;

                    const button = authNode.querySelector('button');
                    button.setAttribute('data-authenticator', authenticator.authenticator)

                    if (authenticator.hasSettings) {
                        button.addEventListener('click', (e) => {
                            const button = e.target.nodeName === 'BUTTON' ? e.target : e.target.parentNode;

                            this.$showAuthenticatorSettings(
                                button.getAttribute('data-authenticator')
                            );
                        });
                    } else {
                        button.addEventListener('click', (e) => {
                            const button = e.target.nodeName === 'BUTTON' ? e.target : e.target.parentNode;

                            this.$enableAuthenticator(
                                button.getAttribute('data-authenticator')
                            );
                        });
                    }

                    container
                        .querySelector('[data-name="enable-secondary-authenticators"]')
                        .appendChild(authNode);
                });

                this.Loader.hide();
            });
        },

        $showAuthenticatorSettings: function (authenticator) {
            const list = this.getContent().querySelector('[data-name="authenticator-list"]');
            const settings = this.getContent().querySelector('[data-name="authenticator-settings"]');

            settings.style.opacity = 0;
            settings.style.position = 'absolute';
            settings.style.left = '-20px';
            settings.style.top = '0';
            settings.style.width = '100%';
            settings.style.height = '100%';

            this.Loader.show();

            return new Promise((resolve) => {
                QUIAjax.get('ajax_users_authenticator_secondarySettings', (settingHtml) => {
                    settings.innerHTML = settingHtml;

                    QUI.parse(settings).then(() => {
                        const settingsNode = settings.querySelector('[data-qui]');
                        const settingsInstance = QUI.Controls.getById(settingsNode.getAttribute('data-quiid'));

                        settingsInstance.addEvents({
                            completed: () => {
                                moofx(settings).animate({
                                    left: -10,
                                    opacity: 0
                                }, {
                                    duration: 250,
                                    callback: () => {
                                        settings.style.display = 'none';
                                        settings.parentNode.removeChild(settings);
                                        resolve();
                                    }
                                });
                            }
                        });

                        moofx(list).animate({
                            opacity: 0,
                            left: -20
                        }, {
                            duration: 250
                        });

                        moofx(settings).animate({
                            left: 0,
                            opacity: 1
                        }, {
                            duration: 250,
                            callback: () => {
                                this.Loader.hide();
                            }
                        });
                    });
                }, {
                    authenticator: authenticator,
                    uid: QUIQQER_USER.id,
                    onError: (err) => {
                        console.error(err);
                    }
                });
            });
        },

        $enableAuthenticator: function (authenticator) {
            console.log(authenticator);
        }
    });
});