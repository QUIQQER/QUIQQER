/**
 * Makes an input field to a user selection field
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/users/Input
 *
 * @require controls/Control
 * @require controls/buttons/Button
 * @require controls/users/Entry
 *
 * @event onAdd [this, userid]
 */

define('controls/users/Input', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/users/Entry',
    'Ajax',

    'css!controls/users/Input.css'

], function(QUI, QUIControl, QUIButton, UserEntry, Ajax)
{
    "use strict";

    /**
     * @class controls/users/Input
     *
     * @param {Object} options
     * @param {DOMNode Input} Input [optional] -> if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/users/Input',

        Binds : [
            'close',
            'fireSearch',
            'update'
        ],

        options : {
            max    : false,
            name   : '',
            styles : false
        },

        initialize : function(options, Input)
        {
            this.parent( options );

            this.$search = false;

            this.$Input     = null;
            this.$Elm       = false;
            this.$Container = null;
            this.$search    = false;
            this.$DropDown  = null;
            this.$disabled  = false;

            this.$Parent = Input || null;
        },

        /**
         * Return the DOMNode of the users search
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element( 'div.users-input' );

            if ( !this.$Parent )
            {
                this.$Parent = new Element('input', {
                    name : this.getAttribute('name')
                }).inject( this.$Elm );

            } else
            {
                this.$disabled = this.$Parent.disabled;

                this.$Elm.wraps( this.$Parent );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }


            this.$Parent.set( 'type', 'hidden' );
            this.$Parent.set( 'data-quiid', this.getId() );

            this.$Input = new Element('input', {
                type   : 'text',
                name   : this.$Parent.get('name') +'-search',
                styles : {
                    'float'       : 'left',
                    'margin'      : '3px 0',
                    'paddingLeft' : 20,
                    'background'  : 'url('+ URL_BIN_DIR +'10x10/search.png) no-repeat 4px center',
                    width         : 165,
                    cursor        : 'pointer',
                    display       : 'none'
                },
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
            }).inject( this.$Parent, 'before' );


            this.$DropDown = new Element('div.users-input-dropdown', {
                styles : {
                    display : 'none',
                    top  : this.$Input.getPosition().y + this.$Input.getSize().y,
                    left : this.$Input.getPosition().x
                }
            }).inject( document.body );

            this.$Container = new Element('div', {
                styles : {
                    clear   : 'both',
                    'float' : 'left'
                }
            }).inject( this.$Input, 'after' );

            // loading
            if ( this.$Parent.value === '' )
            {
                if ( !this.isDisabled() ) {
                    this.enable();
                }

                return this.$Elm;
            }

            var wasDisabled = this.isDisabled();

            this.$Parent.disabled = false;
            this.$disabled        = false;


            var i, len;
            var values = this.$Parent.value.toString().split(',');

            for ( i = 0, len = values.length; i < len; i++ )
            {
                if ( values[i] !== '' ) {
                    this.addUser( values[i] );
                }
            }

            if ( wasDisabled )
            {
                this.$Parent.disabled = true;
                this.$disabled        = true;

                // disable children
                var list = this.$getUserEntries();

                for ( var i = 0, len = list.length; i < len; i++ ) {
                    list[ i ].disable();
                }
            }

            return this.$Elm;
        },

        /**
         * updates the users search field
         */
        update : function()
        {
            if ( this.isDisabled() ) {
                return this;
            }

            if ( !this.$Container ) {
                return;
            }

            // set value
            var i, len;

            var list = this.$Container.getElements('.users-entry'),
                ids  = [];

            if ( !list.length ) {
                return;
            }

            for ( i = 0, len = list.length; i < len; i++ ) {
                ids.push( list[i].get( 'data-id' ) );
            }


            if ( ids.length == 1 )
            {
                this.$Parent.set( 'value', ids[ 0 ] );
                return;
            }

            this.$Parent.set(
                'value',
                ','+ ids.join(',') +','
            );
        },

        /**
         * fire the search
         */
        fireSearch : function()
        {
            if ( this.isDisabled() ) {
                return;
            }

            this.cancelSearch();

            this.$DropDown.set({
                html   : '<img src="'+ URL_BIN_DIR +'images/loader.gif" />',
                styles : {
                    display : '',
                    top     : this.$Input.getPosition().y + this.$Input.getSize().y,
                    left    : this.$Input.getPosition().x
                }
            });

            this.$search = this.search.delay( 500, this );
        },

        /**
         * cancel the search timeout
         */
        cancelSearch : function()
        {
            if ( this.$search ) {
                clearTimeout( this.$search );
            }
        },

        /**
         * close the users search
         */
        close : function()
        {
            if ( this.isDisabled() ) {
                return this;
            }

            this.cancelSearch();
            this.$DropDown.setStyle( 'display', 'none' );
            this.$Input.value = '';
        },

        /**
         * Add a users to the field
         *
         * @param {Integer} uid - User-ID
         */
        addUser : function(uid)
        {
            if ( this.isDisabled() ) {
                return this;
            }

            if ( !uid ) {
                return;
            }

            if ( this.$Container.getElement( '.users-entry[data-id="'+ uid +'"]') ) {
                return;
            }

            var entries = this.$Container.getElements( '.users-entry' );

            if ( this.getAttribute( 'max' ) &&
                 this.getAttribute( 'max' ) <= entries.length )
            {
                return;
            }

            var User = new UserEntry(uid, {
                events : {
                    onDestroy : this.update
                }
            }).inject( this.$Container );

            if ( this.isDisabled() ) {
                User.disable();
            }

            this.fireEvent( 'add', [ this, uid ] );
            this.update();
        },

        /**
         * trigger a users search and open a user dropdown for selection
         */
        search : function()
        {
            if ( this.isDisabled() ) {
                return this;
            }

            Ajax.get('ajax_users_search', function(result, Request)
            {
                var i, len, nam, func_mousedown, func_mouseover;

                var data     = result.data,
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
                    this.addUser(
                        event.target.get( 'data-id' )
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
                    nam = data[ i ].username.toString().replace(
                        new RegExp('('+ value +')', 'gi'),
                        '<span class="mark">$1</span>'
                    );

                    new Element('div', {
                        html        : nam +' ('+ data[ i ].id +')',
                        'class'     : 'box-sizing radius5',
                        'data-id'   : data[ i ].id,
                        'data-name' : data[ i ].username,
                        styles : {
                            'float' : 'left',
                            'clear' : 'both',
                            padding : 5,
                            cursor  : 'pointer',
                            width   : '100%'
                        },
                        events :
                        {
                            mousedown : func_mousedown,
                            mouseover : func_mouseover
                        }
                    }).inject( DropDown );
                }
            }, {
                Elm    : this,
                value  : this.$Input.value,
                params : JSON.encode({
                    order  : 'ASC',
                    limit  : 5,
                    page   : 1,
                    search : true,
                    searchSettings : {
                        userSearchString : this.$Input.value
                    }
                })
            });
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @return {this}
         */
        up : function()
        {
            if ( this.isDisabled() ) {
                return this;
            }

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
         * @return {this}
         */
        down : function()
        {
            if ( this.isDisabled() ) {
                return this;
            }

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
         * select the selected users
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
         * Disable the input field
         * no changes are possible
         */
        disable : function()
        {
            if ( this.isDisabled() ) {
                return;
            }


            this.$disabled = true;

            if ( this.$Parent ) {
                this.$Parent.disabled = true;
            }

            var self = this;

            moofx( this.$Input ).animate({
                opacity : 0
            }, {
                callback : function() {
                    self.$Input.setStyle( 'display', 'none' );
                }
            });

            // disable children
            var list = this.$getUserEntries();

            for ( var i = 0, len = list.length; i < len; i++ ) {
                list[ i ].disable();
            }
        },

        /**
         * Enable the input field if it is disabled
         * changes are possible
         */
        enable : function()
        {
            this.$disabled = false;

            if ( this.$Parent ) {
                this.$Parent.disabled = false;
            }

            this.$Input.setStyle( 'display', null );


            // enable children
            var list = this.$getUserEntries();

            for ( var i = 0, len = list.length; i < len; i++ ) {
                list[ i ].enable();
            }
        },

        /**
         * Is it disabled?
         * if disabled, no changes are possible
         */
        isDisabled : function()
        {
            if ( this.$Parent )  {
                return this.$Parent.disabled;
            }

            return this.$disabled;
        },

        /**
         * Return the UserEntry objects
         *
         * @return {Array}
         */
        $getUserEntries : function()
        {
            var list   = this.$Container.getElements('.users-entry'),
                result = [];

            for ( var i = 0, len = list.length; i < len; i++ )
            {
                result.push(
                    QUI.Controls.getById(
                        list[ i ].get('data-quiid')
                    )
                );
            }

            return result;
        }
    });
});