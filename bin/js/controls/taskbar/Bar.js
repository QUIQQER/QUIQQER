/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/taskbar/Bar
 * @package com.pcsg.qui.js.controls.taskbar
 * @namespace QUI.controls.taskbar
 *
 * @event onAppendChild [
 *      {QUI.controls.taskbar.Bar},
 *      {QUI.controls.taskbar.Task}
 * ]
 * @event onAppendChildBegin [
 *      {QUI.controls.taskbar.Bar},
 *      {QUI.controls.taskbar.Task}
 * ]
 */

define('controls/taskbar/Bar', [

    'controls/Control',
    'controls/contextmenu/Item',
    'controls/buttons/Button',
    'controls/taskbar/Task',
    'controls/taskbar/Group',

    'css!controls/taskbar/Bar.css'

],
function(Control)
{
    QUI.namespace( 'controls.taskbar' );

    /**
     * @class QUI.controls.taskbar.Bar
     *
     * @param {Object} options
     */
    QUI.controls.taskbar.Bar = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.taskbar.Bar',

        Binds : [
            '$onTaskRefresh',
            '$onTaskDestroy',
            '$onTaskClick'
        ],

        options : {
            width    : false,
            styles   : false,
            position : 'bottom' // bottom or top
        },

        initialize : function(options)
        {
            this.$Elm        = null;
            this.$TaskButton = null;
            this.$tasks      = [];
            this.$Active     = null;

            this.init( options );
        },

        /**
         * Return the save date, eq for the workspace
         *
         * @return {Object}
         */
        serialize : function()
        {
            var tasks = [];

            for ( var i = 0, len = this.$tasks.length; i < len; i++ ) {
                tasks.push( this.$tasks[ i ].serialize() );
            }

            return {
                attributes : this.getAttributes(),
                type       : this.getType(),
                tasks      : tasks
            };
        },

        /**
         * Import the saved data
         *
         * @param {Object} data
         * @return {this}
         */
        unserialize : function(data)
        {
            this.setAttributes( data.attributes );

            if ( !this.$Elm )
            {
                this.$serialize = data;
                return this;
            }

            var tasks = data.tasks;

            if ( !tasks ) {
                return this;
            }

            var i, len, Task;

            var importInit = function( Task )
            {
                this.appendChild( Task );
            }.bind( this );

            for ( i = 0, len = tasks.length; i < len; i++ )
            {
                if ( tasks[ i ].type === 'QUI.controls.taskbar.Group' )
                {
                    Task = new QUI.controls.taskbar.Group();
                } else
                {
                    Task = new QUI.controls.taskbar.Task();
                }

                Task.addEvent('onInit', importInit);
                Task.unserialize( tasks[i] );
            }
        },

        /**
         * Create the DOMNode for the Bar
         *
         * @method QUI.controls.taskbar.Bar#create
         * @return {DOMNode}
         */
        create : function()
        {
            if ( this.$Elm )
            {
                this.refresh();
                return this.$Elm;
            }

            this.$Elm = new Element('div', {
                'class'      : 'qui-taskbar box',
                'data-quiid' : this.getId()
            });

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }


            if ( this.getAttribute( 'position' ) == 'bottom' ) {
                this.$Elm.addClass( 'qui-taskbar-bottom' );
            }

            if ( this.getAttribute( 'position' ) == 'top' ) {
                this.$Elm.addClass( 'qui-taskbar-top' );
            }

            this.$TaskButton = new QUI.controls.buttons.Button({
                name   : 'qui-taskbar-btn-'+ this.getId(),
                styles : {
                    width  : 24,
                    height : 24
                }
            }).inject( this.$Elm );

            this.$TaskButton.disable();


            // exist serialize data
            if ( typeof this.$serialize !== 'undefined' ) {
                this.unserialize( this.$serialize );
            }


            return this.$Elm;
        },

        /**
         * refresh?
         */
        refresh : function()
        {

        },

        /**
         * Append a child to the Taskbar
         *
         * @param {QUI.controls.taskbar.Task|QUI.controls.taskbar.Group} Task
         */
        appendChild : function(Task)
        {
            this.fireEvent( 'appendChildBegin', [ this, Task ] );


            if ( Task.getParent() &&
                 Task.getParent().getType() === 'QUI.controls.taskbar.Bar' )
            {
                var Parent = Task.getParent();

                Task.removeEvent( 'refresh', Parent.$onTaskRefresh );
                Task.removeEvent( 'destroy', Parent.$onTaskDestroy );
                Task.removeEvent( 'click', Parent.$onTaskClick );
            }

            Task.setParent( this );

            Task.addEvent( 'onRefresh', this.$onTaskRefresh );
            Task.addEvent( 'onDestroy', this.$onTaskDestroy );
            Task.addEvent( 'onClick', this.$onTaskClick );

            Task.inject( this.$Elm );

            this.$tasks.push( Task );


            this.$TaskButton.appendChild(
                new QUI.controls.contextmenu.Item({
                    icon   : Task.getIcon(),
                    text   : Task.getTitle(),
                    name   : Task.getId(),
                    Task   : Task,
                    events :
                    {
                        onMouseDown : function(Item, event) {
                            Item.getAttribute( 'Task' ).click();
                        }
                    }
                })
            );

            this.$TaskButton.enable();

            this.fireEvent( 'appendChild', [ this, Task ] );

            return this;
        },

        /**
         * Return the first task children
         *
         * @return {QUI.controls.taskbar.Task|QUI.controls.taskbar.Group|false}
         */
        firstChild : function()
        {
            if ( this.$tasks[ 0 ] ) {
                return this.$tasks[ 0 ];
            }

            return false;
        },

        /**
         * Return the last task children
         *
         * @return {QUI.controls.taskbar.Task|QUI.controls.taskbar.Group|false}
         */
        lastChild : function()
        {
            if ( this.$tasks.length ) {
                return this.$tasks[ this.$tasks.length - 1  ];
            }

            return false;
        },

        /**
         * Remove a task from the bar
         *
         * @return {QUI.controls.taskbar.Task}
         */
        removeChild : function(Task)
        {
            var Child = false;

            if ( this.$TaskButton )
            {
                Child = this.$TaskButton.getContextMenu().getChildren(
                    Task.getId()
                );
            }

            Task.removeEvent( 'refresh', this.$onTaskRefresh );

            if ( Child ) {
                Child.destroy();
            }
        },

        /**
         * highlight the toolbar
         *
         * @return {this}
         */
        highlight : function()
        {
            this.$Elm.addClass( 'highlight' );

            return this;
        },

        /**
         * normalize the toolbar
         *
         * @return {this}
         */
        normalize : function()
        {
            this.$Elm.removeClass( 'highlight' );

            return this;
        },

        /**
         * Refresh the context menu item of the task, if the task refresh
         *
         * @param {QUI.controls.taskbar.Task}
         */
        $onTaskRefresh : function(Task)
        {
            var Child = false;

            if ( this.$TaskButton )
            {
                Child = this.$TaskButton.getContextMenu().getChildren(
                    Task.getId()
                );
            }

            if ( !Child ) {
                return;
            }

            Child.setAttribute( 'icon', Task.getIcon() );
            Child.setAttribute( 'text', Task.getTitle() );
        },

        /**
         * Refresh the context menu if the task would be destroyed
         *
         * @param {QUI.controls.taskbar.Task} Task
         */
        $onTaskDestroy : function(Task)
        {
            this.removeChild( Task );
        },

        /**
         * event task click
         *
         * @param {QUI.controls.taskbar.Task} Task
         * @param {DOMEvent} event
         */
        $onTaskClick : function(Task, event)
        {
            if ( this.$Active == Task ) {
                return;
            }

            if ( this.$Active ) {
                this.$Active.normalize();
            }

            this.$Active = Task;
            this.$Active.activate();
        }
    });

    return QUI.controls.taskbar.Bar;
});