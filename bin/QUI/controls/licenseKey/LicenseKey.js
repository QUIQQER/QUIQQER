/**
 * LicenseKey JavaScript Control
 *
 * Manage a license key for a QUIQQER system
 *
 * @module controls/LicenseKey
 * @author www.pcsg.de (Patrick Müller)
 *
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require qui/controls/windows/Popup
 * @require qui/controls/buttons/Button
 * @require controls/upload/Form
 * @require Locale
 * @require Ajax
 * @require Mustache
 * @require text!controls/licenseKey/LicenseKey.html
 * @require css!controls/licenseKey/LicenseKey.css
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

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/licenseKey/LicenseKey',

        Binds: [
            '$onInject',
            '$displayLicenseData',
            '$getLicenseData',
            'refresh',
            '$deleteLicense'
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

            this.refresh().then(function() {
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
                header         : QUILocale.get(lg, lgPrefix + 'header'),
                labelId        : QUILocale.get(lg, lgPrefix + 'labelId'),
                labelCreated   : QUILocale.get(lg, lgPrefix + 'labelCreated'),
                labelValidUntil: QUILocale.get(lg, lgPrefix + 'labelValidUntil'),
                labelName      : QUILocale.get(lg, lgPrefix + 'labelName'),
                labelUpload    : QUILocale.get(lg, lgPrefix + 'labelUpload'),
                id             : LicenseData.id,
                created        : LicenseData.created,
                validUntil     : LicenseData.validUntil,
                name           : LicenseData.name
            }));

            // license key upload
            var UploadForm = new QUIUploadForm({
                multible    : false,
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
