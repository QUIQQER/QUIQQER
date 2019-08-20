/**
 * The type sitemap for the project
 *
 * The type sitemap displays / create a qui/controls/sitemap/Map
 * with all available types for the project
 *
 * @module controls/projects/TypeSitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onLoad [ self ]
 */
define('controls/projects/TypeSitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'Ajax',
    'Locale'

], function (QUIControl, QUISitemap, QUISitemapItem, Ajax, Locale) {
    "use strict";

    /**
     * The type sitemap for the project
     *
     * The type sitemap displays / create a qui/controls/sitemap/Map
     * with all available types for the project
     *
     * @class controls/projects/TypeSitemap
     *
     * @fires onItemClick
     * @fires onItemDblClick
     *
     * @param {HTMLElement} Container
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/TypeSitemap',

        options: {
            multiple         : false,
            project          : false,
            pluginsSelectable: false
        },

        Binds: [
            'open'
        ],

        initialize: function (options) {
            this.$Map       = null;
            this.$Container = null;
            this.$load      = false;

            this.parent(options);
        },

        /**
         * return the domnode
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Map = new QUISitemap({
                name    : 'Type-Sitemap',
                multiple: this.getAttribute('multiple')
            });

            // Firstchild
            var First = new QUISitemapItem({
                name       : 1,
                index      : 1,
                value      : 1,
                text       : Locale.get('quiqqer/system', 'projects.typesitemap.firstChild'),
                alt        : Locale.get('quiqqer/system', 'projects.typesitemap.firstChild'),
                icon       : 'fa fa-magic',
                hasChildren: false
            });

            this.$Map.appendChild(First);
            this.open();

            return this.$Map.create();
        },

        /**
         * Opens the first child of the sitemap
         * Fetches the children asynchronously
         *
         * @method controls/projects/TypeSitemap#open
         */
        open: function () {
            if (this.$load) {
                return;
            }

            this.$load = true;

            var self  = this,
                First = self.$Map.firstChild();

            First.removeIcon('fa-magic');
            First.addIcon('fa fa-spinner fa-spin');

            Ajax.get('ajax_project_types_get_list', function (result) {
                First = self.$Map.firstChild();
                First.clearChildren();
                First.disable();

                First.removeIcon('fa-spinner');
                First.addIcon('fa fa-magic');

                // empty result
                if (typeOf(result) === 'array') {
                    First.setAttribute(
                        'text',
                        Locale.get('quiqqer/system', 'projects.typesitemap.message.no.types')
                    );

                    self.fireEvent('load', [self]);
                    return;
                }

                var c, i, len, ilen, data, icon, Plugin, packageName;

                var func_itm_click = function (Itm) {
                    Itm.open();

                    if (self.getAttribute('pluginsSelectable')) {
                        return;
                    }

                    if (Itm.firstChild()) {
                        (function () {
                            Itm.firstChild().click();
                        }).delay(100);
                    }
                };

                var pluginSorted = Object.keys(result).sort(function (a, b) {
                    if (a === 'standard') {
                        return -1;
                    }

                    if (b === 'standard') {
                        return 1;
                    }

                    a = Locale.get(a, 'package.title');
                    b = Locale.get(b, 'package.title');

                    if (a > b) {
                        return 1;
                    }

                    if (a < b) {
                        return -1;
                    }

                    return 0;
                });

                // create the map
                for (i = 0, ilen = pluginSorted.length; i < ilen; i++) {
                    packageName = pluginSorted[i];

                    if (!result.hasOwnProperty(packageName)) {
                        continue;
                    }

                    if (packageName === 'standard') {
                        new QUISitemapItem({
                            name       : packageName,
                            value      : packageName,
                            text       : Locale.get('quiqqer/quiqqer', 'site.type.standard'),
                            alt        : packageName,
                            icon       : result[packageName].icon,
                            hasChildren: false
                        }).inject(First);

                        continue;
                    }

                    Plugin = new QUISitemapItem({
                        name       : packageName,
                        value      : packageName,
                        text       : Locale.get(packageName, 'package.title'),
                        alt        : packageName,
                        icon       : 'fa fa-puzzle-piece',
                        hasChildren: true,
                        events     : {
                            onClick: func_itm_click
                        }
                    });

                    First.appendChild(Plugin);


                    for (c = 0, len = result[packageName].length; c < len; c++) {
                        icon = 'fa fa-magic';
                        data = result[packageName][c];

                        if (data.icon) {
                            icon = data.icon;
                        }

                        new QUISitemapItem({
                            name : packageName,
                            value: data.type,
                            text : data.text || data.type.split(':')[1],
                            alt  : data.type,
                            icon : icon
                        }).inject(Plugin);
                    }
                }

                self.fireEvent('load', [self]);
                First.open();
            }, {
                project: JSON.encode({
                    name: this.getAttribute('project')
                })
            });
        },

        /**
         * Get the selected Values
         *
         * @method controls/projects/TypeSitemap#getValues
         * @return {Array} Array of the selected values
         */
        getValues: function () {
            var i, len;

            var actives = this.$Map.getSelectedChildren(),
                result  = [];

            for (i = 0, len = actives.length; i < len; i++) {
                result.push(
                    actives[i].getAttribute('value')
                );
            }

            return result;
        }
    });
});
