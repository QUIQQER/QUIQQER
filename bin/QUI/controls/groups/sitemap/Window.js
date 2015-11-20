
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

        Extends : QUIConfirm,
        Type    : 'controls/groups/sitemap/Window',

        Binds : [
            '$onWindowCreate',
            '$onSubmit'
        ],

        options : {
            multible : false,
            message  : false,
            title    : Locale.get('quiqqer/system', 'groups.sitemap.window.title'),
            text     : Locale.get('quiqqer/system', 'groups.sitemap.window.text'),
            texticon : false,
            icon     : 'icon-group',
            maxHeight   : 300,
            maxWidth    : 450
        },

        initialize : function (options) {
            this.$Win = null;
            this.$Map = null;

            this.parent(options);
        },

        /**
         * event : onCreate
         */
        open : function () {
            this.parent();

            var Content = this.getContent();

            var SitemapBody = new Element('div', {
                'class' : 'group-sitemap'
            }).inject(Content);

            Content.getElements('.information').destroy();

            if (this.getAttribute('message')) {
                new Element('div', {
                    html : this.getAttribute('message')
                }).inject(Content, 'top');
            }

            this.$Map = new GroupSitemap({
                multible : this.getAttribute('multible')
            }).inject(SitemapBody);
        },

        /**
         * Submit the window
         *
         * @method qui/controls/windows/Confirm#submit
         */
        submit : function () {
            this.fireEvent('submit', [this, this.$Map.getValues()]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
