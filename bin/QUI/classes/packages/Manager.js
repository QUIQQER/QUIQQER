/**
 * Package manager
 *
 * @module classes/packages/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 */
define('classes/packages/Manager', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'Locale'

], function (QUI, QDOM, Ajax, QUILocale) {
    "use strict";

    var setupIsRunning = false;

    return new Class({

        Extends: QDOM,
        Type   : 'classes/packages/Manager',

        initialize: function (options) {
            this.parent(options);

            this.$packages = {};
        },

        /**
         * Return the server type icon
         *
         * @param {String} type
         * @return {String}
         */
        getServerTypeIcon: function (type) {
            switch (type) {
                case 'composer':
                    return '<img src="' + URL_BIN_DIR + 'images/logo-composer.png" />';

                case 'npm':
                    return '<span class="fa fa-server"></span>';

                case 'artifact':
                    return '<span class="fa fa-archive"></span>';

                default:
                case 'vcs':
                    return '<span class="fa fa-server"></span>';
            }
        },

        /**
         * Execute a system or plugin setup
         *
         * @param {String} [pkg] - (optional), Package name, if no package name given, complete setup are executed
         * @param {Function} [callback] - (optional), callback function
         * @return {Promise}
         */
        setup: function (pkg, callback) {
            if (setupIsRunning) {
                var message = QUILocale.get(
                    'quiqqer/quiqqer',
                    'message.setup.is.currently.running'
                );

                if (typeOf(callback) === 'function') {
                    callback(message);
                }

                return Promise.reject(message);
            }

            setupIsRunning = true;

            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_setup', function () {
                    setupIsRunning = false;

                    if (typeOf(callback) === 'function') {
                        callback();
                    }

                    resolve();
                }, {
                    'package': pkg || false,
                    showError: false,
                    onError  : function (Err) {
                        setupIsRunning = false;
                        reject(Err);
                    }
                });
            });
        },

        /**
         * Execute a system or plugin update
         *
         * @param {String} [pkg] - (optional), Package name, if no package name given, complete update are executed
         * @param {Function} [callback] - (optional), callback function
         * @return {Promise}
         */
        update: function (pkg, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_update', function (result) {
                    if (typeOf(callback) === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    'package': pkg || false,
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Execute a system or plugin update with an internal local server
         *
         * @param {Function} [callback] - optional
         * @return {Promise}
         */
        updateWithLocalServer: function (callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_updateWithLocalServer', function (result) {
                    if (typeOf(callback) === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Search for packages
         *
         * @param {String} search - search string
         * @return {Promise}
         */
        search: function (search) {
            if (typeof search === 'undefined' || search === '') {
                return Promise.reject('Undefined search string');
            }

            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_search', resolve, {
                    'search' : search,
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Activate the local repository
         *
         * @param {Function} [callback] - optional
         * @returns {Promise}
         */
        activateLocalServer: function (callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_activateLocalServer', function () {
                    if (typeOf(callback) === 'function') {
                        callback();
                    }

                    resolve();
                }, {
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Add a server to the update server list
         *
         * @param {String} server - server name
         * @param {Object} params - server params
         */
        addServer: function (server, params) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_server_add', resolve, {
                    server : server,
                    params : JSON.encode(params),
                    onError: reject
                });
            });
        },

        /**
         * Edit a server from the server list
         *
         * @param {String} server - server name
         * @param {Object} params - server params
         */
        editServer: function (server, params) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_server_edit', resolve, {
                    server : server,
                    params : JSON.encode(params),
                    onError: reject
                });
            });
        },

        /**
         * Remove a server from the server list
         *
         * @param {String} server - server name
         */
        removeServer: function (server) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_server_remove', resolve, {
                    server : server,
                    onError: reject
                });
            });
        },

        /**
         * Activate the local repository
         *
         * @param {String} server - server address
         * @param {Boolean} status - new status
         * @returns {Promise}
         */
        setServerStatus: function (server, status) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_server_status', resolve, {
                    showError: false,
                    server   : server,
                    status   : status ? 1 : 0,
                    onError  : reject
                });
            });
        },

        /**
         * Return the complete server list
         *
         * @returns {Promise}
         */
        getServer: function (server) {
            return this.getServerList().then(function (result) {
                var data = result.filter(function (entry) {
                    return entry.server == server;
                });

                return data.length ? data[0] : false;
            });
        },

        /**
         * Return the complete server list
         *
         * @returns {Promise}
         */
        getServerList: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_system_packages_server_list', resolve, {
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * install a local package
         *
         * @param {String|Array} packages - name of the package
         * @param {Function} [callback] - optional
         * @returns {Promise}
         */
        installLocalPackages: function (packages, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_installLocalePackage', function () {
                    if (typeOf(callback) === 'function') {
                        callback();
                    }

                    resolve();
                }, {
                    packages : JSON.encode(packages),
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Read the locale repository and search installable packages
         *
         * @param {Function} [callback] - optional
         * @return {Promise}
         */
        readLocalRepository: function (callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_readLocalRepository', function (result) {
                    if (typeOf(callback) === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Check, if updates are available
         *
         * @return {Promise}
         */
        checkUpdate: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_system_update_check', resolve, {
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Return the data of one package
         *
         * @param {String} pkg          - Package name
         * @param {Function} [callback] - optional, callback function
         * @return {Promise}
         */
        getPackage: function (pkg, callback) {
            var self = this;

            return new Promise(function (resolve, reject) {
                if (self.$packages[pkg]) {
                    if (typeOf(callback) === 'function') {
                        callback(self.$packages[pkg]);
                    }

                    resolve(self.$packages[pkg]);
                    return;
                }

                Ajax.get('ajax_system_packages_get', function (result) {
                    self.$packages[pkg] = result;

                    if (typeOf(callback) === 'function') {
                        callback(result);
                    }

                    resolve(result);

                }, {
                    'package': pkg,
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Return all installed packages
         *
         * @returns {Promise}
         */
        getInstalledPackages: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_system_packages_list', resolve, {
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Change / Set the Version for a package
         *
         * @param {String} pkg          - Name of the package
         * @param {String} version      - Version of the package
         * @param {Function} [callback] - callback function
         * @return {Promise}
         */
        setVersion: function (pkg, version, callback) {
            var self = this;

            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_setVersion', function (result) {
                    self.update(pkg).done(function () {
                        if (typeOf(callback) === 'function') {
                            callback(result);
                        }

                        resolve(result);

                    }, reject);
                }, {
                    packages : JSON.encode(pkg),
                    version  : version,
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Return the package config
         *
         * @param {String} pkg
         * @returns {Promise}
         */
        getConfig: function (pkg) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_getConfig', resolve, {
                    'package': pkg,
                    onError  : reject
                });
            });
        }
    });
});
