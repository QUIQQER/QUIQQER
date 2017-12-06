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
    'Ajax',
    'Locale',

    'css!controls/packages/SystemCheck.css'


], function (QUI, QUIButton, QUIControl, QUIPopup, QUIAjax, QUILocale) {
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
            'openChecksumPopup',
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

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;
            this.runSystemCheck().then(function () {
                self.fireEvent('load', [this]);
                console.log(document.getElement('div[data-package="mtdowling/cron-expression"]'));

                var checksums = self.$Elm.getElement('.test-message-checkSum');

                var Packages = checksums.getChildren();

                Packages.each(function (Package) {
                    Package.addEvent('click', function () {
                        self.openChecksumPopup(Package.getAttribute('data-package'));
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
         * Open the check sum for files popup
         *
         * @param packageName
         */
        openChecksumPopup: function (packageName) {
            var self = this;

            var Popup = new QUIPopup({
                'class'        : 'qui-control-packages-systemcheck-checksum',
                maxWidth       : 900,
                maxHeight      : 700,
                title          : QUILocale.get(lg, 'packages.panel.category.systemcheck.checksum'),
                closeButtonText: QUILocale.get(lg, 'close'),
                events         : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();
                        self.getChecksumForPackage(packageName).then(function (response) {
                            Content.set('html', response);
                        }).catch(function () {
                            Content.set(
                                'html',
                                QUILocale.get(lg, 'packages.panel.category.systemcheck.checksum.error')
                            );
                        });

                    }
                }
            });
            Popup.open();
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
                    resolve(result);
                }, {
                    params : JSON.encode({
                        packageName: packageName
                    }),
                    onError: reject
                });
            });
        }
    });
});
