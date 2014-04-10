/**
 * A group field / display
 * the display updates itself
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/groups/Entry
 *
 * @require controls/Control
 * @require Groups
 */

define('controls/groups/Entry', [

    'qui/controls/Control',
    'Groups',

    'css!controls/groups/Entry.css'

], function(QUIControl, Groups)
{
    "use strict";

    /**
     * @class controls/groups/Entry
     *
     * @param {Integer} gid - Group-ID
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/groups/Entry',

        Binds : [
            '$onGroupUpdate',
            'destroy'
        ],

        initialize : function(gid, options)
        {
            this.$Group = Groups.get( gid );
            this.parent( options );

            this.$Elm = null;
        },

        /**
         * Return the binded Group
         *
         * @return {classes/groups/Group}
         */
        getGroup : function()
        {
            return this.$Group;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @method controls/groups/Entry#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'   : 'group-entry',
                'data-id' : this.$Group.getId(),

                html : '<span class="group-entry-icon icon-group"></span>' +
                       '<span class="group-entry-text"></span>' +
                       '<span class="group-entry-close icon-remove"></span>'
            });

            var Close = this.$Elm.getElement( '.group-entry-close' );

            Close.addEvent( 'click', this.destroy);
            Close.set({
                alt   : 'Gruppe entfernen',
                title : 'Gruppe entfernen'
            });

            this.$Group.addEvent( 'onRefresh', this.$onGroupUpdate );
            this.refresh();

            return this.$Elm;
        },

        /**
         * event : on entry destroy
         */
        $onDestroy : function()
        {
            this.$Group.removeEvent( 'refresh', this.$onGroupUpdate );
        },

        /**
         * Refresh the data of the group
         *
         * @return {this}
         */
        refresh : function()
        {
            var GroupIcon = this.$Elm.getElement( '.group-entry-icon' );

            GroupIcon.removeClass( 'icon-group' );
            GroupIcon.addClass( 'icon-refresh' );
            GroupIcon.addClass( 'icon-spin' );

            if ( this.$Group.getAttribute('name') )
            {
                this.$onGroupUpdate( this.$Group );
                return this;
            }

            this.$Group.load();

            return this;
        },

        /**
         * Update the group name
         *
         * @param {classes/groups/Group}
         * @return {this}
         */
        $onGroupUpdate : function(Group)
        {
            if ( !this.$Elm ) {
                return this;
            }

            var GroupIcon = this.$Elm.getElement( '.group-entry-icon' );

            GroupIcon.addClass( 'icon-group' );
            GroupIcon.removeClass( 'icon-refresh' );
            GroupIcon.removeClass( 'icon-spin' );

            this.$Elm.getElement( '.group-entry-text' )
                     .set( 'html', Group.getAttribute( 'name' ) );

            return this;
        }
    });
});