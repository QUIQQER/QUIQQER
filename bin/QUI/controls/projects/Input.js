/**
 * Makes an input field to a project selection field
 *
 * @module controls/projects/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/utils/Elements
 * @require controls/projects/project/Entry
 * @require Ajax
 * @require Locale
 * @require Projects
 * @require css!controls/projects/Input.css
 *
 * @event onAdd [ this, project, lang ]
 * @event onChange [ this ]
 */

define('controls/projects/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/utils/Elements',
    'controls/projects/project/Entry',
    'Ajax',
    'Locale',
    'Projects',

    'css!controls/projects/Input.css'

], function(QUIControl, QUIButton, ElementUtils, ProjectEntry, Ajax, Locale, Projects)
{
    "use strict";

    /**
     * @class controls/projects/Input
     *
     * @param {Object} options
     * @param {Element} Input [optional] -> if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/Input',

        Binds : [
            'close',
            'fireSearch',
            'refresh'
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
         * @return {HTMLElement} Main DOM-Node Element
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'      : 'projects-input',
                'data-quiid' : this.getId()
            });

            if ( !this.$Parent )
            {
                this.$Parent = new Element('input', {
                    name : this.getAttribute('name')
                }).inject( this.$Elm );

            } else
            {
                this.$Elm.wraps( this.$Parent );

                this.$Elm.setStyle(
                    'width',
                    this.$Parent.getStyle( 'width' )
                );
            }

            this.$Parent.set( 'data-quiid', this.getId() );

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }


            this.$Parent.set('type', 'hidden' );

            this.$Input = new Element('input', {
                'class' : 'projects-input-input box',
                type   : 'text',
                name   : this.$Parent.get('name') +'-search',
                styles : {
                   'background'  : 'url('+ URL_BIN_DIR +'10x10/search.png) no-repeat 4px center'
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
                'class' : 'projects-input-container'
            }).inject( this.$Input, 'after' );

            this.$Container.setStyle(
                'width',
                this.$Parent.getStyle( 'width' )
            );

            // loading
            if ( this.$Parent.value === '' ) {
                return this.$Elm;
            }

            var i, len;
            var values = JSON.decode( this.$Parent.value.toString() );

            for ( i = 0, len = values.length; i < len; i++ )
            {
                if ( "project" in values[ i ] && "lang" in values[ i ] ) {
                    this.addProject( values[ i ].project, values[ i ].lang );
                }
            }

            return this.$Elm;
        },

        /**
         * updates the projects search field
         *
         * @param {Function} [callback] - optional, callback function on finish
         * @method controls/projects/Input#refresh
         */
        refresh : function(callback)
        {
            if ( !this.$Container )
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                return;
            }

            // set value
            var list = this.$Container.getElements( '.project-entry' ),
                data = [];

            for ( var i = 0, len = list.length; i < len; i++ )
            {
                data.push({
                    project : list[i].get( 'data-project' ),
                    lang    : list[i].get( 'data-lang' )
                });
            }

            this.$Parent.set( 'value', JSON.encode( data ) );

            if ( typeof callback !== 'undefined' ) {
                callback();
            }
        },

        /**
         * Return the project list
         *
         * @return {Array}
         */
        getProjects : function()
        {
            var i, len, Project;
            var result = [],
                list   = this.$Container.getElements( '.project-entry' );

            for ( i = 0, len = list.length; i < len; i++ )
            {
                Project = Projects.get(
                    list[ i ].get( 'data-project' ),
                    list[ i ].get( 'data-lang' )
                );

                result.push( Project );
            }

            return result;
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
                    left    : this.$Input.getPosition().x,
                    zIndex  : ElementUtils.getComputedZIndex( this.$Input )
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
         * @param {String} [lang] - optional, Project language
         */
        addProject : function(project, lang)
        {
            if ( !project ) {
                return;
            }

            var Container = this.$Container;

            if ( this.getAttribute( 'multible' ) === false )
            {
                // wenn multible = false
                // dann leeren und nur ein projekt zu lassen
                this.$Container.set( 'html', '' );
            }

            if ( Container.getElement( '.project-entry[data-project="'+ project +'"]') ) {
                return;
            }

            var entries = Container.getElements( '.project-entry' ),
                max     = this.getAttribute( 'max' );

            if ( max && max <= entries.length ) {
                return;
            }

            var self = this;

            new ProjectEntry(project, lang, {
                styles : {
                    width : '100%'
                },
                events :
                {
                    onDestroy : function()
                    {
                        (function()
                        {
                            self.refresh(function()
                            {
                                self.$Parent.fireEvent( 'change', [ this ] );
                                self.fireEvent( 'change', [ this ] );
                            });
                        }).delay( 250 );
                    }
                }
            }).inject( Container );

            this.fireEvent( 'add', [ this, project, lang ] );

            this.refresh(function()
            {
                self.$Parent.fireEvent( 'change', [ this ] );
                self.fireEvent( 'change', [ this ] );
            });
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
                        html   : Locale.get( 'quiqqer/system', 'projects.project.input.no.results' ),
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
         * @return {Object} this (controls/projects/Input)
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
         * @return {Object} this (controls/projects/Input)
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
         * @return {Object} this (controls/projects/Input)
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
