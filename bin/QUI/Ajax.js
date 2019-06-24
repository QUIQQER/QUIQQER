/**
 * Ajax request for QUIQQER
 * Ajax Manager, collect, exec multiple requests
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module Ajax
 *
 * @require qui/QUI
 * @require qui/classes/request/Ajax
 * @require qui/utils/Object
 * @require Locale
 *
 * @example

 require(['Ajax'], function(Ajax)
 {
     Ajax.post('ajax_project_getlist', function(result, Request)
     {
         console.info(result);
     });
 });

 */
define('Ajax', [

    'qui/QUI',
    'qui/classes/request/Ajax',
    'qui/utils/Object',
    'Locale'

], function (QUI, QUIAjax, Utils, Locale) {
    "use strict";

    // load message handling
    QUI.getMessageHandler().catch(function () {
        // nothing
    });

    var apiPoint = '/ajax.php';

    if (typeof QUIQQER !== 'undefined' && "ajax" in QUIQQER) {
        apiPoint = QUIQQER.ajax;
    } else if (typeof URL_SYS_DIR !== 'undefined') {
        apiPoint = URL_SYS_DIR + 'ajax.php';
    }

    var TRY_MAX   = 3;
    var TRY_DELAY = 1000;

    if (typeof QUIQQER_CONFIG !== 'undefined' &&
        typeof QUIQQER_CONFIG.globals !== 'undefined' &&
        (QUIQQER_CONFIG.globals.debug_mode || QUIQQER_CONFIG.globals.development)) {
        TRY_MAX = 0;
    }

    return {
        $globalJSF : {}, // global javascript callback functions
        $onprogress: {},
        $url       : apiPoint,
        $on401     : false,

        /**
         * Send a Request async
         *
         * @method Ajax#request
         *
         * @param {String} call         - PHP function
         * @param {String} method       - Send Method -> post or get
         * @param {Function} [callback] - Callback function if the request is finish
         * @param {Object} [params]     - PHP parameter (optional)
         *
         * @return {Ajax}
         */
        request: function (call, method, callback, params) {
            // if sync, the browser freeze
            var options;
            var self = this,
                id   = String.uniqueID();

            method   = method || 'post'; // is post, put, get or delete
            callback = callback || function () {
            };

            params = Utils.combine(params, {
                '_rf'      : call,
                '_FRONTEND': window.QUIQQER_FRONTEND || 0
            });

            if (typeof params.lang === 'undefined') {
                params.lang = Locale.getCurrent();
            }

            if (typeof params.project === 'undefined' && typeof window.QUIQQER_PROJECT !== 'undefined') {
                params.project = JSON.encode(window.QUIQQER_PROJECT);
            }


            // combine all params, so, they are available in the Request Object
            options = Utils.combine(params, {
                callback : callback,
                method   : method,
                url      : this.$url,
                async    : true,
                showError: typeof params.showError !== 'undefined' ? params.showError : true,
                showLogin: typeof params.showLogin !== 'undefined' ? params.showLogin : true,
                events   : {
                    onSuccess: function () {
                        var args    = arguments;
                        var Request = args[args.length - 1];

                        if (Request.getAttribute('logout')) {
                            return;
                        }

                        if (Request.getAttribute('hasError')) {
                            return;
                        }

                        if (this in self.$onprogress &&
                            "$result" in self.$onprogress[this] &&
                            self.$onprogress[this].$result.jsCallbacks
                        ) {
                            self.$triggerGlobalJavaScriptCallback(
                                self.$onprogress[this].$result.jsCallbacks,
                                self.$onprogress[this].$result
                            );
                        }

                        QUI.fireEvent('ajaxResult', [self.$onprogress[this].$result]);

                        // maintenance?
                        if (this in self.$onprogress &&
                            "$result" in self.$onprogress[this] &&
                            "maintenance" in self.$onprogress[this].$result &&
                            self.$onprogress[this].$result.maintenance
                        ) {
                            self.showMaintenanceMessage();
                        }

                        callback.apply(this, arguments);
                    }.bind(id),

                    onCancel: function (Request) {
                        if (Request.getAttribute('onCancel')) {
                            return Request.getAttribute('onCancel')(Request);
                        }
                    },

                    onError: function (Exception, Request) {
                        var Response     = null,
                            networkError = true,
                            tries        = Request.getAttribute('REQUEST_TRIES') || 0;

                        if (typeof self.$onprogress[this] !== 'undefined') {
                            Response = self.$onprogress[this];
                        }


                        // maintenance?
                        if (Response &&
                            "$result" in Response && Response.$result &&
                            "maintenance" in Response.$result && Response.$result.maintenance
                        ) {
                            self.showMaintenanceMessage();
                        }


                        if (Response &&
                            "$result" in Response && Response.$result &&
                            Response.$result.jsCallbacks
                        ) {
                            self.$triggerGlobalJavaScriptCallback(
                                Response.$result.jsCallbacks,
                                Response.$result
                            );
                        }

                        Request.setAttribute('hasError', true);

                        if (Exception.getMessage() === '') {
                            Exception.setAttribute('message', Locale.get('quiqqer/quiqqer', 'exception.network.error'));
                            Exception.setAttribute('code', 503);
                            networkError = true;
                        }

                        if (Request.getAttribute('showError') || networkError) {
                            if (tries === TRY_MAX) {
                                QUI.getMessageHandler().then(function (MessageHandler) {
                                    MessageHandler.addException(Exception);
                                });
                            }
                        }

                        if (Exception.getCode() === 401 &&
                            Exception.getAttribute('type') === 'QUI\\Users\\Exception' &&
                            Request.getAttribute('showLogin')
                        ) {
                            Request.setAttribute('logout', true);

                            if (self.$on401) {
                                if (typeof self.$on401 === 'function') {
                                    self.$on401(Exception);
                                }

                                return;
                            }

                            require(['controls/users/LoginWindow'], function (Login) {
                                new Login({
                                    message: Exception.getMessage(),
                                    events : {
                                        onSuccess: function () {
                                            self.request(call, method, callback, params);
                                        }
                                    }
                                }).open();
                            });

                            return;
                        }

                        if ("QUIQQER" in window &&
                            "inAdministration" in QUIQQER &&
                            QUIQQER.inAdministration &&
                            Exception.getCode() === 440
                        ) {
                            Request.setAttribute('logout', true);

                            require(['controls/users/LoginWindow'], function (Login) {
                                new Login({
                                    events: {
                                        onSuccess: function () {
                                            self.request(call, method, callback, params);
                                        }
                                    }
                                }).open();
                            });

                            return;
                        }

                        switch (Exception.getCode()) {
                            // if server error, then test again
                            case '':
                            case 0:
                            case 503:
                            case 504:
                            case 507:
                            case 509:
                            case 510:
                                break;

                            default:
                                QUI.getMessageHandler().then(function (MessageHandler) {
                                    MessageHandler.addException(Exception);
                                });

                                if (Request.getAttribute('onError')) {
                                    return Request.getAttribute('onError')(Exception, Request);
                                }

                                QUI.triggerError(Exception, Request);
                                return;
                        }

                        // another try
                        if (tries < TRY_MAX) {
                            tries++;
                            Request.setAttribute('REQUEST_TRIES', tries);

                            (function () {
                                options._rf = call;
                                self.$onprogress[id].send(options);
                            }).delay(TRY_DELAY * tries);

                            return;
                        }

                        if (Request.getAttribute('onError')) {
                            return Request.getAttribute('onError')(Exception, Request);
                        }

                        QUI.triggerError(Exception, Request);
                    }.bind(id)
                }
            });

            this.$onprogress[id] = new QUIAjax(options);
            this.$onprogress[id].send(options);

            return this.$onprogress[id];
        },

        /**
         * Register a global callback javascript function
         * This functions are to be executed after every request
         * the execution is controlled via php
         * Ajax->triggerGlobalJavaScriptCallback();
         *
         * @param {String} fn - Function name
         * @param {Function} callback - Callback function
         */
        registerGlobalJavaScriptCallback: function (fn, callback) {
            if (typeOf(callback) === 'function') {
                this.$globalJSF[fn] = callback;
            }
        },

        /**
         * Excute globale functions
         *
         * @param {Array} functionList - list of functions
         * @param response - Request response
         */
        $triggerGlobalJavaScriptCallback: function (functionList, response) {
            if (typeOf(functionList) !== 'object') {
                return;
            }

            if (!Object.getLength(functionList)) {
                return;
            }

            for (var f in functionList) {
                if (f in this.$globalJSF && this.$globalJSF.hasOwnProperty(f)) {
                    this.$globalJSF[f](response, functionList[f]);
                }
            }
        },

        /**
         * show a maintenance message
         */
        showMaintenanceMessage: function () {
            QUI.getMessageHandler(function (MH) {
                MH.addInformation(
                    Locale.get('quiqqer/quiqqer', 'message.maintenance')
                );
            });
        },

        /**
         * Send a Request sync
         *
         * @method Ajax#syncRequest
         *
         * @param {String} call     - PHP function
         * @param {String} method   - Send Method -> post or get
         * @param {Object} [params] - PHP parameter (optional)
         *
         * @return {Ajax}
         */
        syncRequest: function (call, method, params) {
            var id = String.uniqueID();

            method = method || 'post'; // is post, put, get or delete

            params = Utils.combine(params, {
                '_rf': call
            });

            this.$onprogress[id] = new QUIAjax(
                // combine all params, so, they are available in the Request Object
                Utils.combine(params, {
                    method : method,
                    url    : this.$url,
                    async  : false,
                    timeout: 5000,
                    events : {
                        onCancel: function (Request) {
                            if (Request.getAttribute('onCancel')) {
                                return Request.getAttribute('onCancel')(Request);
                            }
                        },

                        onError: function (Exception, Request) {
                            QUI.getMessageHandler(function (MessageHandler) {
                                MessageHandler.addException(Exception);
                            });


                            if (Request.getAttribute('onError')) {
                                return Request.getAttribute('onError')(Exception, Request);
                            }

                            QUI.triggerError(Exception, Request);
                        }
                    }
                })
            );

            this.$onprogress[id].send(params);

            return this.$onprogress[id];
        },

        /**
         * Send a POST Request
         *
         * @method Ajax#post
         *
         * @param {String|Array} call - PHP function
         * @param {Function} callback - Callback function if the Request is finish
         * @param {Object} [params]   - PHP parameter (optional)
         *
         * @return {Ajax}
         */
        post: function (call, callback, params) {
            return this.request(call, 'post', callback, params);
        },

        /**
         * Send a GET Request
         *
         * @method Ajax#get
         *
         * @param {String|Array} call - PHP function
         * @param {Function} callback - Callback function if the Request is finish
         * @param {Object} [params]   - PHP parameter (optional)
         *
         * @return {Ajax}
         */
        get: function (call, callback, params) {
            // chrome cache get request, so we must extend the request
            if (typeof params === 'undefined') {
                params = {};
            }

            params.preventCache = String.uniqueID();

            return this.request(call, 'get', callback, params);
        },

        /**
         * Parse params to a ajax request string
         *
         * @method Ajax#parseParams
         *
         * @param {String|Array} call - PHP function
         * @param {Object} params     - PHP parameter (optional)
         */
        parseParams: function (call, params) {
            params = Utils.combine(params, {
                '_rf': call
            });

            if (typeof this.$AjaxHelper === 'undefined') {
                this.$AjaxHelper = new QUIAjax();
            }

            return Object.toQueryString(
                this.$AjaxHelper.parseParams(params)
            );
        },

        /**
         *
         */
        put: function (call, callback, params) {
            // chrome cache get request, so we must extend the request
            if (typeof params === 'undefined') {
                params = {};
            }

            params.preventCache = String.uniqueID();

            return this.request(call, 'put', callback, params);
        },

        /**
         *
         */
        del: function (call, callback, params) {
            // chrome cache get request, so we must extend the request
            if (typeof params === 'undefined') {
                params = {};
            }

            params.preventCache = String.uniqueID();

            return this.request(call, 'delete', callback, params);
        }
    };
});
