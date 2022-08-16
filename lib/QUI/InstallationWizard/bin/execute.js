// require config
require.config({
    baseUrl: URL_BIN_DIR + 'QUI/',
    paths  : {
        "package"    : URL_OPT_DIR,
        "qui"        : URL_OPT_DIR + 'bin/qui/qui',
        "locale"     : URL_VAR_DIR + 'locale/bin',
        "URL_OPT_DIR": URL_OPT_DIR,
        "URL_BIN_DIR": URL_BIN_DIR,
        "Mustache"   : URL_OPT_DIR + 'bin/quiqqer-asset/mustache/mustache/mustache.min',

        "URI"               : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/URI',
        'IPv6'              : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/IPv6',
        'punycode'          : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/punycode',
        'SecondLevelDomains': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/SecondLevelDomains',
        'Navigo'            : URL_OPT_DIR + 'bin/quiqqer-asset/navigo/navigo/lib/navigo.min',
        'HistoryEvents'     : URL_OPT_DIR + 'bin/quiqqer-asset/history-events/history-events/dist/history-events.min',
        '@popperjs/core'    : URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/lib/tippy/popper.min'
    },

    waitSeconds: 0,
    catchError : true,

    map: {
        '*': {
            'css'  : URL_OPT_DIR + 'bin/qui/qui/lib/css.min.js',
            'image': URL_OPT_DIR + 'bin/qui/qui/lib/image.min.js',
            'text' : URL_OPT_DIR + 'bin/qui/qui/lib/text.min.js'
        }
    }
});

(function () {
    "use strict";

    const WizardSteps = document.querySelector('.wizard-steps');

    const ProcessButton = document.createElement('div');
    ProcessButton.className = 'wizard-show-process';
    ProcessButton.innerHTML = 'Process';
    document.body.appendChild(ProcessButton);

    const StepNode = document.createElement('div');
    WizardSteps.appendChild(StepNode);

    let currentStep = -1;
    let stepData;

    function showNextStep() {
        currentStep++;

        if (typeof window.STEPS[currentStep] === 'undefined') {
            currentStep = 0;
        }

        stepData = window.STEPS[currentStep];
        console.log(stepData);

        require([
            URL_OPT_DIR + 'bin/quiqqer-asset/animejs/animejs/lib/anime.min.js'
        ], function (anime) {
            anime({
                targets : StepNode,
                duration: 500,
                opacity : 0,
                left    : -20,
                easing  : 'easeOutSine',
                complete: function () {
                    StepNode.innerHTML = stepData.content;
                    StepNode.style.left = '20px';

                    anime({
                        targets : StepNode,
                        duration: 500,
                        opacity : 1,
                        left    : 0,
                        easing  : 'easeOutSine',
                        complete: function () {

                            setTimeout(function () {
                                showNextStep();
                            }, 8000);

                        }
                    });
                }
            });
        });
    }

    showNextStep();


    // process click
    ProcessButton.addEventListener('click', function () {
        require([
            URL_OPT_DIR + 'bin/quiqqer-asset/animejs/animejs/lib/anime.min.js'
        ], function (anime) {
            const Pre = document.querySelector('pre');

            if (!Pre) {
                return;
            }

            if (Pre.style.display === 'none') {
                Pre.style.opacity = '0';
                Pre.style.display = 'inline';

                anime({
                    targets : Pre,
                    duration: 500,
                    opacity : 1,
                    easing  : 'easeOutSine'
                });
            } else {
                anime({
                    targets : Pre,
                    duration: 500,
                    opacity : 0,
                    easing  : 'easeOutSine',
                    complete: function () {
                        Pre.style.display = 'none';
                    }
                });
            }
        });
    });
})();
