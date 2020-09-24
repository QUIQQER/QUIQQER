/**
 * LicenseKey JavaScript Control
 *
 * Manage a license key for a QUIQQER system
 *
 * @module controls/LicenseKey
 * @author www.pcsg.de (Patrick Müller)
 */
define('controls/licenseKey/LicenseKey', [

    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'controls/upload/Form',

    'Locale',
    'Ajax',
    'Mustache',

    'text!controls/licenseKey/LicenseKey.html',
    'css!controls/licenseKey/LicenseKey.css'

], function (QUIControl, QUILoader, QUIConfirm, QUIButton, QUIUploadForm,
             QUILocale, QUIAjax, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/licenseKey/LicenseKey',

        Binds: [
            '$onInject',
            '$displayLicenseData',
            '$getLicenseData',
            'refresh',
            '$deleteLicense',
            '$checkStatus',
            '$getStatus'
        ],

        initialize: function (options) {
            this.parent(options);

            this.Loader       = new QUILoader();
            this.$License     = null;
            this.$PackageList = {};

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Event: onImport
         */
        $onInject: function () {
            this.$Elm.addClass('quiqqer-licensekey');
            this.Loader.inject(this.$Elm);

            var self = this;

            this.refresh().then(function () {
                self.fireEvent('load');
            });
        },

        /**
         * Refresh license data
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;
            this.Loader.show();

            return this.$getLicenseData().then(function (LicenseData) {
                self.Loader.hide();
                self.$displayLicenseData(LicenseData);
            });
        },

        /**
         * Display License Data
         *
         * @param {Object} LicenseData
         */
        $displayLicenseData: function (LicenseData) {
            var lgPrefix = 'controls.licensekey.template.';

            this.$Elm.set('html', Mustache.render(template, {
                header             : QUILocale.get(lg, lgPrefix + 'header'),
                labelId            : QUILocale.get(lg, lgPrefix + 'labelId'),
                labelCreated       : QUILocale.get(lg, lgPrefix + 'labelCreated'),
                labelValidUntil    : QUILocale.get(lg, lgPrefix + 'labelValidUntil'),
                labelName          : QUILocale.get(lg, lgPrefix + 'labelName'),
                labelUpload        : QUILocale.get(lg, lgPrefix + 'labelUpload'),
                labelStatus        : QUILocale.get(lg, lgPrefix + 'labelStatus'),
                labelSystemId      : QUILocale.get(lg, lgPrefix + 'labelSystemId'),
                labelSystemDataHash: QUILocale.get(lg, lgPrefix + 'labelSystemDataHash'),
                descSystemId       : QUILocale.get(lg, lgPrefix + 'descSystemId'),
                descSystemDataHash : QUILocale.get(lg, lgPrefix + 'descSystemDataHash'),
                id                 : LicenseData.id,
                created            : LicenseData.created,
                validUntil         : LicenseData.validUntil,
                name               : LicenseData.name,
                systemId           : LicenseData.systemId,
                systemDataHash     : LicenseData.systemDataHash
            }));

            // license key upload
            var UploadForm = new QUIUploadForm({
                multiple    : false,
                sendbutton  : true,
                cancelbutton: true,
                events      : {
                    onComplete: this.refresh
                }
            }).inject(
                this.$Elm.getElement('.quiqqer-licensekey-upload')
            );

            // delete button
            var DeleteBtn = new QUIButton({
                textimage: 'fa fa-trash',
                text     : QUILocale.get(lg, 'controls.licensekey.delete.btn'),
                'class'  : 'btn-red',
                events   : {
                    onClick: this.$deleteLicense
                }
            }).inject(
                this.$Elm.getElement(
                    '.quiqqer-licensekey-delete'
                )
            );

            if (LicenseData.id === '-') {
                DeleteBtn.disable();
            }

            UploadForm.setParam('onfinish', 'ajax_licenseKey_upload');
            UploadForm.setParam('extract', 0);

            this.$checkStatus();
        },

        /**
         * Check license status
         */
        $checkStatus: function () {
            var self      = this;
            var StatusElm = this.$Elm.getElement('.quiqqer-licensekey-status');

            StatusElm.set('html', QUILocale.get(lg, 'controls.licensekey.status.loading'));

            new Element('span', {
                'class': 'fa fa-spinner fa-spin'
            }).inject(StatusElm, 'top');

            var setStatus = function (status, remainingActivations) {
                StatusElm.set('html', '');

                remainingActivations = remainingActivations || false;

                new Element('div', {
                    'class': 'quiqqer-licensekey-status-text quiqqer-licensekey-status-' + status,
                    html   : QUILocale.get(lg, 'controls.licensekey.status.' + status)
                }).inject(StatusElm);

                if (remainingActivations) {
                    new Element('span', {
                        html: QUILocale.get(lg, 'controls.licensekey.status.remaining_activations', {
                            count: remainingActivations
                        })
                    }).inject(StatusElm);
                }
            };

            var showMessage = function (msg, isError) {
                var elmClass = 'quiqqer-licensekey-status-info';

                isError = isError || false;

                if (isError) {
                    elmClass = 'quiqqer-licensekey-status-error';
                }

                new Element('div', {
                    'class': elmClass,
                    html   : msg
                }).inject(StatusElm);
            };

            var showActivationRequestMessage = function (msg) {
                new QUIConfirm({
                    maxHeight: 300,
                    maxWidth : 500,
                    autoclose: true,

                    information: msg,
                    title      : false,
                    texticon   : 'fa fa-info-circle',
                    text       : QUILocale.get(lg, 'controls.licensekey.status.activation.text'),
                    icon       : 'fa fa-info-circle',

                    cancel_button: false,
                    ok_button    : {
                        text     : QUILocale.get(lg, 'controls.licensekey.status.activation.btn.ok'),
                        textimage: 'icon-ok fa fa-check'
                    }
                }).open();
            };

            var showActivateBtn = function () {
                new QUIButton({
                    'class': 'quiqqer-licensekey-status-btn',
                    text   : QUILocale.get(lg, 'controls.licensekey.status.btn.activate'),
                    events : {
                        onClick: function (Btn) {
                            Btn.disable();

                            self.$activateSystem().then(function (Status) {
                                self.$checkStatus();
                                showActivationRequestMessage(Status.msg);
                            }, function (Error) {
                                Btn.enable();
                                showActivationRequestMessage(
                                    QUILocale.get(lg, 'controls.licensekey.status.activation.error')
                                );
                            });
                        }
                    }
                }).inject(StatusElm);
            };

            this.$getStatus().then(function (Status) {
                if (Status.active) {
                    setStatus('active', Status.remainingActivations);
                } else {
                    setStatus('inactive');
                    showActivateBtn();

                    if ("reasonCode" in Status && Status.reasonCode) {
                        showMessage(QUILocale.get(lg, 'controls.licensekey.status.reasonCode.' + Status.reasonCode));
                    }
                }
            }, function (Error) {
                setStatus('unknown');
                showMessage(Error.getMessage(), true);
            });
        },

        /**
         * Activate QUIQQER system for uploaded license
         *
         * @return {Promise}
         */
        $activateSystem: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_licenseKey_activate', resolve, {
                    onError  : reject,
                    showError: false
                });
            });
        },

        /**
         * Deactivate QUIQQER system for uploaded license
         *
         * @return {Promise}
         */
        $deactivateSystem: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_licenseKey_deactivate', resolve, {
                    onError  : reject,
                    showError: false
                });
            });
        },

        /**
         * Get license status
         *
         * @return {Promise}
         */
        $getStatus: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_licenseKey_checkStatus', resolve, {
                    onError  : reject,
                    showError: false
                });
            });
        },

        /**
         * Delete license
         */
        $deleteLicense: function () {
            var self = this;

            // open popup
            var Popup = new QUIConfirm({
                'maxHeight': 300,
                'autoclose': true,

                'information': QUILocale.get(lg,
                    'controls.licensekey.delete.popup.info'
                ),
                'title'      : QUILocale.get(lg,
                    'controls.licensekey.delete.popup.title'
                ),
                'texticon'   : 'fa fa-trash',
                'icon'       : 'fa fa-trash',

                cancel_button: {
                    text     : false,
                    textimage: 'icon-remove fa fa-remove'
                },
                ok_button    : {
                    text     : false,
                    textimage: 'icon-ok fa fa-check'
                },
                events       : {
                    onSubmit: function () {
                        Popup.Loader.show();

                        QUIAjax.post(
                            'ajax_licenseKey_delete',
                            function () {
                                Popup.close();
                                self.refresh();
                            }
                        );
                    }
                }
            });

            Popup.open();
        },

        /**
         * Get current QUIQQER license data
         *
         * @return {Promise}
         */
        $getLicenseData: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get(
                    'ajax_licenseKey_get', resolve, {
                        onError: reject
                    }
                )
            });
        }

        //$onLicenseUploadComplete: function
    });
});
