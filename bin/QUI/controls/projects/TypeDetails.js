
/**
 * Type details
 * Shows details of the types
 *
 * @module controls/projects/TypeDetails
 * @author www.pcsg.de (Henning Leutz)
 *
 */

define('controls/projects/TypeDetails', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',

    'css!controls/projects/TypeDetails.css'

], function (QUI, QUIControl, Ajax, QUILocale) {
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/TypeDetails',

        Binds : [
            '$showPlugins',
            '$toggleItem'
        ],

        options : {
            multible : false,
            project  : false,
            pluginsSelectable : false
        },

        initialize : function (options) {
            this.parent(options);

            this.$list      = false;
            this.$Container = false;

            this.$selected = {};
        },

        /**
         * Create the DOMNode of the control
         *
         * @return {HTMLElement}
         */
        create : function () {
            this.$Elm = new Element('div', {
                'class' : 'qui-type-details'
            });

            this.$Container = new Element('div', {
                'class' : 'qui-type-details-container',
                styles  : {
                    left    : '110%',
                    opacity : 0
                }
            }).inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * open the list
         */
        open : function () {
            var self = this;

            this.$showPlugins(function () {
                self.fireEvent('load', [self]);
            });
        },

        /**
         * Return the selected types
         *
         * @return {Array}
         */
        getValues : function () {
            var result = [];

            for (var key in this.$selected) {
                if (this.$selected.hasOwnProperty(key)) {
                    result.push(key);
                }
            }

            return result;
        },

        /**
         * Get the type list
         *
         * @param {Function} callback
         */
        $getList : function (callback) {
            if (this.$list) {
                callback(this.$list);
                return;
            }

            var self = this;

            Ajax.get('ajax_project_types_get_list', function (result) {
                self.$list = result;

                callback(result);
            }, {
                project : JSON.encode({
                    name : this.getAttribute('project')
                })
            });
        },

        /**
         * create a detail list
         *
         * @param {Object} data - data of the entry
         * @return {HTMLElement}
         */
        $createEntry : function (data) {
            var Entry = new Element('div', {
                'class' : 'qui-type-details-entry smooth',
                html : '<div class="qui-type-details-entry-icon">' +
                           '<span class="icon-puzzle-piece"></span>' +
                       '</div>' +
                       '<div class="qui-type-details-entry-text">' +
                           '<div class="qui-type-details-entry-title">' + data.title + '</div>' +
                           '<div class="qui-type-details-entry-description">' + data.description + '</div>' +
                       '</div>'
            });

            var Icon = Entry.getElement('.qui-type-details-entry-icon');

            if ("icon" in data) {
                Icon.set('html', '<span class="' + data.icon + '"></span>');
            } else {
                Icon.set('html', '<span class="icon-puzzle-piece"></span>');
            }


            if ("plugin" in data && !("sitetype" in data)) {
                var self = this;

                Entry.addClass('qui-type-details-entry-isPlugin');
                Entry.set('data-plugin', data.plugin);

                Entry.addEvent('click', function (event) {
                    var Elm = event.target;

                    if (!Elm.hasClass('qui-type-details-entry')) {
                        Elm = Elm.getParent('.qui-type-details-entry');
                    }

                    self.$showSiteTypes(Elm.get('data-plugin'));
                });

                new Element('div', {
                    'class' : 'qui-type-details-entry-opentypes',
                    html    : '<span class="icon-chevron-right"></span>',
                    'data-plugin' : data.plugin
                }).inject(Entry);
            }

            return Entry;
        },

        /**
         * Show the project plugins
         *
         * @param {Function} callback
         */
        $showPlugins : function (callback) {
            var self = this;

            this.$getList(function (result) {
                var Child;
                var Container = new Element('div', {
                    'class' : 'qui-type-details-container',
                    styles  : {
                        left    : '110%',
                        opacity : 0
                    }
                });

                Container.inject(self.getElm());


                // create the map
                for (var i in result) {
                    if (!result.hasOwnProperty(i)) {
                        continue;
                    }

                    if (i == 'standard') {
                        Child = self.$createEntry({
                            title       : QUILocale.get('quiqqer/system', 'standard.title'),
                            description : QUILocale.get('quiqqer/system', 'standard.description'),
                            sitetype    : i
                        });

                        Child.set('data-type', 'standard');

                        Child.addEvent('click', self.$toggleItem);
                        Child.inject(self.$Container);

                        continue;
                    }

                    Child = self.$createEntry({
                        title       : QUILocale.get(i, 'package.title'),
                        description : QUILocale.get(i, 'package.description'),
                        plugin      : i
                    });

                    Child.inject(Container);
                }

                if (typeof callback === 'function') {
                    callback();
                }


                moofx(self.$Container).animate({
                    left    : '-100%',
                    opacity : 0
                }, {
                    callback : function () {
                        self.$Container.destroy();
                        self.$Container = Container;

                        moofx(Container).animate({
                            left    : 0,
                            opacity : 1
                        });
                    }
                });
            });
        },

        /**
         * Show the sitetypes
         *
         * @param {String} plugin
         */
        $showSiteTypes : function (plugin) {
            var self = this;

            this.$getList(function (list) {
                if (!(plugin in list)) {
                    return;
                }

                var i, len, type, Child;

                var types     = list[ plugin ],
                    Container = new Element('div', {
                        'class' : 'qui-type-details-container',
                        styles  : {
                            left    : '110%',
                            opacity : 0
                        }
                    });

                Container.inject(self.getElm());


                new Element('div', {
                    'class' : 'qui-type-details-container-levelUp',
                    html    : '<span class="icon-level-up"></span>' +
                              '<span class="qui-type-details-container-levelUp-text">' +
                                  'Plugins' +
                              '</span>',
                    events  : {
                        click : self.$showPlugins
                    }
                }).inject(Container);


                for (i = 0, len = types.length; i < len; i++) {
                    type = types[ i ].type;

                    Child = self.$createEntry({
                        icon        : types[ i ].icon,
                        title       : QUILocale.get(plugin, type + '.title'),
                        description : QUILocale.get(plugin, type + '.description'),
                        plugin      : plugin,
                        sitetype    : types[ i ].type
                    });

                    Child.set('data-type', types[ i ].type);
                    Child.addEvent('click', self.$toggleItem);
                    Child.inject(Container);
                }

                moofx(self.$Container).animate({
                    left    : '-100%',
                    opacity : 0
                }, {
                    callback : function () {
                        self.$Container.destroy();
                        self.$Container = Container;

                        moofx(Container).animate({
                            left    : 0,
                            opacity : 1
                        });
                    }
                });
            });
        },

        /**
         * toggle the active status of an item
         *
         * @param {DOMEvent} event
         */
        $toggleItem : function (event) {
            var Target = event.target;

            if (!Target.hasClass('qui-type-details-entry')) {
                Target = Target.getParent('.qui-type-details-entry');
            }

            if (!Target.get('data-type')) {
                return;
            }

            var type = Target.get('data-type');

            if (Target.hasClass('qui-type-details-entry-active')) {
                Target.removeClass('qui-type-details-entry-active');

                if (type in this.$selected) {
                    delete this.$selected[ type ];
                }

                return;
            }

            // if no multible, we deselect all
            if (!this.getAttribute('multible')) {
                this.$selected = {};

                this.getElm()
                    .getElements('.qui-type-details-entry-active')
                    .removeClass('qui-type-details-entry-active');
            }

            Target.addClass('qui-type-details-entry-active');
            this.$selected[ type ] = true;
        }
    });
});
