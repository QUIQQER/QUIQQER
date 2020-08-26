/**
 * A group field / display
 * the display updates itself
 *
 * @module controls/groups/Entry
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require Groups
 * @require Locale
 */

define('controls/groups/Entry', [

    'qui/controls/Control',
    'Groups',
    'Locale',

    'css!controls/groups/Entry.css'

], function (QUIControl, Groups, Locale) {
    "use strict";

    /**
     * @class controls/groups/Entry
     *
     * @param {Number} gid - Group-ID
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/groups/Entry',

        Binds: [
            '$onGroupUpdate',
            'destroy'
        ],

        initialize: function (gid, options) {
            this.$Group = Groups.get(gid);
            this.parent(options);

            this.$Elm = null;
        },

        /**
         * Return the binded Group
         *
         * @return {Object} classes/groups/Group
         */
        getGroup: function () {
            return this.$Group;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @method controls/groups/Entry#create
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class'  : 'group-entry',
                'data-id': this.$Group.getId(),

                html: '<span class="group-entry-icon fa fa-group"></span>' +
                      '<span class="group-entry-text"></span>' +
                      '<span class="group-entry-close fa fa-remove"></span>'
            });

            var Close = this.$Elm.getElement('.group-entry-close');

            Close.addEvent('click', this.destroy);
            Close.set({
                alt  : Locale.get('quiqqer/quiqqer', 'groups.entry.btn.remove'),
                title: Locale.get('quiqqer/quiqqer', 'groups.entry.btn.remove')
            });

            this.$Group.addEvent('onRefresh', this.$onGroupUpdate);
            this.refresh();

            return this.$Elm;
        },

        /**
         * event : on entry destroy
         *
         * @method controls/groups/Entry#$onDestroy
         */
        $onDestroy: function () {
            this.$Group.removeEvent('refresh', this.$onGroupUpdate);
        },

        /**
         * Refresh the data of the group
         *
         * @method controls/groups/Entry#refresh
         * @return {Object} this (controls/groups/Entry)
         */
        refresh: function () {
            var GroupIcon = this.$Elm.getElement('.group-entry-icon');

            GroupIcon.removeClass('fa-group');
            GroupIcon.addClass('fa-refresh');
            GroupIcon.addClass('fa-spin');

            if (this.$Group.getAttribute('name')) {
                this.$onGroupUpdate(this.$Group);
                return this;
            }

            this.$Group.load();

            return this;
        },

        /**
         * Update the group name
         *
         * @method controls/groups/Entry#$onGroupUpdate
         * @param {Object} Group - classes/groups/Group
         * @return {Object} this (controls/groups/Entry)
         */
        $onGroupUpdate: function (Group) {
            if (!this.$Elm) {
                return this;
            }

            var GroupIcon = this.$Elm.getElement('.group-entry-icon');

            GroupIcon.addClass('fa-group');
            GroupIcon.removeClass('fa-refresh');
            GroupIcon.removeClass('fa-spin');

            this.$Elm.getElement('.group-entry-text')
                .set('html', Group.getAttribute('name'));

            return this;
        }
    });
});
