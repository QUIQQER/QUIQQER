/**
 * Makes a group input field to a field selection field
 *
 * @module controls/groups/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onAddGroup [ this, id ]
 * @event onChange [ this ]
 */
define('controls/groups/Select', [

    'qui/QUI',
    'qui/controls/elements/Select',
    'Locale',
    'Groups',

    'css!controls/groups/Select.css'

], function (QUIControl, QUIElementSelect, QUILocale, Groups) {
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

            this.setAttribute('Search', this.groupSearch);
            this.setAttribute('icon', 'fa fa-group');
            this.setAttribute('child', 'controls/groups/SelectItem');

            this.setAttribute(
                'placeholder',
                QUILocale.get(lg, 'control.groups.select.search.field.placeholder')
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
        groupSearch: function (value) {
            return new Promise(function (resolve) {
                Groups.search({
                    order: 'ASC',
                    limit: 5
                }, {
                    id  : value,
                    name: value
                }).then(function (result) {
                    var data = [];

                    for (var i = 0, len = result.data.length; i < len; i++) {
                        data.push({
                            id   : result.data[i].id,
                            title: result.data[i].name,
                            icon : 'icon-group'
                        });
                    }

                    resolve(data);
                });
            });
        },

        /**
         * event : on search click
         *
         * @param {Object} Select
         * @param {Object} Btn
         */
        $onSearchButtonClick: function (Select, Btn) {
            var oldIcon = Btn.getAttribute('icon');

            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');
            Btn.disable();

            require([
                'controls/groups/sitemap/Window'
            ], function (Window) {
                new Window({
                    autoclose: true,
                    multiple : this.getAttribute('multiple'),
                    events   : {
                        onSubmit: function (Win, groupIds) {
                            for (var i = 0, len = groupIds.length; i < len; i++) {
                                this.addItem(groupIds[i]);
                            }
                        }.bind(this)
                    }
                }).open();

                Btn.setAttribute('icon', oldIcon);
                Btn.enable();
            }.bind(this));
        }
    });
});
