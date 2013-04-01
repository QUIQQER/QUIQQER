/**
 * Makes an input field to a user selection field
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/usersAndGroups/Input
 * @package com.pcsg.qui.js.controls.usersAndGroups
 * @namespace QUI.controls.usersAndGroups
 *
 * @require controls/Control
 * @require controls/buttons/Button
 * @require controls/users/Entry
 * @require controls/groups/Entry
 *
 * @event onAddUser [ this, id ]
 * @event onAddgroup [ this, id ]
 */

define('controls/usersAndGroups/Input', [

    'controls/Control',
    'controls/buttons/Button',
    'controls/users/Entry',
    'controls/groups/Entry',

    'css!controls/usersAndGroups/Input.css'

], function(QUI_Control)
{
    "use strict";

    QUI.namespace( 'controls.usersAndGroups' );

    /**
     * @class QUI.controls.usersAndGroups.Input
     *
     * @param {Object} options
     * @param {DOMNode Input} Input [optional] -> if no input given, one would be created
     *
     * @memberof! <global>
     */
    QUI.controls.usersAndGroups.Input = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.usersAndGroups.Input',

        Binds : [
            'close',
            'fireSearch',
            'update',
            '$onGroupUserDestroy'
        ],

        options : {
            max      : false,
            multible : true,
            name     : '',
            styles   : false
        },

        initialize : function(options, Input)
        {
            this.init( options );

            this.$Input    = Input || null;
            this.$Elm      = null;
            this.$List     = null;
            this.$Search   = null;
            this.$DropDown = null;

            this.$search = false;
            this.$values = [];

            if ( Input.value === '' ) {
                return;
            }

            var val = Input.value.split(',');

            for ( var i = 0, len = val.length; i < len; i++ )
            {
                switch ( val[ i ].substr( 0, 1 ) )
                {
                    case 'u':
                        this.addUser( val[ i ].substr( 1 ) );
                    break;

                    case 'g':
                        this.addGroup( val[ i ].substr( 1 ) );
                    break;
                }
            }
        },

        /**
         * Return the DOMNode of the users and groups search
         *
         * @method QUI.controls.usersAndGroups.Input#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'qui-users-and-groups',
                'data-quiid' : this.getId()
            });

            if ( !this.$Input )
            {
                this.$Input = new Element('input', {
                    name : this.getAttribute('name')
                }).inject( this.$Elm );
            } else
            {
                this.$Elm.wraps( this.$Input );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.$Input.set( 'type', 'hidden' );

            this.$List = new Element('div', {
                'class' : 'qui-users-and-groups-list radius5'
            }).inject( this.$Elm );

            this.$Search = new Element('input', {
                'class'     : 'qui-users-and-groups-search radius5',
                placeholder : 'Suche nach einer Gruppe oder Benutzer...',
                events :
                {
                    keyup : function(event)
                    {
                        if ( event.key === 'down' )
                        {
                            this.down();
                            return;
                        }

                        if ( event.key === 'up' )
                        {
                            this.up();
                            return;
                        }

                        if ( event.key === 'enter' )
                        {
                            this.submit();
                            return;
                        }

                        this.fireSearch();
                    }.bind( this ),

                    blur  : this.close,
                    focus : this.fireSearch
                }
            }).inject( this.$Elm );

            this.$DropDown = new Element('div', {
                'class' : 'qui-users-and-groups-dropdown',
                styles  : {
                    display : 'none',
                    top  : this.$Search.getPosition().y + this.$Search.getSize().y,
                    left : this.$Search.getPosition().x
                }
            }).inject( document.body );

            // load values


            return this.$Elm;
        },

        /**
         * fire the search
         *
         * @method QUI.controls.usersAndGroups.Input#fireSearch
         */
        fireSearch : function()
        {
            this.cancelSearch();

            this.$DropDown.set({
                html   : '<img src="'+ URL_BIN_DIR +'images/loader.gif" />',
                styles : {
                    display : '',
                    top     : this.$Search.getPosition().y + this.$Search.getSize().y,
                    left    : this.$Search.getPosition().x
                }
            });

            this.$search = this.search.delay( 500, this );
        },

        /**
         * cancel the search timeout
         *
         * @method QUI.controls.usersAndGroups.Input#cancelSearch
         */
        cancelSearch : function()
        {
            if ( this.$search ) {
                clearTimeout( this.$search );
            }
        },

        /**
         * close the users search
         *
         * @method QUI.controls.usersAndGroups.Input#close
         */
        close : function()
        {
            this.cancelSearch();
            this.$DropDown.setStyle( 'display', 'none' );
            this.$Search.value = '';
        },

        /**
         * trigger a users search and open a user dropdown for selection
         *
         * @method QUI.controls.usersAndGroups.Input#search
         */
        search : function()
        {
            QUI.Ajax.get('ajax_usersgroups_search', function(result, Request)
            {
                var i, len, nam, type, Entry,
                    func_mousedown, func_mouseover,

                    data     = result.data,
                    value    = Request.getAttribute( 'value' ),
                    Elm      = Request.getAttribute( 'Elm' ),
                    DropDown = Elm.$DropDown;


                DropDown.set( 'html', '' );

                if ( !data.length )
                {
                    new Element('div', {
                        html   : 'Keine Ergebnisse gefunden',
                        styles : {
                            'float' : 'left',
                            'clear' : 'both',
                            padding : 5,
                            margin  : 5
                        }
                    }).inject( DropDown );

                    return;
                }

                // events
                func_mousedown = function(event)
                {
                    var Elm = event.target;

                    this.add(
                        Elm.get( 'data-id' ),
                        Elm.get( 'data-type' )
                    );

                }.bind( Elm );

                func_mouseover = function()
                {
                    this.getParent().getElements( '.hover' ).removeClass( 'hover' );
                    this.addClass( 'hover' );
                };

                // create
                for ( i = 0, len = data.length; i < len; i++ )
                {
                    nam = data[ i ].username || data[ i ].name;

                    if ( value )
                    {
                        nam = nam.toString().replace(
                            new RegExp('('+ value +')', 'gi'),
                            '<span class="mark">$1</span>'
                        );
                    }


                    type = 'group';

                    if ( data[ i ].username ) {
                        type = 'user';
                    }

                    Entry = new Element('div', {
                        html        : nam +' ('+ data[ i ].id +')',
                        'class'     : 'box-sizing radius5 entry',
                        'data-id'   : data[ i ].id,
                        'data-name' : data[ i ].username || data[ i ].name,
                        'data-type' : type,
                        events :
                        {
                            mousedown  : func_mousedown,
                            mouseenter : func_mouseover
                        }
                    }).inject( DropDown );

                    if ( type == 'group' )
                    {
                        Entry.setStyle( 'background-image', 'url('+ URL_BIN_DIR +'16x16/group.png)' );
                    } else
                    {
                        Entry.setStyle( 'background-image', 'url('+ URL_BIN_DIR +'16x16/user.png)' );
                    }

                }
            }, {
                Elm    : this,
                value  : this.$Search.value,
                params : JSON.encode({
                    order  : 'ASC',
                    limit  : 5,
                    page   : 1,
                    search : true,
                    searchSettings : {
                        userSearchString : this.$Search.value
                    }
                })
            });
        },

        /**
         * Add a entry / item to the list
         *
         * @method QUI.controls.usersAndGroups.Input#add
         * @param {Integer} id  - id of the group or the user
         * @param {String} type - group or user
         *
         * @return {this}
         */
        add : function(id, type)
        {
            if ( type == 'user' ) {
                return this.addUser( id );
            }

            return this.addGroup( id );
        },

        /**
         * Add a group to the input
         *
         * @method QUI.controls.usersAndGroups.Input#addGroup
         * @param {Integer} id - id of the group
         * @return {this}
         */
        addGroup : function(id)
        {
            new QUI.controls.groups.Entry( id, {
                events : {
                    onDestroy : this.$onGroupUserDestroy
                }
            }).inject(
                this.$List
            );

            this.$values.push( 'g'+ id );

            this.fireEvent( 'addGroup', [ this, id ] );
            this.$refreshValues();

            return this;
        },

        /**
         * Add a user to the input
         *
         * @method QUI.controls.usersAndGroups.Input#addUser
         * @param {Integer} id - id of the user
         * @return {this}
         */
        addUser : function(id)
        {
            new QUI.controls.users.Entry( id, {
                events : {
                    onDestroy : this.$onGroupUserDestroy
                }
            }).inject(
                this.$List
            );

            this.$values.push( 'u'+ id );

            this.fireEvent( 'addUser', [ this, id ] );
            this.$refreshValues();

            return this;
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @method QUI.controls.usersAndGroups.Input#up
         * @return {this}
         */
        up : function()
        {
            if ( !this.$DropDown ) {
                return this;
            }

            var Active = this.$DropDown.getElement( '.hover' );

            // Last Element
            if ( !Active )
            {
                this.$DropDown.getLast().addClass( 'hover' );
                return this;
            }

            Active.removeClass( 'hover' );

            if ( !Active.getPrevious() )
            {
                this.up();
                return this;
            }

            Active.getPrevious().addClass( 'hover' );
        },

        /**
         * keydown - users dropdown selection one step down
         *
         * @method QUI.controls.usersAndGroups.Input#down
         * @return {this}
         */
        down : function()
        {
            if ( !this.$DropDown ) {
                return this;
            }

            var Active = this.$DropDown.getElement( '.hover' );

            // First Element
            if ( !Active )
            {
                this.$DropDown.getFirst().addClass( 'hover' );
                return this;
            }

            Active.removeClass( 'hover' );

            if ( !Active.getNext() )
            {
                this.down();
                return this;
            }

            Active.getNext().addClass( 'hover' );

            return this;
        },

        /**
         * select the selected user / group
         *
         * @method QUI.controls.usersAndGroups.Input#submit
         */
        submit : function()
        {
            if ( !this.$DropDown ) {
                return;
            }

            var Active = this.$DropDown.getElement( '.hover' );

            if ( Active ) {
                this.addUser( Active.get( 'data-id' ) );
            }

            this.$Input.value = '';
            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @method QUI.controls.usersAndGroups.Input#focus
         * @return {this}
         */
        focus : function()
        {
            if ( this.$Input ) {
                this.$Input.focus();
            }

            return this;
        },

        /**
         * Write the ids to the real input field
         */
        $refreshValues : function()
        {
            this.$Input.value = this.$values.join( ',' );
        },

        /**
         * event : if a user or a groupd would be destroyed
         *
         * @param {QUI.controls.groups.Entry|QUI.controls.users.Entry} Item
         */
        $onGroupUserDestroy : function(Item)
        {
            var id = false;

            switch ( Item.getType() )
            {
                case 'QUI.controls.groups.Entry':
                    id = 'g'+ Item.getGroup().getId();
                break;

                case 'QUI.controls.users.Entry':
                    id = 'u'+ Item.getUser().getId();
                break;

                default:
                    return;
            }

            this.$values = this.$values.erase( id );
            this.$refreshValues();
        }
    });

    return QUI.controls.usersAndGroups.Input;
});