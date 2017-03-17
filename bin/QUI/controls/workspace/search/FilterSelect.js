/**
 * Select multiple filters for QUIQQER Backend Search
 *
 * @module controls/workspace/search/FilterSelect
 *
 * @require qui/controls/buttons/Select
 * @require Locale
 * @require Ajax
 *
 * @event onLoaded [this] - fires when all filters are loaded and the FilterSelect is ready
 */
define('controls/workspace/search/FilterSelect', [

    'qui/controls/buttons/Select',
    'Locale',
    'Ajax'

], function (QUISelect, QUILocale, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUISelect,
        Type   : 'controls/workspace/search/FilterSelect',

        Binds: [
            '$onCreate',
            '$onImport',
            '$onInject'
        ],

        options: {
            showIcons            : false,
            checkable            : true,
            placeholderIcon      : false,
            placeholderSelectable: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Groups        = {};
            this.$DefaultGroups = {};

            this.addEvents({
                onCreate: this.$onCreate,
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * event: onCreate
         */
        create: function () {
            var self = this;

            this.setAttribute(
                'placeholderText',
                QUILocale.get('quiqqer/quiqqer',
                    'controls.workspace.search.filter.placeholder'
                )
            );

            this.$Elm = this.parent();

            Promise.all([
                this.$getDefaultFilters(),
                this.$getFilterGroups()
            ]).then(function (result) {
                if (result[0]) {
                    self.$DefaultGroups = result[0];
                }

                var filterGroups    = result[1];

                for (var i = 0, len = filterGroups.length; i < len; i++) {
                    var Group = filterGroups[i];

                    if ("label" in Group) {
                        Group.label = QUILocale.get(Group.label[0], Group.label[1]);
                    } else {
                        Group.label = Group.group;
                    }

                    self.$Groups[Group.group] = Group;

                    self.appendChild(
                        Group.label,
                        Group.group,
                        false
                    );
                }

                self.fireEvent('loaded', [self]);
            });

            return this.$Elm;
        },

        /**
         * event: onImport
         */
        $onImport: function () {
            var self = this;

            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            var Elm = this.create();

            self.addEvents({
                onLoaded: function () {
                    if (self.$Input.value.length) {
                        var FilterGroups = JSON.decode(self.$Input.value);

                        for (var group in FilterGroups) {
                            if (!FilterGroups.hasOwnProperty(group)) {
                                continue;
                            }

                            self.selectChild(group);
                        }

                        return;
                    }

                    var children = self.getChildren();

                    for (var i = 0, len = children.length; i < len; i++) {
                        self.selectChild(children[i].getAttribute('value'));
                    }
                },
                onChange: function () {
                    var FilterGroups = {};
                    var values       = self.getValue();

                    for (var i = 0, len = values.length; i < len; i++) {
                        var Group                 = self.$Groups[values[i]];
                        FilterGroups[Group.group] = Group;
                    }

                    self.$Input.value = JSON.encode(FilterGroups);
                }
            });

            Elm.inject(this.$Input, 'after');
        },

        /**
         * event: onInject
         */
        $onInject: function () {
            var self = this;

            this.addEvents({
                onLoaded: function () {
                    if (!Object.getLength(self.$DefaultGroups)) {
                        var children = self.getChildren();

                        for (var i = 0, len = children.length; i < len; i++) {
                            self.selectChild(children[i].getAttribute('value'));
                        }

                        return;
                    }

                    for (var group in self.$DefaultGroups) {
                        if (!self.$DefaultGroups.hasOwnProperty(group)) {
                            continue;
                        }

                        self.selectChild(group);
                    }
                }
            });
        },

        /**
         * Get all default filters
         *
         * @return {Promise}
         */
        $getDefaultFilters: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_workspace_search_getSetting', function (result) {
                    resolve(JSON.decode(result));
                }, {
                    onError: reject,
                    section: 'general',
                    'var'  : 'defaultFilterGroups'
                });
            });
        },

        /**
         * Get all available filter groups
         *
         * @return {Promise}
         */
        $getFilterGroups: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_workspace_search_getFilterGroups', resolve, {
                    onError: reject
                });
            });
        }
    });
});
