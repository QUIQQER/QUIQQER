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

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'controls/groups/Sitemap',
    'Locale',
    'Permissions'

], function (QUI, QUIConfirm, GroupSitemap, QUILocale, Permissions) {
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
            '$onSubmit',
            '$onOpen'
        ],

        options: {
            multible   : false,
            multiple   : false,
            message    : false,
            title      : QUILocale.get('quiqqer/system', 'groups.sitemap.window.title'),
            text       : QUILocale.get('quiqqer/system', 'groups.sitemap.window.title'),
            information: QUILocale.get('quiqqer/system', 'groups.sitemap.window.information'),
            texticon   : false,
            icon       : 'fa fa-group',
            maxHeight  : 600,
            maxWidth   : 400
        },

        initialize: function (options) {
            this.$Win = null;
            this.$Map = null;

            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : onCreate
         */
        $onOpen: function () {
            var self    = this,
                Content = this.getContent();

            this.Loader.show();

            Permissions.hasPermission(
                'quiqqer.admin.groups.create'
            ).then(function (hasPermission) {
                if (!hasPermission) {
                    QUI.getMessageHandler().then(function (MH) {
                        MH.addError(
                            QUILocale.get('quiqqer/system', 'exception.no.permission')
                        );
                    });

                    self.close();
                    return;
                }


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
                    multiple: this.getAttribute('multiple')
                }).inject(SitemapBody);

                self.Loader.hide();
            }.bind(this)).catch(function (err) {
                console.error(err);
                self.close();
            });
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
