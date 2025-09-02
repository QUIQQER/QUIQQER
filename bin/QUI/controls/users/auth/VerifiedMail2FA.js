/**
 * QUIQQER Authentication via email code
 */
define('controls/users/auth/VerifiedMail2FA', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Ajax'

], function (QUI, QUIControl, QUILocale, QUIAjax) {
    'use strict';

    return new Class({

        Extends: QUIControl,
        Type: 'controls/users/auth/VerifiedMail2FA',

        Binds: [
            '$onImport'
        ],

        /**
         * construct
         * @param {Object} options
         */
        initialize: function (options) {
            this.parent(options);

            this.Loader = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            const node = this.getElm();
            const inputs = node.querySelectorAll('input');

            // send mail
            QUIAjax.post('ajax_users_authenticator_sendVerifiedMail2faMail', () => {
            }, {
                'package': 'quiqqer/code',
                onError: (err) => {
                    QUI.getMessageHandler().then((mh) => {
                        mh.addError(err.getMessage());

                        // destroy session
                        require(['utils/Session'], (Session) => {
                            Session.remove('inAuthentication');
                            Session.remove('auth-globals');

                            const loginNode = this.getElm().closest('[data-qui="controls/users/Login"]');

                            if (loginNode) {
                                QUI.Controls.getById(loginNode.get('data-quiid')).refresh();
                            }
                        });
                    });
                }
            });

            // Focus handling for code inputs
            inputs.forEach((input, idx) => {
                input.addEventListener('input', (e) => {
                    const val = input.value;
                    // If user pasted more than one character, handle paste
                    if (val.length > 1) {
                        // Paste scenario: distribute chars
                        for (let i = 0; i < inputs.length; i++) {
                            inputs[i].value = val[i] || '';
                        }
                        if ([...inputs].every(inp => inp.value.length === 1)) {
                            input.form && input.form.querySelector('[type="submit"]').click();
                        } else {
                            // Focus next empty
                            for (let i = 0; i < inputs.length; i++) {
                                if (!inputs[i].value) {
                                    inputs[i].focus();
                                    break;
                                }
                            }
                        }
                        return;
                    }
                    // Normal input: jump to next if filled
                    if (val.length === 1 && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                    }
                    // Submit if all filled
                    if ([...inputs].every(inp => inp.value.length === 1)) {
                        input.form && input.form.querySelector('[type="submit"]').click();
                    }
                });

                input.addEventListener('keydown', (e) => {
                    // Backspace or left arrow: go to previous if empty
                    if ((e.key === 'Backspace' || e.key === 'ArrowLeft') && !input.value && idx > 0) {
                        inputs[idx - 1].focus();
                        e.preventDefault();
                    }
                    // Ctrl+Backspace: clear current and go to previous
                    if ((e.key === 'Backspace' && e.ctrlKey) && idx > 0) {
                        input.value = '';
                        inputs[idx - 1].focus();
                        e.preventDefault();
                    }
                    // Right arrow: go to next
                    if (e.key === 'ArrowRight' && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                        e.preventDefault();
                    }
                    // Ctrl+V (paste) is handled by input event
                });

                // Paste event for mouse paste
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    for (let i = 0; i < inputs.length; i++) {
                        inputs[i].value = paste[i] || '';
                    }
                    if ([...inputs].every(inp => inp.value.length === 1)) {
                        input.form && input.form.querySelector('[type="submit"]').click();
                    } else {
                        // Focus next empty
                        for (let i = 0; i < inputs.length; i++) {
                            if (!inputs[i].value) {
                                inputs[i].focus();
                                break;
                            }
                        }
                    }
                });
            });
        }
    });
});
