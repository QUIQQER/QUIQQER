/**
 * The type window for the project
 *
 * The type window create a qui/controls/windows/Confirm
 * with all available types for the project
 *
 * @module controls/projects/TypeWindow
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/TypeWindow', [

    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'controls/projects/TypeSitemap',
    'Locale',

    'css!controls/projects/TypeWindow.css'

], function (QUIConfirm, QUIButton, QUI_TypeSitemap, QUILocale) {
    "use strict";

    /**
     * @class controls/projects/TypeWindow
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/projects/TypeWindow',

        Binds: [
            '$onCreate',
            '$onOpenBegin',
            'sitemapView',
            'detailsView'
        ],

        options: {
            multiple         : false,
            project          : false,
            pluginsSelectable: false,

            title    : QUILocale.get('quiqqer/core', 'projects.typewindow.title'),
            icon     : 'fa fa-magic',
            maxHeight: 600,
            maxWidth : 400,
            message  : false,

            cancel_button: {
                text     : QUILocale.get('quiqqer/core', 'cancel'),
                textimage: 'fa fa-remove'
            },
            ok_button    : {
                text     : QUILocale.get('quiqqer/core', 'ok'),
                textimage: 'fa fa-check'
            }
        },

        initialize: function (options) {
            this.parent(options);

            this.$Sitemap = null;
            this.$Elm     = null;

            this.$HeaderButtons = null;
            this.$ShowSitemap   = null;
            this.$ShowDetails   = null;

            this.addEvents({
                onOpen     : this.$onOpen,
                onOpenBegin: this.$onOpenBegin
            });
        },

        /**
         * Event : open begin
         */
        $onOpenBegin: function () {
            this.Loader.show();
        },

        /**
         * Create the Window with a type sitemap
         *
         * @method controls/projects/TypeWindow#create
         */
        $onOpen: function () {
            var Content = this.getContent();

            Content.set(
                'html',

                '<div class="qui-type-window-buttons"></div>' +
                '<div class="qui-type-window-cc"></div>' +
                '<div class="qui-type-window-info messages-message message-information">' +
                QUILocale.get('quiqqer/core', 'projects.typewindow.info') +
                '</div>'
            );

            this.$Elm.addClass('qui-type-window');

            this.$HeaderButtons = this.$Elm.getElement('.qui-type-window-buttons');
            this.$CC            = this.$Elm.getElement('.qui-type-window-cc');

            Content.setStyles({
                padding: 0
            });

            this.$ShowSitemap = new QUIButton({
                name     : 'sitemap',
                textimage: 'fa fa-sitemap',
                text     : QUILocale.get('quiqqer/core', 'projects.typewindow.btn.sitemapView'),
                events   : {
                    click: this.sitemapView
                }
            }).inject(this.$HeaderButtons);

            this.$ShowDetails = new QUIButton({
                name     : 'details',
                textimage: 'fa fa-file-text',
                text     : QUILocale.get('quiqqer/core', 'projects.typewindow.btn.detailView'),
                events   : {
                    click: this.detailsView
                }
            }).inject(this.$HeaderButtons);


            if (this.getAttribute('message')) {
                new Element('div', {
                    'class': 'messages-message message-attention',
                    html   : this.getAttribute('message')
                }).inject(Content);
            }

            this.sitemapView();
        },

        /**
         * submit the window
         *
         * @method controls/projects/TypeWindow#submit
         */
        submit: function () {
            var values = [];

            if (this.$Sitemap) {
                values = this.$Sitemap.getValues();
            }


            this.fireEvent('submit', [this, values]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        },

        /**
         * show the sitemap view
         */
        sitemapView: function () {
            var self = this;

            this.Loader.show();

            this.$ShowSitemap.setActive();
            this.$ShowDetails.setNormal();

            this.$CC.set('html', '');

            this.setAttribute('maxWidth', 400);

            this.resize(true, function () {
                require(['controls/projects/TypeSitemap'], function (TyeSitemap) {
                    self.$Sitemap = new TyeSitemap({
                        project          : self.getAttribute('project'),
                        multiple         : self.getAttribute('multiple'),
                        pluginsSelectable: self.getAttribute('pluginsSelectable'),
                        events           : {
                            onLoad: function () {
                                self.Loader.hide();
                            }
                        }
                    }).inject(self.$CC);

                    self.$Sitemap.open();
                });
            });
        },

        /**
         * show the detail view
         */
        detailsView: function () {
            var self = this;

            this.Loader.show();

            this.$ShowSitemap.setNormal();
            this.$ShowDetails.setActive();

            this.$CC.set('html', '');

            this.setAttribute('maxWidth', 700);

            this.resize(true, function () {
                require(['controls/projects/TypeDetails'], function (TypeDetails) {
                    self.$Sitemap = new TypeDetails({
                        project          : self.getAttribute('project'),
                        multiple         : self.getAttribute('multiple'),
                        pluginsSelectable: self.getAttribute('pluginsSelectable'),
                        events           : {
                            onLoad: function () {
                                self.Loader.hide();
                            }
                        }
                    }).inject(self.$CC);

                    self.$Sitemap.open();
                });
            });
        }
    });
});
