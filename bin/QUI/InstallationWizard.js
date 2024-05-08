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
    const STATUS_SET_UP_STARTED = 1;
    const STATUS_SET_UP_DONE = 2;

    let StepsContainer,
        NextButton, NextButtonContainer;

    let CurrentProvider = null;
    let currentStep = null;
    let CurrentControl = null;
    let WizardWindow = null;
    let formData = {};
    let CURRENT_MAX_STEP = 0; // der maximale step welcher schon ausgewählt wurde

    let finishButtonTitle = '';

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

                // open installation wizard
                require(['qui/controls/windows/Popup'], (Window) => {
                    const sizes = QUI.getWindowSize();

                    let maxHeight = 800,
                        maxWidth  = 1200;

                    if (sizes.y * 0.9 < maxHeight) {
                        maxHeight = Math.round(sizes.y * 0.9);
                    }

                    if (sizes.x * 0.9 < maxWidth) {
                        maxWidth = Math.round(sizes.x * 0.9);
                    }

                    WizardWindow = new Window({
                        title             : QUILocale.get('quiqqer/core', 'quiqqer.setup.window.title'),
                        maxHeight         : maxHeight,
                        maxWidth          : maxWidth,
                        resizable         : false,
                        icon              : 'fa fa-magic',
                        backgroundClosable: false,
                        events            : {
                            onCreate: (Win) => {
                                Win.getElm().addClass('installation-wizard');
                                Win.$Buttons.getElements('button').destroy();

                                StepsContainer = new Element('div.steps-container').inject(Win.$Buttons);
                                NextButtonContainer = new Element('div.next-button').inject(Win.$Buttons);

                                NextButton = new Element('button', {
                                    'class': 'qui-button',
                                    html   : QUILocale.get('quiqqer/core', 'set.up.next.button.text'),
                                    events : {
                                        click: this.$clickNextButton.bind(this)
                                    }
                                }).inject(NextButtonContainer);

                                Win.Loader.show();
                            },

                            onOpen: () => {
                                this.$loadInstallation(list);
                            },

                            onCancel: () => {
                                let providers = list.map(function (entry) {
                                    return entry.class;
                                });

                                QUIAjax.post('ajax_installationWizard_cancel', function () {
                                    // nothing
                                }, {
                                    'package': 'quiqqer/core',
                                    providers: JSON.encode(providers)
                                });
                            }
                        }
                    });

                    WizardWindow.open();
                });
            }, {
                'package': 'quiqqer/core',
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

            new Element('img', {
                'class': 'installation-wizard-logo',
                src    : list[0].logo
            }).inject(WizardWindow.getContent());

            finishButtonTitle = list[0].finishButton;

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
                    'data-step': i,
                    events     : {
                        click: this.$stepClick.bind(this)
                    }
                }).inject(StepsContainer);
            }

            this.loadStep(0).catch((err) => {
                console.error(err);
            });
        },

        $stepClick: function (e) {
            const Step = e.target;

            if (Step.hasClass('steps-container-step--clickable')) {
                this.loadStep(parseInt(Step.get('data-step'))).catch(function (err) {
                    console.error(err);
                });
            }
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
                    WizardWindow.getContent().getChildren().forEach(function (Node) {
                        if (!Node.hasClass('installation-wizard-logo')) {
                            Node.destroy();
                        }
                    });

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
                        NextButton.set('html', finishButtonTitle);
                    } else {
                        NextButton.set('html', QUILocale.get('quiqqer/core', 'set.up.next.button.text'));
                    }

                    if (providerSteps[step].jsControl !== '') {
                        Container.set('data-qui', providerSteps[step].jsControl);
                    }

                    Container.getElements('form').addEvent('submit', function (e) {
                        e.stop();
                    });

                    QUI.parse(WizardWindow.getContent()).then(() => {
                        if (Container.get('data-quiid')) {
                            CurrentControl = QUI.Controls.getById(Container.get('data-quiid'));
                            CurrentControl.setAttribute('Wizard', this);

                            if (typeof CurrentControl.load === 'function') {
                                CurrentControl.load();
                            }
                        }

                        currentStep = step;

                        // refresh steps
                        if (currentStep > CURRENT_MAX_STEP) {
                            CURRENT_MAX_STEP = currentStep;
                        }

                        let i, Step;

                        for (i = 0; i <= CURRENT_MAX_STEP; i++) {
                            Step = StepsContainer.getElement('.steps-container-step:nth-child(' + (i + 1) + ')');

                            if (Step) {
                                Step.addClass('steps-container-step--clickable');
                            } else {
                                Step.removeClass('steps-container-step--clickable');
                            }
                        }

                        resolve();
                    });
                }, {
                    'package': 'quiqqer/core',
                    provider : CurrentProvider.class,
                    step     : step
                });
            });

            // get current form data
            const Form = WizardWindow.getContent().getElement('form');

            if (Form) {
                formData = Object.assign(formData, FormUtils.getDataFromNode(Form));
            }

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

            WizardWindow.Loader.show();

            let steps = StepsContainer.getElements('.steps-container-step');

            if (currentStep >= steps.length - 1) {
                WizardWindow.Loader.setAttribute('closetime', 500000);

                // execute
                QUIAjax.post('ajax_installationWizard_execute', (saved) => {
                    if (saved) {
                        WizardWindow.close();

                        // @todo  open iframe

                        new Element('iframe', {
                            src   : URL_OPT_DIR + 'quiqqer/core/src/QUI/InstallationWizard/bin/execute.php',
                            styles: {
                                background: '#fff',
                                border    : 0,
                                height    : '100%',
                                left      : 0,
                                position  : 'absolute',
                                top       : 0,
                                width     : '100%',
                                zIndex    : 1000
                            }
                        }).inject(document.body);

                        return;
                    }

                    // @todo show some errors

                    /*
                    QUI.getMessageHandler().then(function (MH) {
                        MH.addSuccess(
                            QUILocale.get('quiqqer/core', 'quiqqer.setup.success')
                        );
                    });

                    WizardWindow.close();

                    // reload
                    window.location.reload();
                    */
                }, {
                    'package': 'quiqqer/core',
                    provider : CurrentProvider.class,
                    data     : JSON.encode(formData),
                    onError  : function (err) {
                        QUI.getMessageHandler().then(function (MH) {
                            MH.addError(err.getMessage());
                        });

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
            WizardWindow.Loader.show();
        },

        hideLoader: function () {
            WizardWindow.Loader.hide();
        }

        //endregion
    };
});
