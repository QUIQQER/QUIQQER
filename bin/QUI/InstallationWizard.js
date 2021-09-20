/**
 * Installation wizard
 * - checks if plugins / modules needs a set up
 */
define('InstallationWizard', [

    'qui/QUI',
    'Ajax',
    'Locale',

    'css!InstallationWizard.css'

], function (QUI, QUIAjax, QUILocale) {
    "use strict";

    const STATUS_SET_UP_NOT_STARTED = 0;
    const STATUS_SET_UP_STARTED     = 1;
    const STATUS_SET_UP_DONE        = 2;

    let StepsContainer,
        NextButton, NextButtonContainer;

    let CurrentProvider = null;
    let WizardWindow    = null;

    // @todo execute setup
    // @todo save all fields from form
    // @todo step changing -> next click
    // @todo last step -> execute setup
    // @todo multiple setups (module / plugin)

    return {
        /**
         * Loads the wizard - and checks if
         */
        load: function () {
            return;
            QUIAjax.get('ajax_installationWizard_get', (list) => {
                if (!list.length) {
                    return;
                }

                console.log('Installation wizard loading', list);

                // open installation wizard
                require(['qui/controls/windows/Popup'], (Window) => {
                    // @todo height + width -> max 90%

                    WizardWindow = new Window({
                        title    : 'Welcome to the QUIQQER Setup',
                        maxHeight: 800,
                        maxWidth : 1200,
                        resizable: false,
                        events   : {
                            onCreate: function (Win) {
                                Win.getElm().addClass('installation-wizard');
                                Win.$Buttons.getElements('button').destroy();

                                StepsContainer      = new Element('div.steps-container').inject(Win.$Buttons);
                                NextButtonContainer = new Element('div.next-button').inject(Win.$Buttons);

                                NextButton = new Element('button', {
                                    html: 'Next' // @todo locale
                                }).inject(NextButtonContainer);

                                Win.Loader.show();
                            },

                            onOpen: (Win) => {
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

            this.loadStep(0);
        },

        loadStep: function (step) {
            let providerSteps = CurrentProvider.steps;

            if (typeof providerSteps[step] === 'undefined') {
                return;
            }

            WizardWindow.Loader.show();
            QUIAjax.get('ajax_installationWizard_getStep', function (html) {
                WizardWindow.getContent().set('html', html);

                QUI.parse(WizardWindow.getContent()).then(function () {
                    WizardWindow.Loader.hide();
                });
            }, {
                'package': 'quiqqer/quiqqer',
                provider : CurrentProvider.class,
                step     : step,
            });
        }
    };
});
