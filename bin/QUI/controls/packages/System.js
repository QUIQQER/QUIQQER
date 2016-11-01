/**
 * @module controls/packages/System
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 * @requires qui/controls/buttons/Button
 * @requires qui/controls/windows/Confirm
 * @requires Packages
 * @requires Mustache
 * @requires Ajax
 * @requires Locale
 * @requires utils/Favicon
 * @requires package/quiqqer/translator/bin/Translator
 * @requires text!controls/packages/System.html
 * @requires css!controls/packages/Server.css
 *
 * @event onLoad
 * @event onShowLoader
 * @event onHideLoader
 */
define('controls/packages/System', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'Packages',
    'Mustache',
    'Ajax',
    'Locale',
    'utils/Favicon',
    'package/quiqqer/translator/bin/Translator',

    'text!controls/packages/System.html',
    'css!controls/packages/System.css'

], function (QUI, QUIControl, QUIButton, QUIConfirm, Packages, Mustache,
             QUIAjax, QUILocale, FaviconUtils, Translator, template) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/System',

        Binds: [
            '$onInject',
            'checkUpdates',
            'executeCompleteSetup',
            'executeSystemUpdate',
            'viewTile',
            'viewList',
            '$onPackageUpdate'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Buttons = null;
            this.$Result  = null;
            this.$list    = [];
            this.$view    = options && options.view || 'tile';

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'qui-control-packages-update',
                html   : Mustache.render(template, {
                    URL_BIN_DIR: URL_BIN_DIR
                })
            });

            this.$Buttons = this.$Elm.getElement(
                '.qui-control-packages-update-buttons'
            );

            this.$Result = this.$Elm.getElement(
                '.qui-control-packages-update-result'
            );

            this.$Update = new QUIButton({
                name        : 'update',
                text        : QUILocale.get(lg, 'packages.panel.btn.startUpdate'),
                textimage   : 'fa fa-check-circle-o',
                checkUpdates: false,
                events      : {
                    onClick: function (Btn) {
                        if (!Btn.getAttribute('checkUpdates')) {
                            this.checkUpdates();
                        } else {
                            this.executeSystemUpdate();
                        }
                    }.bind(this)
                }
            }).inject(this.$Buttons);

            this.$Setup = new QUIButton({
                name     : 'setup',
                text     : QUILocale.get(lg, 'packages.panel.btn.setup'),
                textimage: 'fa fa-hdd-o',
                events   : {
                    onClick: this.executeCompleteSetup
                },
                styles   : {
                    margin: '0 0 0 20px'
                }
            }).inject(this.$Buttons);

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            switch (this.$view) {
                case 'list':
                    this.viewList();
                    break;

                default:
                case 'tile':
                    this.viewTile();
                    break;
            }
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.$List = {
                viewTile: this.viewTile,
                viewList: this.viewList
            };

            require(['QUIQQER'], function (QUIQQER) {
                QUIQQER.getInformation().then(function (data) {
                    self.$Elm.getElement(
                        '.qui-control-packages-update-infos-version'
                    ).set('html', data.version);

                    self.$Elm.getElement(
                        '.qui-control-packages-update-infos-ref'
                    ).set('html', data.source.reference);

                    self.$Elm.getElement(
                        '.qui-control-packages-update-infos-time'
                    ).set('html', data.time);

                }).then(function () {
                    return self.refreshLastUpdateCheckDate();
                }).then(function () {
                    self.fireEvent('load', [self]);
                });
            });
        },

        /**
         * Refrsh the last update date display
         *
         * @returns {Promise}
         */
        refreshLastUpdateCheckDate: function () {
            return Packages.getLastUpdateCheck(true).then(function (lastUpdateCheck) {
                if (!lastUpdateCheck) {
                    lastUpdateCheck = '---';
                }

                this.$Elm.getElement(
                    '.qui-control-packages-update-infos-lastCheck'
                ).set('html', lastUpdateCheck);
            }.bind(this));
        },

        /**
         * Execute a complete setup
         *
         * @returns {Promise}
         */
        executeCompleteSetup: function () {
            var Button = this.$Setup;

            FaviconUtils.loading();

            Button.setAttribute('textimage', 'fa fa-spinner fa-spin');

            return QUI.getMessageHandler().then(function (MH) {
                return MH.addLoading('message.setup.runs');

            }).then(function (Loading) {

                return Packages.setup().then(function () {
                    return Translator.refreshLocale();
                }).then(function () {
                    Loading.finish(QUILocale.get(lg, 'message.setup.successfull'));

                    return QUI.getMessageHandler().then(function (Handler) {
                        Handler.pushSuccess(
                            QUILocale.get(lg, 'message.setup.successfull.title'),
                            QUILocale.get(lg, 'message.setup.successfull'),
                            false
                        );
                    });
                }).catch(function (Error) {
                    return QUI.getMessageHandler().then(function (MH) {
                        if (typeOf(Error) === 'string') {
                            MH.addError(Error);
                            Loading.finish(Error, 'error');
                            return;
                        }

                        MH.addError(Error.getMessage());
                        Loading.finish(Error.getMessage(), 'error');
                    });
                });

            }).then(function () {
                Button.setAttribute('textimage', 'fa fa-hdd-o');
                FaviconUtils.setDefault();
            });
        },

        /**
         * Execute a complete setup
         *
         * @returns {Promise}
         */
        checkUpdates: function () {
            var self   = this,
                Button = this.$Update;

            Button.setAttribute('textimage', 'fa fa-spinner fa-spin');

            return Packages.getOutdated().then(function (result) {
                var title   = QUILocale.get(lg, 'message.update.not.available.title'),
                    message = QUILocale.get(lg, 'message.update.not.available.description');

                if (result && result.length) {
                    title   = QUILocale.get(lg, 'message.update.available.title');
                    message = QUILocale.get(lg, 'message.update.available.description');

                    self.$list = result;

                    Button.setAttribute(
                        'text',
                        QUILocale.get(lg, 'packages.panel.btn.executeUpdate')
                    );
                }

                QUI.getMessageHandler().then(function (Handler) {
                    if (result) {
                        Handler.pushAttention(title, message, false);
                        Handler.addAttention(message);
                        return;
                    }

                    Handler.pushInformation(title, message, false);
                    Handler.addInformation(message);
                });

                if (result && result.length) {
                    Button.setAttribute('textimage', 'fa fa-exclamation-triangle');
                    Button.setAttribute('checkUpdates', true);
                } else {
                    Button.setAttribute('textimage', 'fa fa-check-circle-o');
                    Button.setAttribute('checkUpdates', false);
                }

                self.refresh();

            }).then(function () {
                return self.refreshLastUpdateCheckDate();

            }).catch(function (Exception) {

                QUI.getMessageHandler().then(function (Handler) {
                    Handler.pushError(
                        QUILocale.get(lg, 'message.update.error.title'),
                        Exception.getMessage(),
                        false
                    );

                    Handler.addError(Exception.getMessage());
                });

                Button.setAttribute('textimage', 'fa fa-check-circle-o');
            });
        },

        /**
         * Execute a complete system update
         * @return {Promise}
         */
        executeSystemUpdate: function () {
            this.fireEvent('showLoader');

            return Packages.update().then(function () {
                this.$Update.setAttribute('textimage', 'fa fa-check-circle-o');
                this.$Update.setAttribute('checkUpdates', false);
                this.checkUpdates().then(function () {
                    this.fireEvent('hideLoader');
                }.bind(this));
            }.bind(this));
        },

        /**
         * Execute a package update
         *
         * @param {String} pkg - name of the package
         * @return {Promise}
         */
        executePackageUpdate: function (pkg) {
            return Packages.update(pkg);
        },

        /**
         * Return the list
         *
         * @returns {Object}
         */
        getList: function () {
            return this.$List;
        },

        /**
         * Tile view
         */
        viewTile: function () {
            this.$view = 'tile';
            this.$Result.set('html', '');

            var i, len, pkg, Package;

            var Update = new Element('span', {
                'class': 'fa fa-play-circle-o button'
            });

            for (i = 0, len = this.$list.length; i < len; i++) {
                pkg = this.$list[i];

                Package = new Element('div', {
                    'class'       : 'packages-package qui-control-packages-system-package-viewTile',
                    'html'        : '<div class="qui-control-packages-system-package-viewTile-text">' +
                                    '  <span class="package">' + pkg.package + '</span>' +
                                    '  <span class="version">' + pkg.version + '</span>' +
                                    '  <span class="oldVersion">' + pkg.oldVersion + '</span>' +
                                    '</div>' +
                                    '<div class="qui-control-packages-system-package-viewTile-buttons"></div>',
                    title         : QUILocale.get(lg, 'packages.panel.system.packageUpdate.title', {
                        package: pkg.package,
                        version: pkg.version
                    }),
                    'data-package': pkg.package
                }).inject(this.$Result);

                Update.clone().addEvent('click', this.$onPackageUpdate)
                    .inject(Package.getElement('.qui-control-packages-system-package-viewTile-buttons'));
            }
        },

        /**
         * List view
         */
        viewList: function () {
            this.$view = 'tile';
            this.$Result.set('html', '');

            var i, len, pkg, Package;

            for (i = 0, len = this.$list.length; i < len; i++) {
                pkg = this.$list[i];

                Package = new Element('div', {
                    'class'       : 'packages-package qui-control-packages-system-package-viewList',
                    'html'        : '<div class="qui-control-packages-system-package-viewList-text">' +
                                    '  <span class="package">' + pkg.package + '</span>' +
                                    '  <span class="version">' + pkg.version + '</span>' +
                                    '  <span class="oldVersion">' + pkg.oldVersion + '</span>' +
                                    '</div>' +
                                    '<div class="qui-control-packages-system-package-viewList-buttons"></div>',
                    events        : {
                        click: this.$onPackageUpdate
                    },
                    'data-package': pkg.package
                }).inject(this.$Result);
            }
        },

        /**
         * event : on package update click
         *
         * @param {Event} event
         */
        $onPackageUpdate: function (event) {
            var Target  = event.target,
                Package = Target.getParent('.packages-package'),
                pkg     = Package.get('data-package');

            var Loader = new Element('div', {
                'class': 'packages-package-loader',
                'html' : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    opacity: 0
                }
            }).inject(Package);

            moofx(Loader).animate({
                opacity: 1
            }, {
                duration: 250,
                callback: function () {
                    this.executePackageUpdate(pkg).then(function () {
                        moofx(Loader).animate({
                            opacity: 0
                        }, {
                            duration: 250,
                            callback: function () {
                                Loader.destroy();

                                moofx(Package).animate({
                                    opacity: 0,
                                    width  : 0
                                }, {
                                    callback: function () {
                                        Package.destroy();
                                    }
                                });
                            }
                        });
                    });
                }.bind(this)
            });
        }
    });
});
