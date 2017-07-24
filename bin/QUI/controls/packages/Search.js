/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 * @requires qui/controls/buttons/Button
 * @requires qui/controls/loader/Loader
 * @requires Packages
 * @requires Mustache
 * @requires controls/packages/PackageList
 * @requires Locale
 * @requires Ajax
 * @requires text!controls/packages/Search.html
 * @requires text!controls/packages/Search.TermsOfUse.html
 * @requires text!controls/packages/Search.OtherSources.html
 * @requires css!controls/packages/Search.css
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
    'classes/packages/StoreApi',

    'Locale',
    'Ajax',

    'text!controls/packages/Search.html',
    'text!controls/packages/Search.TermsOfUse.html',
    'text!controls/packages/Search.OtherSources.html',
    'css!controls/packages/Search.css'

], function (QUI, QUIControl, QUIButton, QUILoader, Packages,
             Mustache, PackageList, StoreApi, QUILocale, QUIAjax, template,
             templateTermsOfUse, templateOtherSources) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Search',

        Binds: [
            '$onInject',
            '$onClickInstall',
            '$loadPackageStore',
            '$loadOtherSourcesSearch',
            '$onResize'
        ],

        options: {
            buttons: []
        },

        initialize: function (options) {
            this.parent(options);

            this.$OtherSourcesResultList = null;

            this.$Results         = null;
            this.$Input           = null;
            this.$TermsOfUse      = null;
            this.Loader           = new QUILoader();
            this.$Content         = null;
            this.$storeUrl        = null;
            this.$PackageStoreBtn = null;
            this.$OtherSourcesBtn = null;
            this.$Panel           = null;

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
            return this.$OtherSourcesResultList;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.Loader.show();

            // get parent panel
            Promise.all([
                this.$checkTermsOfUse(),
                this.$getStoreUrl()
            ]).then(function (result) {
                var agreed     = result[0];
                self.$storeUrl = result[1];

                var lgPrefix = 'controls.packages.search.template.';

                self.$Elm.set('html', Mustache.render(template, {
                    togglePackageStore: QUILocale.get(lg, lgPrefix + 'togglePackageStore'),
                    toggleOtherSources: QUILocale.get(lg, lgPrefix + 'toggleOtherSources')
                }));

                self.$Content = self.$Elm.getElement(
                    '.qui-controls-packages-search-content'
                );

                self.$PackageStoreBtn = self.$Elm.getElement(
                    '.qui-controls-packages-search-toggle-packagestore'
                );

                self.$OtherSourcesBtn = self.$Elm.getElement(
                    '.qui-controls-packages-search-toggle-othersources'
                );

                self.$PackageStoreBtn.addEvent('click', self.$loadPackageStore);
                self.$OtherSourcesBtn.addEvent('click', self.$loadOtherSourcesSearch);

                self.Loader.hide();

                if (!agreed) {
                    self.$showTermsOfUse();
                    return;
                }

                self.$loadPackageStore();
            });

            this.fireEvent('load');
        },

        /**
         * Show the package store
         */
        $loadPackageStore: function () {
            this.$Content.set('html', '');

            this.$PackageStoreBtn.addClass('qui-controls-packages-search-toggle-active');
            this.$OtherSourcesBtn.removeClass('qui-controls-packages-search-toggle-active');

            var StoreFrame = new Element('iframe', {
                'class': 'qui-control-packages-search-iframe',
                src    : this.$storeUrl
            }).inject(this.$Content);

            var frameWindow   = StoreFrame.contentWindow;
            var StoreApiClass = new StoreApi();

            var FuncApiController = function (event) {
                var Data = event.data;

                // init request
                if (Data.func === 'init') {
                    frameWindow.postMessage(true, '*');
                    return;
                }

                if (typeof StoreApiClass[Data.func] === 'undefined') {
                    console.log("func not found");
                    frameWindow.postMessage(null, '*');
                    return;
                }

                // regular request
                var params = Data.params || [];

                StoreApiClass[Data.func].apply(StoreApiClass, params).then(function (result) {
                    frameWindow.postMessage(result, '*');
                }, function () {
                    frameWindow.postMessage(null, '*');
                });
            };

            window.removeEventListener('message', FuncApiController);
            window.addEventListener('message', FuncApiController);
        },

        /**
         * Show othersources search
         */
        $loadOtherSourcesSearch: function () {
            var self     = this;
            var lgPrefix = 'controls.packages.search.othersources.template.';

            this.$Content.set('html', Mustache.render(templateOtherSources, {
                labelSearch      : QUILocale.get(lg, lgPrefix + 'labelSearch'),
                placeholderSearch: QUILocale.get(lg, lgPrefix + 'placeholderSearch')
            }));

            this.$PackageStoreBtn.removeClass('qui-controls-packages-search-toggle-active');
            this.$OtherSourcesBtn.addClass('qui-controls-packages-search-toggle-active');

            var SearchInput = this.$Content.getElement('input');

            SearchInput.addEvent('keydown', function (event) {
                if (typeof event !== 'undefined' &&
                    event.code === 13) {
                    event.target.blur();
                    self.search(event.target.value).then(function () {
                        event.target.focus();
                    });
                }
            });

            SearchInput.focus();

            this.$OtherSourcesResultList = new PackageList({
                buttons: [{
                    icon  : 'fa fa-download',
                    title : QUILocale.get(lg, 'packages.panel.system.packageInstall.title'),
                    styles: {
                        width: '100%'
                    },
                    events: {
                        onClick: this.$onClickInstall
                    }
                }]
            }).inject(
                this.$Content.getElement(
                    '.qui-controls-packages-search-othersources-content'
                )
            );
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

            this.$TermsOfUse.getElement('button').addEvent('click', function () {
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
         * @param {String} term
         * @returns {Promise}
         */
        search: function (term) {
            this.fireEvent('searchBegin', [this]);

            return Packages.search(term).then(function (result) {
                this.$OtherSourcesResultList.clear();

                for (var name in result) {
                    if (!result.hasOwnProperty(name)) {
                        continue;
                    }

                    this.$OtherSourcesResultList.addPackage({
                        name       : name,
                        title      : name,
                        description: result[name],
                        installed  : false
                    });
                }

                this.$OtherSourcesResultList.refresh();

                this.fireEvent('searchEnd', [this]);
            }.bind(this)).catch(function () {
                this.$OtherSourcesResultList.clear();
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

            var self        = this,
                SearchInput = this.$Content.getElement('input');

            this.fireEvent('onShowLoader', [this]);

            Packages.install([
                Btn.getAttribute('package')
            ]).then(function () {
                return self.search(SearchInput.value);
            }).then(function () {
                self.fireEvent('onHideLoader', [this]);
            });
        }
    });
});
