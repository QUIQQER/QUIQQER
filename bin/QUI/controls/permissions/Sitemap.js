/**
 * Permissions Sitemap
 *
 * @module controls/permissions/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event itemClick [Item, value]
 */
define('controls/permissions/Sitemap', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/utils/Object',
    'utils/permissions/Utils',
    'Locale'

], function (QUI, QUIControl, QUISitemap, QUISitemapItem, ObjectUtils, PermissionUtils, QUILocale) {
    "use strict";


    return new Class({

        Extends: QUIControl,
        Type   : 'controls/permissions/Sitemap',

        Binds: [
            '$onInject',
            '$onItemClick',
            '$createMap',
            '$onItemOpen'
        ],

        initialize: function (Object, options) {
            this.parent(options);

            this.$Map  = null;
            this.$Bind = Object || false;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode ELement
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'controls-permissions-sitemap'
            });

            this.$Map = new QUISitemap({
                styles: {
                    margin: '20px 10px'
                }
            });

            this.$Map.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * refresh the map
         */
        refresh: function () {
            this.$Map.clearChildren();

            this.$Map.appendChild(
                new QUISitemapItem({
                    text  : 'Rechte', // #locale
                    icon  : 'fa fa-gears',
                    value : '',
                    events: {
                        onClick: this.$onItemClick
                    }
                })
            );

            var Permissions = PermissionUtils.Permissions;

            switch (typeOf(this.$Bind)) {
                case 'classes/users/User':
                    Permissions.getUserPermissionList(this.$Bind).then(this.$createMap);
                    break;

                case 'classes/groups/Group':
                    Permissions.getGroupPermissionList(this.$Bind).then(this.$createMap);
                    break;

                case 'classes/projects/Project':
                    Permissions.getProjectPermissionList(this.$Bind).then(this.$createMap);
                    break;

                case 'classes/projects/project/Site':
                    Permissions.getSitePermissionList(this.$Bind).then(this.$createMap);
                    break;

                case 'classes/projects/project/media/File':
                case 'classes/projects/project/media/Folder':
                case 'classes/projects/project/media/Image':
                case 'classes/projects/project/media/Item':
                    Permissions.getMediaPermissionList(this.$Bind).then(this.$createMap);
                    break;

                case 'qui/classes/DOM':
                    Permissions.getList().then(this.$createMap);
                    break;

                default:
                    console.error(typeOf(this.$Bind));
            }
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.refresh();
        },

        /**
         * Create the map
         *
         * @param {Object} permissions - list of permissions
         */
        $createMap: function (permissions) {
            var i, len, arr, parent, permission, startParent;
            var permissionList = {
                items : {},
                length: 0
            };

            parent = permissionList;

            for (permission in permissions) {
                if (!permissions.hasOwnProperty(permission)) {
                    continue;
                }

                arr = permission.split('.');
                arr.pop(); // drop the last element

                startParent = parent;

                for (i = 0, len = arr.length; i < len; i++) {
                    if (typeof parent.items[arr[i]] === 'undefined') {
                        parent.items[arr[i]] = {
                            items : {},
                            length: 0
                        };
                    }

                    parent.length = Object.getLength(parent.items);
                    parent        = parent.items[arr[i]];
                }

                parent        = startParent;
                parent.length = Object.getLength(parent.items);
            }

            this.$permissionsList = permissionList;

            this.$appendSitemapItemTo(
                this.$Map.firstChild(),
                '',
                permissionList
            );

            //this.$Map.openAll();
            var FirstChild = this.$Map.firstChild();

            // FirstChild.click();
            FirstChild.open();

            if (FirstChild.firstChild()) {
                FirstChild.firstChild().click();
            }
        },

        /**
         * Recursive append item helper for sitemap
         *
         * @param {Object} Parent - qui/controls/sitemap/Item
         * @param {String} name
         * @param {Object} params
         */
        $appendSitemapItemTo: function (Parent, name, params) {
            if (!params.length) {
                return;
            }

            var list = this.$parseItemEntries(params, name);

            for (var i = 0, len = list.length; i < len; i++) {
                Parent.appendChild(
                    new QUISitemapItem({
                        icon       : 'fa fa-gears',
                        value      : list[i].permission,
                        text       : list[i].translation,
                        hasChildren: list[i].hasChildren,
                        events     : {
                            onClick: this.$onItemClick,
                            onOpen : this.$onItemOpen
                        }
                    })
                );
            }
        },

        /**
         * event : item on click
         *
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $onItemClick: function (Item) {
            this.fireEvent('itemClick', [
                Item,
                Item.getAttribute('permission')
            ]);
        },

        /**
         * event: item on open
         */
        $onItemOpen: function (Item) {
            var children = Item.getChildren();

            if (children.length) {
                return;
            }

            var i, len, perm, parent;
            var permission = Item.getAttribute('value'),
                items      = this.$permissionsList.items;

            permission = permission.split('.');
            parent     = items;

            for (i = 0, len = permission.length; i < len; i++) {
                perm = permission[i];

                // first entry
                if (typeof parent[perm] !== 'undefined') {
                    parent = parent[perm];
                    continue;
                }

                if (typeof parent.items[perm] !== 'undefined') {
                    parent = parent.items[perm];
                    continue;
                }

                // break up
                return;
            }

            if (!parent || !parent.length) {
                return;
            }

            var list = this.$parseItemEntries(
                parent,
                Item.getAttribute('value')
            );

            for (i = 0, len = list.length; i < len; i++) {
                Item.appendChild(
                    new QUISitemapItem({
                        icon       : 'fa fa-gears',
                        value      : list[i].permission,
                        text       : list[i].translation,
                        hasChildren: list[i].hasChildren,
                        events     : {
                            onClick: this.$onItemClick,
                            onOpen : this.$onItemOpen
                        }
                    })
                );
            }
        },

        /**
         * parse entries to sorted entries by its locale translation
         *
         * @param params
         * @param permissionName
         * @return {[]}
         */
        $parseItemEntries: function (params, permissionName) {
            var i, len, text, right, permission, permissionEntry;

            var groups = QUILocale.getGroups(),
                list   = [],
                items  = params.items;

            for (right in items) {
                if (!items.hasOwnProperty(right)) {
                    continue;
                }

                permissionEntry = items[right];

                if (permissionName.length) {
                    permission = permissionName + '.' + right;
                } else {
                    permission = right;
                }

                text = 'permission.' + permission + '._header';

                if (QUILocale.exists('quiqqer/quiqqer', text)) {
                    text = QUILocale.get('quiqqer/quiqqer', text);
                } else {
                    for (i = 0, len = groups.length; i < len; i++) {
                        if (QUILocale.exists(groups[i], text)) {
                            text = QUILocale.get(groups[i], text);
                            break;
                        }
                    }
                }

                list.push({
                    translation: text,
                    permission : permission,
                    right      : right,
                    hasChildren: permissionEntry.length
                });
            }

            // sort list
            list.sort(function (a, b) {
                if (a.translation > b.translation) {
                    return 1;
                }

                if (a.translation < b.translation) {
                    return -1;
                }

                return 0;
            });

            return list;
        }
    });
});
