
/**
 * Permissions Sitemap
 *
 * @module controls/permissions/Panel
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/permissions/Sitemap', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/utils/Object',
    'utils/permissions/Utils',
    'Locale'

], function(QUI, QUIControl, QUISitemap, QUISitemapItem, ObjectUtils, PermissionUtils, QUILocale)
{
    "use strict";


    return new Class({

        Extends: QUIControl,
        Types: 'controls/permissions/Sitemap',

        Binds: [
            '$onInject',
            '$onItemClick',
            '$createMap'
        ],

        initialize: function(Object, options)
        {
            this.parent(options);

            this.$Map  = null;
            this.$Bind = Object || false;

            this.addEvents({
                onInject : this.$onInject
            });
        },

        /**
         * Create the DOMNode ELement
         *
         * @return {HTMLDivElement}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'controls-permissions-sitemap'
            });

            this.$Map = new QUISitemap({
                styles : {
                    margin : '20px 10px'
                }
            });

            this.$Map.appendChild(
                new QUISitemapItem({
                    text   : 'Rechte',
                    icon   : 'icon-gears',
                    value  : '',
                    events : {
                        onClick : this.$onItemClick
                    }
                })
            );

            this.$Map.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
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
            }
        },

        /**
         * Create the map
         *
         * @param {Object} permissions - list of permissions
         */
        $createMap : function(permissions)
        {
            var arr, permission;
            var permissionList = {};

            for (permission in permissions)
            {
                if (!permissions.hasOwnProperty(permission)) {
                    continue;
                }

                arr = permission.split( '.' );
                arr.pop(); // drop the last element

                if (arr.length) {
                    ObjectUtils.namespace(arr.join( '.' ), permissionList);
                }
            }

            this.$appendSitemapItemTo(
                this.$Map.firstChild(),
                '',
                permissionList
            );

            this.$Map.openAll();
            this.$Map.firstChild().click();
        },


        /**
         * Recursive append item helper for sitemap
         *
         * @param {Object} Parent - qui/controls/sitemap/Item
         * @param {String} name
         * @param {Object} params
         */
        $appendSitemapItemTo : function(Parent, name, params)
        {
            var right, Item, _name;

            for (right in params)
            {
                if (!params.hasOwnProperty(right)) {
                    continue;
                }

                if (name.length)
                {
                    _name = name +'.'+ right;
                } else
                {
                    _name = right;
                }

                Item = new QUISitemapItem({
                    icon  : 'icon-gears',
                    value : _name,
                    text  : QUILocale.get(
                        'locale/permissions',
                        _name +'._title'
                    ),
                    events : {
                        onClick : this.$onItemClick
                    }
                });

                Parent.appendChild(Item);

                this.$appendSitemapItemTo(Item, _name, params[right]);
            }
        },

        /**
         * event : item on click
         *
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $onItemClick : function(Item)
        {
            this.fireEvent('itemClick', [
                Item,
                Item.getAttribute('permission')
            ]);
        }
    });
});