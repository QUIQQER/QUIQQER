// require config
require.config({
    baseUrl: URL_BIN_DIR + 'QUI/',
    paths: {
        "package": URL_OPT_DIR,
        "qui": URL_OPT_DIR + 'bin/qui/qui',
        "locale": URL_VAR_DIR + 'locale/bin',
        "URL_OPT_DIR": URL_OPT_DIR,
        "URL_BIN_DIR": URL_BIN_DIR,
        "Mustache": URL_OPT_DIR + 'bin/quiqqer-asset/mustache/mustache/mustache.min',

        "URI": URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/URI',
        'IPv6': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/IPv6',
        'punycode': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/punycode',
        'SecondLevelDomains': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/SecondLevelDomains',
        'Navigo': URL_OPT_DIR + 'bin/quiqqer-asset/navigo/navigo/lib/navigo.min',
        'HistoryEvents': URL_OPT_DIR + 'bin/quiqqer-asset/history-events/history-events/dist/history-events.min',
        '@popperjs/core': URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/lib/tippy/popper.min'
    },

    waitSeconds: 0,
    catchError: true,

    map: {
        '*': {
            'css': URL_OPT_DIR + 'bin/qui/qui/lib/css.min.js',
            'image': URL_OPT_DIR + 'bin/qui/qui/lib/image.min.js',
            'text': URL_OPT_DIR + 'bin/qui/qui/lib/text.min.js'
        }
    }
});

function finish() { // jshint ignore:line
    "use strict";

    window.parent.QUIQQER_REFRESH = true;
    window.parent.location = URL_SYS_DIR;
}

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
        if (!document.querySelector('.wizard-steps')) {
            return;
        }

        currentStep++;

        if (typeof window.STEPS[currentStep] === 'undefined') {
            currentStep = 0;
        }

        stepData = window.STEPS[currentStep];
        //console.log(stepData);

        require([
            URL_OPT_DIR + 'bin/quiqqer-asset/animejs/animejs/lib/anime.min.js'
        ], function (anime) {
            anime({
                targets: StepNode,
                duration: 500,
                opacity: 0,
                left: -20,
                easing: 'easeOutSine',
                complete: function () {
                    StepNode.innerHTML = '';
                    StepNode.style.left = '20px';
                    StepNode.style.display = 'flex';
                    StepNode.style.alignItems = 'center';
                    StepNode.style.position = 'relative';
                    StepNode.style.maxWidth = '55%';

                    const Text = document.createElement('div');
                    Text.innerHTML = stepData.content;
                    Text.classList.add('wizard-steps-step');
                    StepNode.appendChild(Text);

                    if (typeof stepData.image !== 'undefined' && stepData.image !== '') {
                        StepNode.style.maxWidth = '100%';

                        const Preview = document.createElement('img');

                        Preview.src = stepData.image;
                        Preview.style.width = '45%';
                        Preview.id = 'IMAGE-PREVIEW';

                        StepNode.appendChild(Preview);

                        anime({
                            targets: Preview,
                            duration: 250,
                            opacity: 1,
                            left: 0,
                            easing: 'easeOutSine',
                            delay: 500
                        });
                    }

                    anime({
                        targets: StepNode,
                        duration: 500,
                        opacity: 1,
                        left: 0,
                        easing: 'easeOutSine',
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
            const Process = document.querySelector('.wizard-process');

            if (!Process) {
                return;
            }

            if (Process.style.display === 'none') {
                Process.style.opacity = '0';
                Process.style.display = 'inline';

                anime({
                    targets: Process,
                    duration: 500,
                    opacity: 1,
                    easing: 'easeOutSine'
                });
            } else {
                anime({
                    targets: Process,
                    duration: 500,
                    opacity: 0,
                    easing: 'easeOutSine',
                    complete: function () {
                        Process.style.display = 'none';
                    }
                });
            }
        });
    });
})();
