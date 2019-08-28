/**
 * Ajax Bundler
 *
 * Send ajax request, but bundle the requests
 * the bundler sends the request between a timeframe
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package quiqqer/quiqqer
 */

define('classes/request/Bundler', [

    'qui/QUI',
    'qui/classes/DOM'

], function (QUI, QUIDom) {
    "use strict";

    return new Class({

        Extends: QUIDom,
        Type   : 'classes/request/Bundler',

        initialize: function (options) {
            this.setAttributes({
                timeframe     : 100,
                requestTimeout: 10000
            });

            this.parent(options);

            this.$stack = {
                get : [],
                post: [],
                put : [],
                del : []
            };

            this.$delay = false;
        },

        /**
         * API
         */

        /**
         * send a get request
         *
         * @param {String} request - Name of the PHP Function
         * @param {Object} params - Function parameter
         *
         * @return Promise
         */
        get: function (request, params) {
            return this.$addCall('get', request, params);
        },

        /**
         * send a get request
         *
         * @param {String} request - Name of the PHP Function
         * @param {Object} params - Function parameter
         *
         * @return Promise
         */
        post: function (request, params) {
            return this.$addCall('post', request, params);
        },

        /**
         * send a get request
         *
         * @param {String} request - Name of the PHP Function
         * @param {Object} params - Function parameter
         *
         * @return Promise
         */
        put: function (request, params) {
            return this.$addCall('put', request, params);
        },

        /**
         * send a get request
         *
         * @param {String} request - Name of the PHP Function
         * @param {Object} params - Function parameter
         *
         * @return Promise
         */
        del: function (request, params) {
            return this.$addCall('del', request, params);
        },

        /**
         * Methods
         */

        /**
         * add request call
         *
         * @param {String} method - get, post, put, del
         * @param {String} request - php function name
         * @param {Object} params - parameter
         * @returns {Promise|Boolean}
         */
        $addCall: function (method, request, params) {
            if (!(method in this.$stack)) {
                return false;
            }

            return new Promise(function (resolve, reject) {
                this.$stack[method].push({
                    request: request,
                    params : params,
                    resolve: resolve,
                    reject : reject,
                    rid    : String.uniqueID()
                });

                this.$start();
            }.bind(this));
        },

        /**
         * start the delay
         */
        $start: function () {
            if (this.$delay) {
                return;
            }

            this.$delay = (function () {
                this.execute();
                this.$delay = false;
            }).delay(this.getAttribute('timeframe'), this);
        },

        /**
         * Execute the stack and set the calls back
         */
        execute: function () {
            var Get  = this.$stack.get;
            var Post = this.$stack.post;
            var Put  = this.$stack.put;
            var Del  = this.$stack.del;

            this.$stack.get  = [];
            this.$stack.post = [];
            this.$stack.put  = [];
            this.$stack.del  = [];

            if (Get.length) {
                this.$request('get', Get);
            }

            if (Post.length) {
                this.$request('post', Post);
            }

            if (Put.length) {
                this.$request('put', Put);
            }

            if (Del.length) {
                this.$request('delete', Del);
            }
        },

        /**
         * Sends request to the php bundler
         *
         * @param {String} method - get, post, put, delete
         * @param {Object} Params - request params
         */
        $request: function (method, Params) {
            var requestData = this.$parseStackForSend(Params);

            var R = new Request({
                url    : URL_DIR + 'ajaxBundler.php',
                method : method || 'post',
                async  : true,
                timeout: this.getAttribute('requestTimeout'),

                onProgress: function () {
                    this.fireEvent('requestProgress', [this]);
                }.bind(this),

                onComplete: function () {
                    this.fireEvent('requestComplete', [this]);
                }.bind(this),

                onSuccess: function (responseText) {
                    this.$parseResponse(responseText, Params, requestData);
                }.bind(this),

                onCancel: function () {
                    this.fireEvent('requestCancel', [this]);
                }.bind(this)
            });

            R.send(
                Object.toQueryString({
                    quiqqerBundle: requestData
                })
            );

            return R;
        },

        /**
         * Parse stack variables for send
         *
         * @param {Array} stack
         * @returns {Array}
         */
        $parseStackForSend: function (stack) {
            var i, k, p, len, stackEntry, stackParams, type_of;
            var result = [];

            for (i = 0, len = stack.length; i < len; i++) {
                stackEntry  = stack[i];
                stackParams = {};

                p = stackEntry.params;

                for (k in p) {
                    if (!p.hasOwnProperty(k)) {
                        continue;
                    }

                    if (typeof p[k] === 'undefined') {
                        continue;
                    }

                    type_of = typeOf(p[k]);

                    if (type_of !== 'string' &&
                        type_of !== 'number' &&
                        type_of !== 'array') {
                        continue;
                    }

                    stackParams[k] = JSON.encode(p[k]);
                }

                result.push({
                    request: stackEntry.request,
                    rid    : stackEntry.rid,
                    params : stackParams
                });
            }

            return result;
        },

        /**
         *
         * @param {String} responseText
         * @param {Object} Params
         * @param {Object} requestData
         */
        $parseResponse: function (responseText, Params, requestData) {
            var k, data, result, entryData, entryResult;

            try {
                result = JSON.decode(responseText);
            } catch (e) {
                console.error(e);
                return;
            }

            var getDataByRID = function (data, id) {
                for (var i = 0, len = data.length; i < len; i++) {
                    if (data[i].rid === id) {
                        return data[i];
                    }
                }

                return false;
            };

            for (k in result) {
                if (!result.hasOwnProperty(k)) {
                    continue;
                }

                data        = getDataByRID(requestData, k);
                entryData   = getDataByRID(Params, k);
                entryResult = result[k];

                entryData.resolve(entryResult);
            }
        }
    });
});
