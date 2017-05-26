/**
 * LicenseKey JavaScript Control
 *
 * Manage a license key for a QUIQQER system
 *
 * @module /controls/LicenseKey
 * @author www.pcsg.de (Patrick Müller)
 *
 * @require qui/controls/loader/Loader
 * @require qui/controls/buttons/Button
 * @require utils/Controls
 * @require /search/bin/controls/SearchExtension
 * @require Locale
 */
define('controls/licenseKey/LicenseKey', [

    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/windows/Popup',
    'qui/controls/buttons/Button',
    'controls/upload/Form',

    'Locale',
    'Ajax',
    'Mustache',

    'text!controls/licenseKey/LicenseKey.html',
    'css!controls/licenseKey/LicenseKey.css'

], function (QUIControl, QUILoader, QUIPopup, QUIButton, QUIUploadForm,
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
            'refresh'
        ],

        options: {
            licenseId: false
        },

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

            this.refresh();
        },

        /**
         * Refresh license data
         */
        refresh: function()
        {
            var self = this;
            this.Loader.show();

            this.$getLicenseData().then(function (LicenseData) {
                self.Loader.hide();
                self.$displayLicenseData(LicenseData);
            });
        },

        /**
         * Display License Data
         *
         * @param {Object} LicenseData
         */
        $displayLicenseData: function(LicenseData)
        {
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

            UploadForm.setParam('onfinish', 'ajax_licenseKey_upload');
            UploadForm.setParam('extract', 0);
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
