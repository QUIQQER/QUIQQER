/**
 * Makes a group input field to a field selection field
 *
 * @module controls/groups/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/elements/Select
 * @require Locale
 * @require Groups
 * @require css!controls/groups/Select.css
 *
 * @event onAddGroup [ this, id ]
 * @event onChange [ this ]
 */
define('controls/usersAndGroups/Select', [

    'qui/QUI',
    'qui/controls/elements/Select',
    'Locale',
    'Groups',
    'Users',
    'Ajax',

    'css!controls/groups/Select.css'

], function (QUIControl, QUIElementSelect, QUILocale, Groups, Users, Ajax) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    /**
     * @class controls/groups/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIElementSelect,
        Type   : 'controls/groups/Select',

        Binds: [
            '$onSearchButtonClick',
            'groupSearch'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('Search', this.usersGroupSearch);
            this.setAttribute('icon', 'fa fa-group');
            this.setAttribute('child', 'controls/usersAndGroups/SelectItem');

            this.setAttribute(
                'placeholder',
                QUILocale.get(lg, 'control.usersgroups.select.search.field.placeholder')
            );

            this.addEvents({
                onSearchButtonClick: this.$onSearchButtonClick
            });
        },

        /**
         * Execute the search
         *
         * @param {String} value
         * @returns {Promise}
         */
        usersGroupSearch: function (value) {
            return new Promise(function (resolve) {
                Ajax.get('ajax_usersgroups_search', function (result) {
                    var i, len;

                    var data         = [],
                        userResult   = result.users,
                        groupsResult = result.groups;

                    for (i = 0, len = userResult.length; i < len; i++) {
                        data.push({
                            id   : 'u' + userResult[i].id,
                            title: userResult[i].username,
                            icon : 'fa fa-user'
                        });
                    }

                    for (i = 0, len = groupsResult.length; i < len; i++) {
                        data.push({
                            id   : 'g' + groupsResult[i].id,
                            title: groupsResult[i].name,
                            icon : 'fa fa-group'
                        });
                    }

                    resolve(data);
                }, {
                    search: value,
                    fields: false,
                    params: JSON.decode({
                        limit: 5
                    })
                });
            });
        },

        /**
         * event : on search button click
         *
         * @param {Object} self - select object
         * @param {Object} Btn - button object
         */
        $onSearchButtonClick: function (self, Btn) {
            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

            require([
                'controls/usersAndGroups/search/Window'
            ], function (Window) {
                new Window({
                    autoclose: true,
                    multiple : this.getAttribute('multiple'),
                    events   : {
                        onSubmit: function (Win, data) {
                            data = data.map(function (Entry) {
                                if (Entry.type == 'group') {
                                    return 'g' + Entry.id;
                                }
                                return 'u' + Entry.id;
                            });

                            for (var i = 0, len = data.length; i < len; i++) {
                                this.addItem(data[i]);
                            }
                        }.bind(this)
                    }
                }).open();

                Btn.setAttribute('icon', 'fa fa-search');
            }.bind(this));
        }
    });
});
