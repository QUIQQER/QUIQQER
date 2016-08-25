/**
 * Groups Sitemapwindow
 *
 * @module controls/groups/sitemap/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Confirm
 * @require controls/groups/Sitemap
 * @require Locale
 *
 * @event onSubmit [ this, values ]
 */

define('controls/groups/sitemap/Window', [

    'qui/controls/windows/Confirm',
    'controls/groups/Sitemap',
    'Locale'

], function (QUIConfirm, GroupSitemap, Locale) {
    "use strict";

    /**
     * @class controls/groups/sitemap/Window
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/groups/sitemap/Window',

        Binds: [
            '$onWindowCreate',
            '$onSubmit'
        ],

        options: {
            multible   : false,
            multiple   : false,
            message    : false,
            title      : Locale.get('quiqqer/system', 'groups.sitemap.window.title'),
            text       : Locale.get('quiqqer/system', 'groups.sitemap.window.text'),
            information: Locale.get('quiqqer/system', 'groups.sitemap.window.information'),
            texticon   : false,
            icon       : 'fa fa-group',
            maxHeight  : 600,
            maxWidth   : 400
        },

        initialize: function (options) {
            this.$Win = null;
            this.$Map = null;

            this.parent(options);
        },

        /**
         * event : onCreate
         */
        open: function () {
            this.parent();

            var Content = this.getContent();

            var SitemapBody = new Element('div', {
                'class': 'group-sitemap'
            }).inject(Content);

            if (!this.getAttribute('information')) {
                Content.getElements('.information').destroy();
            }

            if (this.getAttribute('message')) {
                new Element('div', {
                    html: this.getAttribute('message')
                }).inject(Content, 'top');
            }

            // bugfix
            if (this.getAttribute('multible')) {
                this.setAttribute('multiple', this.getAttribute('multible'));
            }

            this.$Map = new GroupSitemap({
                multible: this.getAttribute('multiple')
            }).inject(SitemapBody);
        },

        /**
         * Submit the window
         *
         * @method qui/controls/windows/Confirm#submit
         */
        submit: function () {
            this.fireEvent('submit', [this, this.$Map.getValues()]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
