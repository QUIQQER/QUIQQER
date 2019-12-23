/**
 * Package manager
 *
 * @module classes/packages/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 * @require Locale
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

            this.$packages  = {};
            this.$installed = {};
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

                case 'artifact':
                    return '<span class="fa fa-archive"></span>';

                case 'npm':
                    return '<svg viewBox="0 0 18 7" height="160">' +
                        '<path fill="#CB3837" d="M0,0v6h5v1h4v-1h9v-6"></path>' +
                        '<path fill="#FFF" d="M1,1v4h2v-3h1v3h1v-4h1v5h2v-4h1v2h-1v1h2v-4h1v4h2v-3h1v3h1v-3h1v3h1v-4"></path>' +
                        '</svg>';

                case 'bower':
                    return '<svg version="1.1" height="160" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"' +
                        'width="462.529px" height="406.613px" viewBox="0 0 462.529 406.613" enable-background="new 0 0 462.529 406.613"' +
                        'xml:space="preserve">' +
                        '<g>' +
                        '<path id="outline" fill="#543729" d="M453.689,198.606c-23.79-22.859-142.749-37.129-180.285-41.283' +
                        'c1.818-4.294,3.366-8.735,4.644-13.308c5.117-2.241,10.641-4.326,16.352-6.067c0.696,2.053,3.974,9.919,5.842,13.652' +
                        'c75.479,2.082,79.354-56.091,82.424-72.028c3.002-15.583,2.849-30.64,28.739-58.166c-38.571-11.24-94.039,17.421-112.618,60.08' +
                        'c-6.982-2.616-13.979-4.548-20.9-5.743C272.927,55.737,247.101,0,179.329,0C93.522,0,0,70.789,0,190.621' +
                        'c0,100.73,68.772,189.007,107.628,189.007c16.969,0,31.566-12.707,34.993-24.098c2.873,7.81,11.688,32.09,14.583,38.271' +
                        'c4.279,9.14,24.068,17.049,32.728,7.564c11.135,6.186,31.566,9.912,42.702-6.585c21.445,4.536,40.404-8.252,40.818-23.511' +
                        'c10.523-0.562,15.685-15.337,13.387-27.104c-1.694-8.663-19.789-39.748-26.847-50.478c13.972,11.365,49.363,14.582,53.661,0.007' +
                        'c22.527,17.682,57.633,8.401,60.417-5.979c27.372,7.112,58.767-8.508,53.612-27.426' +
                        'C471.655,257.248,466.027,210.462,453.689,198.606z"/>' +
                        '<g id="leaf">' +
                        '<path fill="#00ACEE" d="M331.252,98.825c9.471-18.791,21.372-39.309,36.404-52.003c-16.545,6.668-32.88,26.601-42.538,47.906' +
                        'c-4.923-3.129-9.921-5.92-14.961-8.361c13.473-28.758,44.779-52.775,79.28-54.651c-23.109,20.958-14.906,64.518-33.906,87.578' +
                        'C350.094,113.833,337.61,103.294,331.252,98.825z M316.314,129.424c0.01-0.719,0.279-6.266,0.784-8.798' +
                        'c-1.325-0.312-9.561-1.923-13.855-1.822c-0.313,5.393,2.266,14.568,4.815,20.091c17.555-0.368,30.235-5.625,37.698-10.458' +
                        'c-6.354-2.962-17.196-5.595-25.44-7.17C319.396,123.17,317.132,128.019,316.314,129.424z"/>' +
                        '</g>' +
                        '<path id="wingtip" fill="#2BAF2B" d="M251.083,278.09c0.004,0.025,0.015,0.059,0.018,0.084c-2.225-4.788-4.588-10.604-7.41-18.206' +
                        'c10.988,15.994,45.426,7.745,43.62-6.587c16.856,12.683,51.553-2.113,43.665-19.897c16.885,7.868,36.157-7.963,31.835-14.861' +
                        'c28.787,5.552,56.374,11.086,65.034,13.302c-5.753,9.38-18.855,16.004-38.605,11.401c10.672,14.538-10.048,31.979-38.908,22.373' +
                        'c6.353,14.272-19.343,27.121-48.548,12.245C302.155,292.222,265.553,293.866,251.083,278.09z M308.188,205.923' +
                        'c33.411,2.565,88.663,7.547,122.869,12.334c-2.161-11.132-8.064-14.312-26.633-19.3' +
                        'C384.453,201.087,333.786,206.064,308.188,205.923z"/>' +
                        '<path id="body" fill="#FFCC2F" d="M287.311,253.381c16.856,12.683,51.553-2.113,43.665-19.897' +
                        'c16.885,7.868,36.157-7.963,31.835-14.861c-34.034-6.562-69.747-13.148-77.848-14.299c4.914,0.261,13.059,0.819,23.225,1.6' +
                        'c25.599,0.141,76.266-4.836,96.236-6.966c-32.336-8.199-98.356-20.164-143.95-23.112c-2.113,3.088-5.997,8.325-12.762,13.89' +
                        'c-19.948,42.207-56.091,70.262-96.081,70.262c-11.654,0-24.693-1.966-39.308-6.638c-9.114,9.764-47.973,17.163-79.503,1.687' +
                        'c25.01,58.562,83.01,97.654,147.211,97.654c54.07,0,78.046-55.214,72.799-69.823c-1.273-3.547-6.318-15.308-9.141-22.909' +
                        'C254.679,275.962,289.116,267.713,287.311,253.381z"/>' +
                        '<path id="beak" fill="#CECECE" d="M254.543,142.258c4.774-2.597,21.272-12.597,36.993-16.355c-0.248-1.741-0.435-3.497-0.551-5.263' +
                        'c-10.314,2.47-29.759,10.804-40.902-0.681c23.509,7.094,35.247-6.321,52.526-6.321c10.296,0,24.988,2.876,36.569,7.423' +
                        'c-9.315-8.605-39.861-34.575-77.681-34.665C253.053,96.633,243.926,118.808,254.543,142.258z"/>' +
                        '<path id="head" fill="#EF5734" d="M112.323,253.36c14.615,4.672,27.654,6.638,39.308,6.638c39.99,0,76.132-28.056,96.081-70.262' +
                        'c-14.754,12.316-40.396,22.854-80.441,22.854c35.669-8.088,66.375-25.863,81.995-51.845c-10.98-17.476-22.889-56.138,7.269-86.702' +
                        'c-4.639-14.904-27.219-54.248-77.206-54.248c-87.236,0-159.533,72.997-159.533,170.825c0,23.158,4.675,44.877,13.025,64.426' +
                        'C64.35,270.523,103.209,263.124,112.323,253.36z"/>' +
                        '<path id="eye_rim" fill="#FFCC2F" d="M138.496,104.407c0,22.252,18.039,40.293,40.292,40.293' +
                        'c22.253,0,40.294-18.041,40.294-40.293c0-22.253-18.041-40.293-40.294-40.293C156.535,64.114,138.496,82.154,138.496,104.407z"/>' +
                        '<path id="eye" fill="#543729" d="M154.664,104.407c0,13.322,10.802,24.123,24.123,24.123c13.324,0,24.124-10.801,24.124-24.123' +
                        'c0-13.323-10.799-24.124-24.124-24.124C165.466,80.283,154.664,91.084,154.664,104.407z"/>' +
                        '<ellipse id="pupil_highlight" fill="#FFFFFF" cx="178.787" cy="93.703" rx="14.057" ry="8.74"/>' +
                        '</g>' +
                        '</svg>';

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
         * @return {Promise}
         */
        update: function (pkg) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_update', resolve, {
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
         * Return all not installed packages in the local server
         *
         * @return {Promise}
         */
        getNotInstalledPackages: function () {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_upload_getNotInstalledPackages', resolve, {
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
         * install a package
         *
         * @param {String|Array} packages - name of the package
         * @returns {Promise}
         */
        install: function (packages) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_install', resolve, {
                    packages : JSON.encode(packages),
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         *
         * @param {String} packageName
         * @param {String|Boolean} [packageVersion]
         * @param {Array} [server]
         */
        installPackage: function (packageName, packageVersion, server) {
            packageVersion = packageVersion || false;
            server         = server || false;

            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_system_packages_installPackage', resolve, {
                    packageName   : packageName,
                    packageVersion: packageVersion,
                    server        : JSON.encode(server),
                    showError     : false,
                    onError       : reject
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
        checkUpdates: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_system_update_check', resolve, {
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Return the date of the last update check
         *
         * @param {Boolean} [formatted] - Should the date be formatted
         * @returns {Promise}
         */
        getLastUpdateCheck: function (formatted) {
            formatted = formatted || false;

            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_system_getLastUpdateCheck', resolve, {
                    formatted: formatted ? 1 : 0,
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Returns the packages which are updatable
         *
         * @param {Boolean|Number} [force] - default = false; if false and a cached outdated list exists,
         *                                             this list would be used
         * @return {Promise}
         */
        getOutdated: function (force) {
            return new Promise(function (resolve, reject) {
                if (typeof force === 'undefined') {
                    force = 0;
                }

                Ajax.get('ajax_system_update_getOutdated', resolve, {
                    force    : force ? 1 : 0,
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
         * Return the lock data of a package
         *
         * @param {String} pkg - Package name
         * @returns {Promise}
         */
        getPackageLock: function (pkg) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_system_packages_getLock', resolve, {
                    showError: false,
                    onError  : reject,
                    package  : pkg
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
         * Is the wanted package installed?
         *
         * @param {String} pkg - name of the package
         *
         * @return {Promise}
         */
        isInstalled: function (pkg) {
            if (typeof this.$installed[pkg] !== 'undefined') {
                return Promise.resolve(this.$installed[pkg]);
            }

            if (typeof this.$packages[pkg] !== 'undefined') {
                this.$installed[pkg] = true;

                return Promise.resolve(!!this.$packages[pkg]);
            }

            var self = this;

            return this.getPackage(pkg).then(function () {
                self.$installed[pkg] = true;

                return true;
            }).catch(function () {
                self.$installed[pkg] = false;

                return false;
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
