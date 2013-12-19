/**
 * A group field / display
 * the display updates itself
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/groups/Entry
 * @package com.pcsg.qui.js.controls.groups
 * @namespace QUI.controls.groups
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
         * @return {QUI.classes.groups.Group}
         */
        getGroup : function()
        {
            return this.$Group;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @method QUI.controls.groups.Entry#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'   : 'group-entry radius5',
                'data-id' : this.$Group.getId(),

                html : '<div class="text"></div>' +
                       '<div class="close"></div>',

                events :
                {
                    mouseover : function() {
                        this.addClass( 'hover' );
                    },
                    mouseout : function() {
                        this.removeClass( 'hover' );
                    }
                }
            });

            var Close = this.$Elm.getElement( '.close' );

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
            this.$Elm.getElement( '.text' ).set(
                'html',
                '<img src="'+ URL_BIN_DIR +'images/loader.gif" />'
            );

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
         * @param {QUI.classes.groups.Group}
         * @return {this}
         */
        $onGroupUpdate : function(Group)
        {
            if ( !this.$Elm ) {
                return this;
            }

            this.$Elm.getElement( '.text' )
                     .set( 'html', Group.getAttribute( 'name' ) );

            return this;
        }
    });
});