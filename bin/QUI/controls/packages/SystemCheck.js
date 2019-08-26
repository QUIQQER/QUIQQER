/**
 * @module controls/packages/SystemCheck
 * @author www.pcsg.de (Michael Danielczok)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 *
 * @event onLoad
 */
define('controls/packages/SystemCheck', [

    'qui/QUI',
    'qui/controls/buttons/Button',
    'qui/controls/Control',
    'qui/controls/windows/Popup',
    'qui/controls/windows/Confirm',
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale',

    'css!controls/packages/SystemCheck.css'


], function (QUI, QUIButton, QUIControl, QUIPopup, QUIConfirm, QUILoader, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/SystemCheck',

        Binds: [
            '$onInject',
            'runSystemCheck',
            'execSystemCheck',
            'openPHPInfoWindow',
            'getPHPInfo',
            '$packageClick',
            'getChecksumForPackage',
            'confirmSystemCheckExec'
        ],

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();

            this.$Container = null;

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
                'class': 'qui-control-packages-systemcheck',
                'html' : '<div class="qui-control-packages-systemcheck-container"></div>'
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            // title bar
            this.TitleBar = new Element('div', {
                'class': 'qui-control-packages-systemcheck-title'
            }).inject(this.$Elm);

            // system check button
            new QUIButton({
                name  : 'Run System Check',
                text  : QUILocale.get(lg, 'packages.panel.category.systemcheck.execbutton'),
                events: {
                    onClick: self.confirmSystemCheckExec
                }
            }).inject(this.TitleBar);

            // phpinfo button
            new QUIButton({
                name  : 'phpinfo',
                text  : QUILocale.get(lg, 'packages.panel.category.systemcheck.phpinfo'),
                events: {
                    onClick: self.openPHPInfoWindow
                }
            }).inject(this.TitleBar);

            // system check result container
            this.ResultContainer = new Element('div', {
                'class': 'qui-control-packages-systemcheck-resultcontainer',
                html   : "<div class='messages-message box message-information'>" +
                         QUILocale.get(lg, 'packages.panel.category.systemcheck.textinfo') +
                         "</div>"
            }).inject(this.$Elm);

            this.getSystemCheckResultsFromCache().then(function (resultHtml) {
                self.ResultContainer.innerHTML += resultHtml;
            });

            self.fireEvent('load', [this]);
        },


        /**
         * Opens a confirm dialog to start the system check
         */
        confirmSystemCheckExec: function () {
            new QUIConfirm({
                icon: 'fa fa-info-circle',
                texticon: false,
                title      : QUILocale.get(lg, 'packages.panel.category.systemcheck.execbutton'),
                information: QUILocale.get(lg, 'packages.panel.category.systemcheck.confirm.info'),
                ok_button  : {
                    text     : QUILocale.get(lg, 'packages.panel.category.systemcheck.confirm.button.ok.text'),
                    textimage: 'fa fa-play'
                },
                events     : {
                    onSubmit: this.execSystemCheck
                }
            }).open();
        },


        /**
         * Execute system check and parse unknown packages
         */
        execSystemCheck: function () {
            this.Loader.inject(this.$Elm);

            this.Loader.show(QUILocale.get(
                lg, 'packages.panel.category.systemcheck.loader'
            ));

            var self = this;

            this.runSystemCheck().then(function () {
                var checksums = self.$Elm.getElement('.test-message-checkSum');

                var click = function (PackageElm) {
                    if (PackageElm.hasClass('unknown-packages-warning')) {
                        return;
                    }
                    PackageElm.addEvent('click', self.$packageClick);
                };

                checksums.getChildren().each(click);
                self.Loader.hide();
            });
        },

        /**
         * Run the system check
         *
         * @returns {Promise}
         */
        runSystemCheck: function () {
            var self = this;
            return new Promise(function (resolve) {
                QUIAjax.get('ajax_system_systemcheck', function (result) {
                    self.ResultContainer.set('html', result);
                    resolve();
                });
            });
        },


        /**
         * Returns a promise resolving with the test results from cache.
         *
         * @return {Promise}
         */
        getSystemCheckResultsFromCache: function () {
            return new Promise(function (resolve) {
                QUIAjax.get('ajax_system_systemcheckResultsFromCache', resolve);
            });
        },

        /**
         * Open window with php info
         */
        openPHPInfoWindow: function () {
            var self = this;

            new QUIPopup({
                'class'        : 'qui-control-packages-systemcheck-phpinfo',
                maxWidth       : 900,
                maxHeight      : 700,
                title          : QUILocale.get(lg, 'packages.panel.category.systemcheck.phpinfo'),
                closeButtonText: QUILocale.get(lg, 'close'),
                events         : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();
                        self.getPHPInfo().then(function (response) {
                            Content.set('html', response);
                        }).catch(function () {
                            Content.set(
                                'html',
                                QUILocale.get(lg, 'packages.panel.category.systemcheck.phpinfo.error')
                            );
                        });
                    }
                }
            }).open();
        },

        /**
         * Get the php info
         *
         * @returns {Promise}
         */
        getPHPInfo: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_system_phpinfo', function (result) {
                    resolve(result);
                }, {
                    onError: reject
                });
            });
        },

        /**
         * Open the popup for each package check sum
         *
         * @param {Object} event
         */
        $packageClick: function (event) {

            var packageName = event.target.getParent().getAttribute('data-package');

            this.getChecksumForPackage(packageName).then(function (response) {
                new QUIPopup({
                    'class'        : 'qui-control-packages-systemcheck-checksum',
                    maxWidth       : 900,
                    maxHeight      : 700,
                    title          : packageName,
                    closeButtonText: QUILocale.get(lg, 'close'),
                    events         : {
                        onOpen: function (Win) {
                            var message = QUILocale.get(lg, 'packages.panel.category.systemcheck.checksum.popupText');

                            Win.getContent().set(
                                'html',
                                message + response
                            );
                        }
                    }
                }).open();
            }).catch(function (ajaxError) {
                if (ajaxError) {
                    return;
                }

                QUI.getMessageHandler().then(function (MH) {
                    MH.setAttribute('displayTimeMessages', 6000);
                    MH.addError(
                        QUILocale.get(lg, 'packages.panel.category.systemcheck.checksum.error')
                    );
                });
            });
        },

        /**
         * Get check sum for files
         *
         * @param packageName
         * @returns {Promise}
         */
        getChecksumForPackage: function (packageName) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_system_systemcheckChecksum', function (result) {
                    if (!result) {
                        reject(true);
                        return;
                    }

                    resolve(result);
                }, {
                    'package'  : 'quiqqer/quiqqer',
                    packageName: packageName,
                    onError    : reject
                });
            });
        }
    });
});
