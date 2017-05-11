/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 *
 * @event onLoad
 * @event onSearchBegin
 * @event onSearchEnd
 */
define('controls/packages/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',
    'Packages',
    'Mustache',
    'controls/packages/PackageList',

    'Locale',
    'Ajax',

    'text!controls/packages/Search.html',
    'text!controls/packages/Search.TermsOfUse.html',
    'css!controls/packages/Search.css'

], function (QUI, QUIControl, QUIButton, QUILoader, Packages, Mustache, PackageList,
             QUILocale, QUIAjax, template, templateTermsOfUse) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Search',

        Binds: [
            '$onInject',
            '$onClickInstall'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$List       = null;
            this.$Results    = null;
            this.$Input      = null;
            this.$TermsOfUse = null;
            this.Loader      = new QUILoader();

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
                'class': 'qui-control-packages-search'
            });

            this.Loader.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * Return the result list
         *
         * @returns {*} PackageList
         */
        getList: function () {
            return this.$List;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.Loader.show();

            Promise.all([
                this.$checkTermsOfUse(),
                this.$getStoreUrl()
            ]).then(function (result) {
                var agreed   = result[0];
                var storeUrl = result[1];

                self.$Elm.set('html', Mustache.render(template, {
                    storeUrl: storeUrl
                }));

                self.Loader.hide();

                if (!agreed) {
                    self.$showTermsOfUse();
                }
            });

            this.fireEvent('load');
        },

        /**
         * Get URL for package store
         *
         * @return {Promise}
         */
        $getStoreUrl: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_packages_getStoreUrl', resolve, {
                    onError: reject
                });
            });
        },

        /**
         * Check if current quiqqer system has approved terms of use
         * and display terms of use if not
         */
        $checkTermsOfUse: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_packages_checkTermsOfUse', resolve, {
                    onError: reject
                });
            });
        },

        /**
         * Agree to package store terms of use
         *
         * @return {Promise}
         */
        $agreeToTermsOfUse: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_packages_agreeToTermsOfUse', resolve, {
                    onError: reject
                });
            });
        },

        /**
         * Show terms of use layer
         */
        $showTermsOfUse: function () {
            var self     = this;
            var lgPrefix = 'controls.packages.search.termsofuse.';

            this.$TermsOfUse = new Element('div', {
                'class': 'quiqqer-packages-search-termsofuse',
                html   : Mustache.render(templateTermsOfUse, {
                    header       : QUILocale.get(lg, lgPrefix + 'header'),
                    content      : QUILocale.get(lg, lgPrefix + 'content'),
                    acceptBtnText: QUILocale.get(lg, lgPrefix + 'acceptBtnText')
                })
            }).inject(this.$Elm);

            this.$TermsOfUse.getElement('button').addEvent('click', function (event) {
                self.Loader.show();

                self.$agreeToTermsOfUse().then(function () {
                    if (self.$TermsOfUse) {
                        self.$TermsOfUse.destroy();
                    }

                    self.Loader.hide();
                });
            });
        },

        /**
         * Return the list
         *
         * @returns {Object} PackageList
         */
        search: function () {
            this.fireEvent('searchBegin', [this]);

            Packages.search(this.$Input.value).then(function (result) {
                this.$List.clear();

                for (var name in result) {
                    if (!result.hasOwnProperty(name)) {
                        continue;
                    }

                    this.$List.addPackage({
                        name       : name,
                        title      : name,
                        description: result[name]
                    });
                }

                this.$List.refresh();

                this.fireEvent('searchEnd', [this]);
            }.bind(this)).catch(function () {
                this.$List.clear();
                this.fireEvent('searchEnd', [this]);
            }.bind(this));
        },

        /**
         * event: install button click
         *
         * @param {Object} Btn - qui/controls/buttons/Button
         * @param {event} event
         */
        $onClickInstall: function (Btn, event) {
            event.stop();

            this.fireEvent('onShowLoader', [this]);

            Packages.install([
                Btn.getAttribute('package')
            ]).then(function () {
                this.fireEvent('onHideLoader', [this]);
            }.bind(this));
        }
    });
});
