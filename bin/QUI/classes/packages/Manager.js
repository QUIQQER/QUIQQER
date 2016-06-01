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
    'Ajax'

], function (QUI, QDOM, Ajax) {
    "use strict";

    return new Class({

        Extends: QDOM,
        Type   : 'classes/packages/Manager',

        initialize: function (options) {
            this.parent(options);

            this.$packages = {};
        },

        /**
         * Execute a system or plugin setup
         *
         * @param {String} [pkg] - (optional), Package name, if no package name given, complete setup are executed
         * @param {Function} [callback] - (optional), callback function
         * @return Promise
         */
        setup: function (pkg, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_setup', function () {
                    if (typeOf(callback) === 'function') {
                        callback();
                    }

                    resolve();

                }, {
                    'package': pkg || false,
                    showError: false,
                    onError  : function (Exception) {
                        reject(Exception);
                    }
                });
            });
        },

        /**
         * Execute a system or plugin update
         *
         * @param {String} [pkg] - (optional), Package name, if no package name given, complete update are executed
         * @param {Function} [callback] - (optional), callback function
         * @return Promise
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
                    onError  : function (Exception) {
                        reject(Exception);
                    }
                });
            });
        },

        /**
         * Execute a system or plugin update with an internal local server
         *
         * @param {Function} [callback] - optional
         * @return Promise
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
                    onError  : function (Exception) {
                        reject(Exception);
                    }
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
                    onError  : function (Exception) {
                        reject(Exception);
                    }
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
                    onError  : function () {
                        reject();
                    }
                });
            });
        },

        /**
         * Read the locale repository and search installable packages
         *
         * @param {Function} [callback] - optional
         * @return Promise
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
                    onError  : function (Exception) {
                        reject(Exception);
                    }
                });
            });
        },

        /**
         * Check, if updates are available
         *
         * @param {Function} [callback] - callback function
         * @return Promise
         */
        checkUpdate: function (callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_system_update_check', function (result) {
                    if (typeOf(callback) === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    showError: false,
                    onError  : function (Exception) {
                        reject(Exception);
                    }
                });
            });
        },

        /**
         * Return the data of one package
         *
         * @param {String} pkg          - Package name
         * @param {Function} [callback] - optional, callback function
         * @return Promise
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
                    onError  : function (Exception) {
                        reject(Exception);
                    }
                });
            });
        },

        /**
         * Change / Set the Version for a package
         *
         * @param {String} pkg          - Name of the package
         * @param {String} version      - Version of the package
         * @param {Function} [callback] - callback function
         * @return Promise
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
                    onError  : function (Exception) {
                        reject(Exception);
                    }
                });
            });
        },

        /**
         * Return the package config
         *
         * @param {String} pkg
         * @returns {*}
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
