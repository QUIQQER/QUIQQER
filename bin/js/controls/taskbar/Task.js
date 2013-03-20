/**
 * A task for the taskbar
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/taskbar/Task
 * @package com.pcsg.qui.js.controls.taskbar
 * @namespace QUI.controls.taskbar
 *
 * @event onClick [this, DOMEvent]
 * @event onActivate [this]
 * @event onNormalize [this]
 * @event onRefresh [this]
 * @event onFocus [this, DOMEvent]
 * @event onBlur [this, DOMEvent]
 * @event onClose [this, DOMEvent]
 * @event onContextMenu [this, DOMEvent]
 * @event onHighlight [this]
 * @event onSelect [this]
 *
 * @require classes/utils/DragDrop
 * @require controls/Control
 */

define('controls/taskbar/Task', [

    'controls/Control',
    'classes/utils/DragDrop',

    'css!controls/taskbar/Task.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.taskbar' );

    /**
     * @class QUI.controls.buttons.Button
     *
     * @param {QUI.controls.Control} Instance - Control for the task
     * @param {Object} options                - QDOM params
     *
     * @memberof! <global>
     */
    QUI.controls.taskbar.Task = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.taskbar.Task',

        Binds : [
            'close',
            '$onDestroy'
        ],

        options : {
            name      : 'qui-task',
            icon      : false,
            text      : '',
            cssClass  : '',
            closeable : true,
            dragable  : true
        },

        initialize : function(Instance, options)
        {
            this.$Instance = Instance || null;
            this.$Elm      = null;

            this.addEvents({
                'onDestroy' : this.$onDestroy
            });

            if ( typeof Instance === 'undefined' ) {
                return;
            }


            Instance.setAttribute( 'Task', this );

            // Instance events
            Instance.addEvent('onRefresh', function(Instance) {
                Instance.getAttribute( 'Task' ).refresh();
            });

            Instance.addEvent('onDestroy', function(Instance)
            {
                var Task = Instance.getAttribute( 'Task' );

                Task.$Instance = null;
                Task.destroy();
            });

            this.init( options );
        },

        /**
         * Return the save date, eq for the workspace
         *
         * @return {Object}
         */
        serialize : function()
        {
            return {
                attributes : this.getAttributes(),
                type       : this.getType(),
                instance   : this.getInstance() ? this.getInstance().serialize() : ''
            };
        },

        /**
         * Import the saved data
         *
         * @param {Object} data
         * @return {this}
         */
        unserialize : function(data, onfinish)
        {
            this.setAttributes( data.attributes );

            var instance = data.instance;

            if ( !instance ) {
                return this;
            }

            QUI.Controls.getByType(instance.type, function(Modul)
            {
                var Instance = new Modul( data.instance );
                    Instance.unserialize( data.instance );

                this.initialize( Instance, data.attributes );

            }.bind( this ));
        },

        /**
         * Refresh the task display
         */
        refresh : function()
        {
            if ( !this.$Elm )
            {
                this.fireEvent( 'refresh', [ this ] );
                return;
            }

            var Icon = this.$Elm.getElement( '.qui-task-icon' ),
                Text = this.$Elm.getElement( '.qui-task-text' );

            if ( this.getIcon() )
            {
                Icon.setStyle(
                    'background-image',
                    'url('+ this.getIcon() +')'
                );
            }

            if ( this.getTitle() )
            {
                var title = this.getTitle();
                    title = title.substring( 0, 19 );

                if ( title.length > 19 ) {
                    title = title +'...';
                }

                Text.set( 'html', title );
            }

            this.fireEvent( 'refresh', [ this ] );
        },

        /**
         * Return the instance icon
         *
         * @return {String|false}
         */
        getIcon : function()
        {
            if ( !this.getInstance() ) {
                return '';
            }

            return this.getInstance().getAttribute( 'icon' );
        },

        /**
         * Return the instance title
         *
         * @return {String|false}
         */
        getTitle : function()
        {
            if ( !this.getInstance() ) {
                return '';
            }

            return this.getInstance().getAttribute( 'title' );
        },

        /**
         * Return the binded instance to the task
         *
         * @return {QUI.controls.Control}
         */
        getInstance : function()
        {
            return this.$Instance;
        },

        /**
         * Set / Bind an instance to the task
         *
         * @param {QUI.controls.Control} Instance
         */
        setInstance : function(Instance)
        {
            this.$Instance = Instance;
        },

        /**
         * Return the Taskbar object
         *
         * @return {QUI
         */
        getTaskbar : function()
        {
            var Taskbar = this.getParent();

            if ( typeOf( Taskbar ) == "QUI.controls.taskbar.Group" ) {
                Taskbar = Taskbar.getParent();
            }

            return Taskbar;
        },

        /**
         * Return the DOMNode
         *
         * @method QUI.controls.buttons.Button#getElm
         * @return {DOMNode}
         */
        create : function()
        {
            if ( this.$Elm ) {
                return this.$Elm;
            }

            this.$Elm = new Element('div', {
                'class' : 'qui-task radius5 box',
                html    : '<span class="qui-task-icon"></span>' +
                          '<span class="qui-task-text"></span>',
                styles : {
                    outline: 'none'
                },
                tabindex : -1,
                events   :
                {
                    click : function(event)
                    {
                        this.click( event );
                    }.bind( this ),

                    focus : function(event)
                    {
                        this.fireEvent( 'focus', [ this, event ] );
                    }.bind( this ),

                    blur : function(event)
                    {
                        this.fireEvent( 'blur', [ this, event ] );
                    }.bind( this ),

                    contextmenu : function(event)
                    {
                        this.fireEvent( 'contextMenu', [ this, event ] );

                        event.stop();
                    }.bind( this )
                }
            });

            if ( this.getAttribute( 'dragable' ) )
            {
                this.$_enter = null;

                new QUI.classes.utils.DragDrop(this.$Elm, {
                    dropables : [ '.qui-taskgroup', '.qui-taskbar' ],
                    cssClass  : 'radius5',
                    events    :
                    {
                        onEnter : function(Element, Droppable)
                        {
                            if ( !Droppable ) {
                                return;
                            }

                            var quiid = Droppable.get( 'data-quiid' );

                            if ( !quiid ) {
                                return;
                            }

                            QUI.Controls.getById( quiid ).highlight();

                        },

                        onLeave : function(Element, Droppable)
                        {
                            if ( !Droppable ) {
                                return;
                            }

                            var quiid = Droppable.get( 'data-quiid' );

                            if ( !quiid ) {
                                return;
                            }

                            QUI.Controls.getById( quiid ).normalize();
                        },

                        onDrop : function(Element, Droppable, event)
                        {
                            if ( !Droppable ) {
                                return;
                            }

                            var quiid = Droppable.get( 'data-quiid' );

                            if ( !quiid ) {
                                return;
                            }

                            var Bar = QUI.Controls.getById( quiid );

                            Bar.normalize();
                            Bar.appendChild( this );

                        }.bind( this )
                    }
                });
            }


            if ( this.getAttribute( 'cssClass' ) ) {
                this.$Elm.addClass( this.getAttribute( 'cssClass' ) );
            }

            if ( this.getAttribute('closeable') )
            {
                new Element('div', {
                    'class' : 'qui-task-close',
                    'html'  : '<span>x</span>',
                    events  : {
                        click : this.close
                    }
                }).inject( this.$Elm );
            }

            // exist serialize data?
            if ( typeof this.$serialize !== 'undefined' ) {
                this.unserialize( this.$serialize );
            }

            this.refresh();

            return this.$Elm;
        },

        /**
         * Set the Tab active
         *
         * @return {this}
         */
        activate : function()
        {
            if ( this.isActive() || !this.$Elm ) {
                return this;
            }

            this.$Elm.addClass( 'active' );
            this.fireEvent( 'activate', [ this ] );

            return this;
        },

        /**
         * Normalize the tab
         *
         * @return {this}
         */
        normalize : function()
        {
            if ( this.$Elm )
            {
                this.$Elm.removeClass( 'active' );
                this.$Elm.removeClass( 'highlight' );
                this.$Elm.removeClass( 'select' );

                this.$Elm.setStyle( 'display', null );
            }

            this.fireEvent( 'normalize', [ this ] );

            return this;
        },

        /**
         * Hide the task tab
         *
         * @return {this}
         */
        hide : function()
        {
            if ( this.$Elm ) {
                this.$Elm.setStyle( 'display', 'none' );
            }

            return this;
        },

        /**
         * Return true if the Task is active
         *
         * @return {Bool}
         */
        isActive : function()
        {
            if ( !this.$Elm ) {
                return false;
            }

            return this.$Elm.hasClass( 'active' );
        },

        /**
         * Trigger the click event
         *
         * @return {this}
         */
        click : function(event)
        {
            this.fireEvent( 'click', [ this, event ] );

            if ( !this.isActive() ) {
                this.activate();
            }

            return this;
        },

        /**
         * Trigger the close event
         *
         * @return {this}
         */
        close : function(event)
        {
            this.fireEvent( 'close', [ this, event ] );
            this.destroy();

            return this;
        },

        /**
         * Set the focus to the task DOMNode element
         *
         * @return {this}
         */
        focus : function()
        {
            if ( this.$Elm ) {
                this.$Elm.focus();
            }

            return this;
        },

        /**
         * Highlight the Task
         *
         * @return {this}
         */
        highlight : function()
        {
            if ( this.$Elm ) {
                this.$Elm.addClass( 'highlight' );
            }

            this.fireEvent( 'highlight', [ this ] );

            return this;
        },

        /**
         * Select the Task
         *
         * @return {this}
         */
        select : function()
        {
            if ( this.$Elm ) {
                this.$Elm.addClass( 'select' );
            }

            this.fireEvent( 'select', [ this ] );

            return this;
        },

        /**
         * Is the Task selected?
         *
         * @return {Bool}
         */
        isSelected : function()
        {
            if ( this.$Elm ) {
                return this.$Elm.hasClass( 'select' );
            }

            return false;
        },

        /**
         * Unselect the Task
         *
         * @return {this}
         */
        unselect : function()
        {
            if ( this.$Elm ) {
                this.$Elm.removeClass( 'select' );
            }

            this.fireEvent( 'unselect', [ this ] );

            return this;
        },

        /**
         * on destroy task event
         */
        $onDestroy : function()
        {
            if ( this.getInstance() ) {
                this.getInstance().destroy();
            }

            this.$Instance = null;
        }
    });

    return QUI.controls.taskbar.Task;
});
