/**
 * Installation wizard
 * - checks if plugins / modules needs a set up
 */
define('InstallationWizard', [

    'qui/QUI',
    'Ajax',
    'Locale',
    'qui/utils/Form',

    'css!InstallationWizard.css'

], function (QUI, QUIAjax, QUILocale, FormUtils) {
    "use strict";

    const STATUS_SET_UP_NOT_STARTED = 0;
    const STATUS_SET_UP_STARTED     = 1;
    const STATUS_SET_UP_DONE        = 2;

    let StepsContainer,
        NextButton, NextButtonContainer;

    let CurrentProvider = null;
    let currentStep     = null;
    let CurrentControl  = null;
    let WizardWindow    = null;
    let formData        = {};

    // @todo last step -> execute setup
    // @todo multiple setups (module / plugin)

    return {
        /**
         * Loads the wizard - and checks if
         */
        load: function () {
            QUIAjax.get('ajax_installationWizard_get', (list) => {
                if (!list.length) {
                    return;
                }

                console.log('Installation wizard loading', list);

                // open installation wizard
                require(['qui/controls/windows/Popup'], (Window) => {
                    // @todo window height + width -> max 90%

                    WizardWindow = new Window({
                        title    : 'Welcome to the QUIQQER Setup',
                        maxHeight: 800,
                        maxWidth : 1200,
                        resizable: false,
                        icon     : 'fa fa-magic',
                        events   : {
                            onCreate: (Win) => {
                                Win.getElm().addClass('installation-wizard');
                                Win.$Buttons.getElements('button').destroy();

                                StepsContainer = new Element('div.steps-container').inject(Win.$Buttons);

                                NextButtonContainer = new Element('div', {
                                    'class': 'next-button',

                                }).inject(Win.$Buttons);

                                NextButton = new Element('button', {
                                    'class': 'qui-button',
                                    html   : QUILocale.get('quiqqer/quiqqer', 'set.up.next.button.text'),
                                    events : {
                                        click: this.$clickNextButton.bind(this)
                                    }
                                }).inject(NextButtonContainer);

                                Win.Loader.show();
                            },

                            onOpen: () => {
                                this.$loadInstallation(list);
                            }
                        }
                    });

                    WizardWindow.open();
                });

            }, {
                'package': 'quiqqer/quiqqer',
                onError  : function () {
                    // nothing
                }
            });
        },

        getData: function () {
            return formData;
        },

        $loadInstallation: function (list) {
            if (list.length > 1) {
                // create provider select
            }

            this.$loadSteps(list[0]);
        },

        /**
         * @param Provider
         */
        $loadSteps: function (Provider) {
            let steps = Provider.steps;

            CurrentProvider = Provider;
            StepsContainer.set('html', '');

            for (let i = 0; i < steps.length; i++) {
                new Element('div', {
                    'class'    : 'steps-container-step',
                    'data-step': i
                }).inject(StepsContainer);
            }

            this.loadStep(0).catch((err) => {
                console.error(err);
            });
        },

        /**
         * load a step
         *
         * @param {Number} step
         * @return {Promise}
         */
        loadStep: function (step) {
            let providerSteps = CurrentProvider.steps;

            if (typeof providerSteps[step] === 'undefined') {
                return Promise.resolve();
            }

            let Next = Promise.resolve();

            if (CurrentControl) {
                if (typeof CurrentControl.next === 'function') {
                    if (CurrentControl.next() === false) {
                        WizardWindow.Loader.hide();

                        return Promise.resolve();
                    }
                }

                if (CurrentControl.save === 'function') {
                    Next = CurrentControl.save();
                }
            }

            // next step
            WizardWindow.Loader.show();

            let fetchNextStep = new Promise((resolve) => {
                QUIAjax.get('ajax_installationWizard_getStep', (html) => {
                    WizardWindow.getContent().set('html', '');

                    let Container = new Element('div', {
                        html  : html,
                        styles: {
                            height: '100%',
                            width : '100%'
                        }
                    }).inject(WizardWindow.getContent());

                    let steps = StepsContainer.getElements('.steps-container-step');
                    steps.removeClass('steps-container-step--active');

                    if (typeof steps[step] !== 'undefined') {
                        steps[step].addClass('steps-container-step--active');
                    }

                    if (steps.length - 1 === step) {
                        // last step
                        NextButton.set('html', QUILocale.get('quiqqer/quiqqer', 'set.up.execute.button.text'));
                    } else {
                        NextButton.set('html', QUILocale.get('quiqqer/quiqqer', 'set.up.next.button.text'));
                    }

                    if (providerSteps[step].jsControl !== '') {
                        Container.set('data-qui', providerSteps[step].jsControl);
                    }

                    QUI.parse(WizardWindow.getContent()).then(() => {
                        if (Container.get('data-quiid')) {
                            CurrentControl = QUI.Controls.getById(Container.get('data-quiid'));
                            CurrentControl.setAttribute('Wizard', this);
                        }

                        currentStep = step;
                        resolve();
                    });
                }, {
                    'package': 'quiqqer/quiqqer',
                    provider : CurrentProvider.class,
                    step     : step
                });
            });

            // get current form data
            const Form = WizardWindow.getContent().getElement('form');

            if (Form) {
                formData = Object.assign(formData, FormUtils.getFormData(Form));
            }

            console.log(formData);

            return Next.then(() => {
                if (CurrentControl) {
                    CurrentControl.destroy();
                    CurrentControl = null;

                    WizardWindow.getContent().set('data-qui', '');
                    WizardWindow.getContent().set('data-quiid', '');
                    WizardWindow.getContent().set('data-qui-parsed', '');
                }

                return fetchNextStep;
            }).then(() => {
                WizardWindow.Loader.hide();
            }).catch((e) => {
                console.error(e);
            });
        },

        /**
         * next button click
         * - next or execute
         */
        $clickNextButton: function () {
            if (currentStep === null) {
                return;
            }

            // @todo step control save

            WizardWindow.Loader.show();

            let steps = StepsContainer.getElements('.steps-container-step');

            if (currentStep >= steps.length - 1) {
                // execute
                QUIAjax.post('ajax_installationWizard_execute', () => {
                    // @todo success message

                    WizardWindow.close();
                }, {
                    'package': 'quiqqer/quiqqer',
                    provider : CurrentProvider.class,
                    data     : JSON.encode(formData),
                    onError  : function (err) {
                        // @todo error message

                        WizardWindow.Loader.hide();
                    }
                });

                return;
            }

            this.loadStep(currentStep + 1).catch(function (err) {
                console.error(err);
            });
        },

        next: function () {
            this.loadStep(currentStep + 1).catch(function (err) {
                console.error(err);
            });
        },

        //region begin loader

        showLoader: function () {
            console.log('showLoader');

            WizardWindow.Loader.show();
        },

        hideLoader: function () {
            console.log('hideLoader');

            WizardWindow.Loader.hide();
        }

        //endregion
    };
});
