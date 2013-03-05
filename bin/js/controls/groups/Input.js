/**
 * Makes an input field to a group selection field
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/groups/Input
 * @package com.pcsg.qui.js.controls.groups
 * @namespace QUI.controls.groups
 *
 * @require controls/Control
 * @require controls/buttons/Button
 * @require controls/groups/Entry
 * @require controls/groups/sitemap/Window
 *
 * @event onAdd [this, groupid]
 */

define('controls/groups/Input', [

    'controls/Control',
    'controls/buttons/Button',
    'controls/groups/Entry',
    'controls/groups/sitemap/Window',

    'css!controls/groups/Input.css'

], function(QUI_Control)
{
    QUI.namespace( 'controls.groups' );

    /**
     * @class QUI.controls.groups.Input
     *
     * @param {Object} options
     * @param {DOMNode Input} Input [optional] -> if no input given, one would be created
     */
    QUI.controls.groups.Input = new Class({

        Implements : [ QUI_Control ],
        Type       : 'QUI.controls.groups.Input',

        Binds : [
            'close',
            'fireSearch',
            'update'
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

            this.$search = false;

            this.$Input     = null;
            this.$Elm       = false;
            this.$Container = null;
            this.$search    = false;
            this.$DropDown  = null;

            this.$Parent = Input || null;
        },

        /**
         * Return the DOMNode of the group search
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element( 'div.group-input' );

            if ( !this.$Parent )
            {
                this.$Parent = new Element('input', {
                    name : this.getAttribute('name')
                }).inject( this.$Elm );
            } else
            {
                this.$Elm.wraps( this.$Parent );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }


            this.$Parent.set('type', 'hidden' );

            // sitemap button
            new QUI.controls.buttons.Button({
                name    : 'groupSitemapBtn',
                image   : URL_BIN_DIR +'16x16/add.png',
                styles  : {
                    marginTop : 4
                },
                Control : this,
                events  :
                {
                    onClick : function(Btn, event)
                    {
                        var Control = Btn.getAttribute('Control');

                        new QUI.controls.groups.sitemap.Window({
                            multible : Control.getAttribute('multible'),
                            events   :
                            {
                                onSubmit : function(value)
                                {
                                    for ( var i = 0, len = values.length; i < len; i++ ) {
                                        this.addGroup( values[i] );
                                    }
                                }.bind( this )
                            }
                        }).create();
                    }
                }
            }).inject( this.$Parent, 'before' );

            this.$Input = new Element('input', {
                type   : 'text',
                name   : this.$Parent.get('name') +'-search',
                styles : {
                    'float'       : 'left',
                    'margin'      : '3px 0',
                    'paddingLeft' : 20,
                    'background'  : 'url('+ URL_BIN_DIR +'10x10/search.png) no-repeat 4px center',
                    width         : 165,
                    cursor        : 'pointer'
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


            this.$DropDown = new Element('div.group-input-dropdown', {
                styles : {
                    display : 'none',
                    top  : this.$Input.getPosition().y + this.$Input.getSize().y,
                    left : this.$Input.getPosition().x
                }
            }).inject( document.body );

            this.$Container = new Element('div', {
                styles : {
                    'float' : 'left',
                    margin  : '0 0 0 10px',
                    width   : 400
                }
            }).inject( this.$Input, 'after' );

            // loading
            if ( this.$Parent.value === '' ) {
                return this.$Elm;
            }

            var i, len;
            var values = this.$Parent.value.toString().split(',');

            for ( i = 0, len = values.length; i < len; i++ )
            {
                if ( values[i] !== '' ) {
                    this.addGroup( values[i] );
                }
            }

            return this.$Elm;
        },

        /**
         * updates the group search field
         */
        update : function()
        {
            if ( !this.$Container ) {
                return;
            }

            // set value
            var i, len;

            var list = this.$Container.getElements('.group-entry'),
                ids  = [];

            for ( i = 0, len = list.length; i < len; i++ ) {
                ids.push( list[i].get('data-id') );
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
         * close the group search
         */
        close : function()
        {
            this.cancelSearch();
            this.$DropDown.setStyle( 'display', 'none' );
            this.$Input.value = '';
        },

        /**
         * Add a group to the field
         *
         * @param {Integer} gid - Group-ID
         */
        addGroup : function(gid)
        {
            if ( !gid ) {
                return;
            }

            if ( this.$Container.getElement( '.group-entry[data-id="'+ gid +'"]') ) {
                return;
            }

            var entries = this.$Container.getElements( '.group-entry' );

            if ( this.getAttribute( 'max' ) &&
                 this.getAttribute( 'max' ) <= entries.length )
            {
                return;
            }

            new QUI.controls.groups.Entry(gid, {
                events : {
                    onDestroy : this.update
                }
            }).inject( this.$Container );

            this.fireEvent( 'add', [ this, gid ] );
            this.update();
        },

        /**
         * trigger a group search and open a group dropdown for selection
         */
        search : function()
        {
            QUI.Ajax.get('ajax_groups_search', function(result, Ajax)
            {
                var i, len, nam, func_mousedown, func_mouseover;

                var data     = result.data,
                    value    = Ajax.getAttribute( 'value' ),
                    Elm      = Ajax.getAttribute( 'Elm' ),
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
                    this.addGroup(
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
                    nam = data[i].name.toString().replace(
                        new RegExp('('+ value +')', 'gi'),
                        '<span class="mark">$1</span>'
                    );

                    new Element('div', {
                        html   : nam +' ('+ data[ i ].id +')',
                        'class'     : 'box-sizing radius5',
                        'data-id'   : data[ i ].id,
                        'data-name' : data[ i ].name,
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
                    field  : 'name',
                    order  : 'ASC',
                    limit  : 5,
                    page   : 1,
                    search : this.$Input.value
                })
            });
        },

        /**
         * keyup - group dropdown selection one step up
         *
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
         * keydown - group dropdown selection one step down
         *
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
         * select the selected group
         */
        submit : function()
        {
            if ( !this.$DropDown ) {
                return;
            }

            var Active = this.$DropDown.getElement( '.hover' );

            if ( Active ) {
                this.addGroup( Active.get( 'data-id' ) );
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
        }
    });

    return QUI.controls.groups.Input;
});