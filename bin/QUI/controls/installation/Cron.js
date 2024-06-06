/**
 * @module controls/installation/Cron
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/installation/Cron', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',

    'css!controls/installation/Cron.css'

], function (QUI, QUIControl, QUIAjax, QUILocale) {
    "use strict";

    let USE_CRON_SERVICE = null;

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/installation/Cron',

        Binds: [
            '$onImport',
            'useCronService',
            'useNoCronService'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const Buttons = this.getElm().getElements('.quiqqer-setup-cron-btns');

            this.getElm().getElement('div').addClass('quiqqer-setup-cron');

            Buttons.getElement('[name="use-cronservice"]').addEvent('click', this.useCronService);
            Buttons.getElement('[name="no-cronservice"]').addEvent('click', this.useNoCronService);
        },

        next: function () {
            if (USE_CRON_SERVICE !== null) {
                return true;
            }

            const Email = this.getElm().getElement('[type="email"]');
            const Wizard = this.getAttribute('Wizard');

            if (USE_CRON_SERVICE === null && !Email) {
                return false;
            }

            Wizard.showLoader();

            this.sendRegistration(Email.value).then(() => {
                USE_CRON_SERVICE = true;
                Wizard.next();
            }).catch(() => {
                // @todo show message
                Wizard.hideLoader();
                Email.focus();

                if ("checkValidity" in Email) {
                    Email.checkValidity();
                }

                // chrome validate message
                if ("reportValidity" in Email) {
                    Email.reportValidity();
                }
            });

            return false;
        },

        useCronService: function () {
            const Form = this.getElm().getElement('form');
            const Buttons = this.getElm().getElements('.quiqqer-setup-cron-btns button');

            moofx(Buttons).animate({
                opacity: 0
            }, {
                duration: 200,
                callback: () => {
                    Buttons.setStyles({
                        display: 'none'
                    });

                    const Input = new Element('input', {
                        type       : 'email',
                        placeholder: 'hello@email.com',
                        required   : 'required',
                        styles     : {
                            'float': 'left',
                            opacity: 0,
                            width  : 400
                        }
                    }).inject(Form);

                    const Cancel = new Element('button', {
                        type   : 'button',
                        'class': 'qui-button',
                        html   : QUILocale.get('quiqqer/core', 'quiqqer.setup.cron.button.next'),
                        styles : {
                            clear  : 'both',
                            margin : '5px 5px 0 0',
                            opacity: 0,
                            width  : 200,
                        },
                        events : {
                            click: () => {
                                USE_CRON_SERVICE = false;
                                this.getAttribute('Wizard').next();
                            }
                        }
                    }).inject(Form);

                    const Button = new Element('button', {
                        type   : 'button',
                        'class': 'qui-button btn-green',
                        html   : QUILocale.get('quiqqer/core', 'quiqqer.setup.cron.button.register'),
                        styles : {
                            margin : '5px 0 0 0',
                            opacity: 0,
                            width  : 195
                        },
                        events : {
                            click: () => {
                                this.getAttribute('Wizard').next();
                            }
                        }
                    }).inject(Form);

                    moofx([
                        Input,
                        Button,
                        Cancel
                    ]).animate({
                        opacity: 1
                    });
                }
            });
        },

        useNoCronService: function () {
            USE_CRON_SERVICE = false;
            this.getAttribute('Wizard').next();
        },

        sendRegistration: function (email) {
            if (email === '') {
                return Promise.reject();
            }

            return new Promise((resolve, reject) => {
                QUIAjax.post('package_quiqqer_cron_ajax_cronservice_sendRegistration', resolve, {
                    'package': 'quiqqer/cron',
                    email    : email,
                    onError  : reject
                });
            });
        }
    });
});
