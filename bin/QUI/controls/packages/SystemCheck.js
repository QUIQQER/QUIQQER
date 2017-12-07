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
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale',

    'css!controls/packages/SystemCheck.css'


], function (QUI, QUIButton, QUIControl, QUIPopup, QUILoader, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/SystemCheck',

        Binds: [
            '$onInject',
            'runSystemCheck',
            'openPHPInfoWindow',
            'getPHPInfo',
            'getChecksumForPackage'
        ],

        initialize: function (options) {
            this.parent(options);

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

            this.$Container = this.$Elm.getElement(
                '.qui-control-packages-systemcheck-container'
            );

            this.Loader = new QUILoader({
                'cssclass' : 'system-check-loader'
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.Loader.inject(this.$Elm.getParent('.qui-panel'));
            this.Loader.show(QUILocale.get(lg, 'packages.panel.category.systemcheck.loader'));

            self.fireEvent('load', [this]);

            this.runSystemCheck().then(function () {

                self.Loader.hide();

                var checksums = self.$Elm.getElement('.test-message-checkSum');

                var Packages = checksums.getChildren();

                Packages.each(function (Package) {
                    Package.addEvent('click', function () {
                        var packageName = Package.getAttribute('data-package');
                        self.getChecksumForPackage(packageName).then(function (response) {
                            var Popup = new QUIPopup({
                                'class'        : 'qui-control-packages-systemcheck-checksum',
                                maxWidth       : 900,
                                maxHeight      : 700,
                                title          : packageName,
                                closeButtonText: QUILocale.get(lg, 'close'),
                                events         : {
                                    onOpen: function (Win) {
                                        var Content = Win.getContent();
                                        Content.set('html', response);
                                    }
                                }
                            });
                            Popup.open();
                        }).catch(function (ajaxError) {

                            // error comes from ajax file itself
                            if (ajaxError) {
                                return;
                            }

                            // internal server error? response time expired?
                            QUI.getMessageHandler().then(function (MH) {
                                var message = QUILocale.get(lg, 'packages.panel.category.systemcheck.checksum.error');

                                MH.setAttribute('displayTimeMessages', 6000);
                                MH.addError(message);
                            });
                        });
                    });
                });
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

                    var html = '<div class="qui-control-packages-systemcheck-title">';
                    html += '<span class="qui-control-packages-systemcheck-title-text">';
                    html += QUILocale.get(lg, 'packages.panel.category.systemcheck.title');
                    html += '</span>';
                    html += '</div>';

                    html += result;

                    self.$Container.set('html', html);

                    self.$PHPInfo = new QUIButton({
                        name  : 'phpinfo',
                        text  : QUILocale.get(lg, 'packages.panel.category.systemcheck.phpinfo'),
                        events: {
                            onClick: function () {
                                self.openPHPInfoWindow();
                            }
                        }
                    }).inject(document.getElement('.qui-control-packages-systemcheck-title'));

                    resolve();
                });
            });
        },

        /**
         * Open window with php info
         */
        openPHPInfoWindow: function () {
            var self = this;

            var Popup = new QUIPopup({
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
            });
            Popup.open();
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
