/**
 * Verify cookie storage and session continuity before authentication.
 */
define('utils/LoginCheck', ['Ajax'], function (Ajax) {
    'use strict';

    let pending = null;

    const failure = (reason) => {
        const error = new Error(reason);
        error.loginCheck = reason;
        return error;
    };

    const checkCookie = () => {
        const random = new Uint32Array(2);
        crypto.getRandomValues(random);
        const name = 'quiqqer_login_check_' + Array.from(random).join('_');
        const attributes = '; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');

        try {
            document.cookie = name + '=1; Max-Age=60' + attributes;

            if (!document.cookie.split(';').some((cookie) => cookie.trim() === name + '=1')) {
                throw failure('cookies');
            }
        } catch (error) {
            throw failure('cookies');
        } finally {
            try {
                document.cookie = name + '=; Max-Age=0' + attributes;
            } catch (error) {
                // The short expiry also cleans up a cookie when deletion is blocked.
            }
        }
    };

    const request = (token) => new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(failure('connection')), 12000);

        const finish = (callback, value) => {
            clearTimeout(timeout);
            callback(value);
        };

        const params = {
            token: token ?? 'start',
            bundle: false,
            showError: false,
            showLogin: false,
            onError: () => finish(reject, failure('connection'))
        };

        try {
            // Diagnose session continuity without requiring a valid session-bound POST/CSRF token first.
            Ajax.get('ajax_users_checkSession', (result) => finish(resolve, result), params);
        } catch (error) {
            finish(reject, failure('connection'));
        }
    });

    return {
        check: function () {
            if (pending) {
                return pending;
            }

            pending = Promise.resolve().then(checkCookie).then(() => request()).then((token) => {
                if (typeof token !== 'string' || !/^[a-f0-9]{64}$/.test(token)) {
                    throw failure('connection');
                }

                return request(token);
            }).then((valid) => {
                if (valid !== true) {
                    throw failure('session');
                }
            }).finally(() => {
                pending = null;
            });

            return pending;
        }
    };
});
