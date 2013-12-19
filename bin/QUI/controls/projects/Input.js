/**
 * Makes an input field to a project selection field
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/Input
 * @package com.pcsg.qui.js.controls.projects
 * @namespace QUI.controls.projects
 *
 * @require controls/Control
 * @require controls/buttons/Button
 * @require controls/projects/Entry
 *
 * @event onAdd [this, project, lang]
 */

define('controls/projects/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/projects/project/Entry',
    'Ajax',

    'css!controls/projects/Input.css'

], function(QUIControl, QUIButton, ProjectEntry, Ajax)
{
    "use strict";

    /**
     * @class controls/projects/Input
     *
     * @param {Object} options
     * @param {DOMNode Input} Input [optional] -> if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/Input',

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
            this.parent( options );

            this.$search = false;

            this.$Input     = null;
            this.$Elm       = false;
            this.$Container = null;
            this.$search    = false;
            this.$DropDown  = null;

            this.$Parent = Input || null;
        },

        /**
         * Return the DOMNode of the projects search
         *
         * @method controls/projects/Input#create
         * @return {DOMNode} Main DOM-Node Element
         */
        create : function()
        {
            this.$Elm = new Element( 'div.projects-input' );

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


            this.$DropDown = new Element('div.projects-input-dropdown', {
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
                    this.addProject( values[i] );
                }
            }

            return this.$Elm;
        },

        /**
         * updates the projects search field
         *
         * @method controls/projects/Input#update
         */
        update : function()
        {
            if ( !this.$Container ) {
                return;
            }

            // set value
            var i, len;

            var list = this.$Container.getElements( '.projects-entry' ),
                ids  = [];

            for ( i = 0, len = list.length; i < len; i++ ) {
                ids.push( list[i].get( 'data-project' ) );
            }

            this.$Parent.set(
                'value',
                ','+ ids.join(',') +','
            );
        },

        /**
         * fire the search
         *
         * @method controls/projects/Input#fireSearch
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
         *
         * @method controls/projects/Input#cancelSearch
         */
        cancelSearch : function()
        {
            if ( this.$search ) {
                clearTimeout( this.$search );
            }
        },

        /**
         * close the projects search
         *
         * @method controls/projects/Input#close
         */
        close : function()
        {
            this.cancelSearch();
            this.$DropDown.setStyle( 'display', 'none' );
            this.$Input.value = '';
        },

        /**
         * Add a projects to the field
         *
         * @method controls/projects/Input#addProject
         * @param {String} project - Project name
         * @param {String} lang - [optional] Project language
         */
        addProject : function(project, lang)
        {
            if ( !project ) {
                return;
            }

            if ( this.$Container.getElement( '.projects-entry[data-id="'+ project +'"]') ) {
                return;
            }

            var entries = this.$Container.getElements( '.projects-entry' );

            if ( this.getAttribute( 'max' ) &&
                 this.getAttribute( 'max' ) <= entries.length )
            {
                return;
            }

            new ProjectEntry(project, {
                events : {
                    onDestroy : this.update
                }
            }).inject( this.$Container );

            this.fireEvent( 'add', [ this, project ] );
            this.update();
        },

        /**
         * trigger a projects search and open a projects dropdown for selection
         *
         * @method controls/projects/Input#search
         */
        search : function()
        {
            Ajax.get('ajax_project_search', function(result, Request)
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
                    this.addProject(
                        event.target.get( 'data-project' ),
                        event.target.get( 'data-lang' )
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
                    nam = data[ i ].project.toString().replace(
                        new RegExp('('+ value +')', 'gi'),
                        '<span class="mark">$1</span>'
                    );

                    new Element('div', {
                        html    : nam +' ('+ data[ i ].lang +')',
                        'class' : 'box-sizing radius5',
                        'data-project' : data[ i ].project,
                        'data-lang'    : data[ i ].lang,
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
                    search : this.$Input.value
                })
            });
        },

        /**
         * keyup - projects dropdown selection one step up
         *
         * @method controls/projects/Input#up
         * @return {this} self
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
         * keydown - projects dropdown selection one step down
         *
         * @method controls/projects/Input#down
         * @return {this} self
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
         * select the selected projects
         *
         * @method controls/projects/Input#submit
         */
        submit : function()
        {
            if ( !this.$DropDown ) {
                return;
            }

            var Active = this.$DropDown.getElement( '.hover' );

            if ( Active )
            {
                this.addProject(
                    Active.get( 'data-project' ),
                    Active.get( 'data-lang' )
                );
            }

            this.$Input.value = '';
            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @method controls/projects/Input#focus
         * @return {this} self
         */
        focus : function()
        {
            if ( this.$Input ) {
                this.$Input.focus();
            }

            return this;
        }
    });
});