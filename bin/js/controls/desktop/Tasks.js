/**
 * A Tasks panel manager
 *
 * A Tasks panel can managed several Panels, Desktop's and other Controls.
 * In a Tasks panel you can insert several controls and you can switch between the Controls
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onResize [this]
 * @event onRefresh [this]
 */

define('controls/desktop/Tasks', [

    'controls/Control',
    'controls/taskbar/Bar',
    'controls/loader/Loader',

    'css!controls/desktop/Tasks.css'

], function(Control)
{
    QUI.namespace( 'controls.desktop' );

    /**
     * @class QUI.controls.desktop.Tasks
     *
     * @memberof! <global>
     */
    QUI.controls.desktop.Tasks = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.desktop.Tasks',

        Binds : [
            '$activateTask',
            '$destroyTask',
            '$normalizeTask',
            '$onTaskbarAppendChild'
        ],

        options :
        {
            name : 'taskpanel',

            // header
            header : true,      // true to create a panel header when panel is created
            title  : false,     // the title inserted into the panel's header

            // Style options:
            height : 125        // the desired height of the panel
        },

        initialize : function(options)
        {
            this.init( options );

            this.Loader = new QUI.controls.loader.Loader();

            this.$Elm        = null;
            this.$Taskbar    = null;
            this.$TaskButton = null;
            this.$Active     = null;
            this.$LastTask   = null;
        },

        /**
         * Return the data for the workspace
         *
         * @return {Object}
         */
        serialize : function()
        {
            return {
                attributes : this.getAttributes(),
                type       : this.getType(),
                bar        : this.$Taskbar.serialize()
            };
        },

        /**
         * Import the saved data
         *
         * @param {Object} data
         */
        unserialize : function(data)
        {
            this.setAttributes( data.attributes );

            if ( !this.$Elm )
            {
                this.$serialize = data;
                return this;
            }

            if ( data.bar )
            {
                this.$Taskbar.unserialize(
                    data.bar
                );
            }
        },

        /**
         * Refresh the panel
         *
         * @return {this}
         */
        refresh : function()
        {
            this.fireEvent( 'refresh', [ this ] );

            return this;
        },

        /**
         * Resize the panel
         *
         * @return {this}
         */
        resize : function()
        {
            this.$Elm.setStyles({
                height : '100%'//this.getAttribute( 'height' ) || '100%'
            });

            var TaskbarElm   = this.$Taskbar.getElm(),
                taskbar_size = TaskbarElm.getSize(),
                content_size = this.$Elm.getSize();

            this.$Container.setStyles({
                height : content_size.y - taskbar_size.y
            });

            if ( this.$Active && this.$Active.getInstance()	) {
                this.$Active.getInstance().resize();
            }

            this.fireEvent( 'resize', [ this ] );

            return this;
        },

        /**
         * Create DOMNode Element for the Tasks
         *
         * @return {DOMNode}
         */
        create : function()
        {
            if ( this.$Elm ) {
                return this.$Elm;
            }

            this.$Elm = new Element('div', {
                'data-quiid' : this.getId(),
                'class'      : 'qui-taskpanel',

                styles : {
                    height : '100%'
                }
            });

            this.$Container = new Element(
                'div.qui-taskpanel-container'
            ).inject( this.$Elm );

            this.$Taskbar = new QUI.controls.taskbar.Bar({
                name   : 'qui-taskbar-'+ this.getId(),
                type   : 'bottom',
                styles : {
                    bottom   : 0,
                    left     : 0,
                    position : 'absolute'
                },
                events : {
                    onAppendChildBegin : this.$onTaskbarAppendChild
                }
            }).inject( this.$Elm );

            this.$Taskbar.setParent( this );

            // exist serialize data
            if ( typeof this.$serialize !== 'undefined' ) {
                this.unserialize( this.$serialize );
            }

            return this.$Elm;
        },

        /**
         * Insert a control in the Taskpanel
         *
         * @param {QUI.controls.Control} Instance - A QUI Control
         */
        appendChild : function(Instance)
        {
            this.$Taskbar.appendChild(
                this.instanceToTask( Instance )
            );

            return this;
        },

        /**
         * Insert a control in the Taskpanel
         *
         * @param {QUI.controls.taskbar.Task|QUI.controls.taskbar.group} Task - A QUI task
         */
        appendTask : function(Task)
        {
            this.$Taskbar.appendChild( Task );
            return this;
        },

        /**
         * Helper method
         *
         * Activasion Tab event
         * Shows the instance from the tab
         *
         * @param {QUI.controls.taskbar.Task|QUI.controls.taskbar.Group} Task
         */
        $activateTask : function(Task)
        {
            if ( typeof Task === 'undefined' ) {
                return;
            }

            if ( this.$Active &&
                 this.$Active.getType() != 'QUI.controls.taskbar.Group' )
            {
                var _Tmp = this.$Active;
                this.$Active = Task;

                this.$normalizeTask( _Tmp );

                if ( this.$LastTask != Task &&
                     this.$LastTask != _Tmp )
                {
                    this.$LastTask = _Tmp;
                }
            }

            this.$Active = Task;

            if ( !Task.getInstance() ) {
                return;
            }

            var Instance = Task.getInstance(),
                Elm      = Instance.getElm();

            Elm.setStyle( 'display', null );


            moofx( Elm ).animate({
                left : 0
            }, {
                callback : function(time) {
                    this.resize();
                }.bind( Instance )
            });
        },

        /**
         * Helper method
         *
         * Destroy Tab event
         * Hide the instance from the tab and destroy it
         *
         * @param {QUI.controls.taskbar.Task} Task
         */
        $destroyTask : function(Task)
        {
            if ( !Task.getInstance() ) {
                return;
            }

            var Instance = Task.getInstance(),
                Elm      = Instance.getElm();

            moofx( Elm ).animate({
                left : (this.$Container.getSize().x + 10) * -1
            }, {
                callback : function(Elm)
                {
                    (function()
                    {
                        Instance.destroy();
                    }).delay( 100 );


                    if ( this.$LastTask &&
                         this.$LastTask.getId() != Task.getId() &&
                         this.$LastTask.getInstance() )
                    {
                        this.$LastTask.click();
                        return;
                    }

                    var LastTask = this.lastChild();

                    if ( LastTask.getInstance() &&
                         LastTask.getInstance().getId() != Instance.getId() )
                    {
                        LastTask.click();
                        return;
                    }

                    var FirstTask = this.firstChild();

                    if ( FirstTask.getInstance() &&
                         FirstTask.getInstance().getId() != Instance.getId() )
                    {
                        FirstTask.click();
                        return;
                    }

                }.bind( this, Elm )
            });
        },

        /**
         * Helper method
         *
         * Activasion Tab event
         * Hide the instance from the tab
         *
         * @param {QUI.controls.taskbar.Task} Task
         */
        $normalizeTask : function(Task)
        {
            if ( Task == this.$Active ) {
                return;
            }

            if ( !Task.getInstance() ) {
                return;
            }

            var Instance = Task.getInstance(),
                Elm      = Instance.getElm();


            moofx( Elm ).animate({
                left : (this.$Container.getSize().x + 10) * -1
            }, {
                callback : function(Elm)
                {
                    Elm.setStyle( 'display', 'none' );
                }.bind( this, Elm )
            });
        },

        /**
         * Return the first task children
         *
         * @return {QUI.controls.taskbar.Task|QUI.controls.taskbar.Group|false}
         */
        firstChild : function()
        {
            return this.$Taskbar.firstChild();
        },

        /**
         * Return the last task children
         *
         * @return {QUI.controls.taskbar.Task|QUI.controls.taskbar.Group|false}
         */
        lastChild : function()
        {
            return this.$Taskbar.lastChild();
        },

        /**
         * Return the taskbar object
         *
         * @return {QUI.controls.taskbar.Bar|null}
         */
        getTaskbar : function()
        {
            return this.$Taskbar;
        },

        /**
         * Create a Task for the Control
         *
         * @param {QUI.controls.Control} Instance - Instance of a QUI control
         * @return {QUI.controls.tasksbar.Task}
         */
        instanceToTask : function(Instance)
        {
            // create task
            var closeable = false,
                dragable  = false;

            if ( Instance.existAttribute( 'closeable' ) === false ||
                 Instance.existAttribute( 'closeable' ) &&
                 Instance.getAttribute( 'closeable' ) )
            {
                closeable = true;
            }

            if ( Instance.existAttribute( 'dragable' ) === false ||
                 Instance.existAttribute( 'dragable' ) &&
                 Instance.getAttribute( 'dragable' ) )
            {
                dragable = true;
            }

            var Task = Instance.getAttribute( 'Task' );

            if ( !Task )
            {
                Task = new QUI.controls.taskbar.Task( Instance );
            } else
            {
                Task.setInstance( Instance );
            }

            Task.setAttributes({
                closeable : closeable,
                dragable  : dragable
            });


            return Task;
        },

        /**
         * event on taskbar append child or taskbar group
         *
         * @param {QUI.controls.taskbar.Bar|QUI.controls.taskbar.Group} Bar
         * @param {QUI.controls.taskbar.Task} Task
         */
        $onTaskbarAppendChild : function(Bar, Task)
        {
            if ( Task.getType() === 'QUI.controls.taskbar.Group' )
            {
                Task.addEvent(
                    'onAppendChild',
                    this.$onTaskbarAppendChild
                );

                var tasks = Task.getTasks();

                for ( var i = 0, len = tasks.length; i < len; i++ ) {
                    this.$onTaskbarAppendChild( Bar, tasks[ i ] );
                }

                return;
            }

            var Instance   = Task.getInstance(),
                Taskbar    = Task.getTaskbar(),
                TaskParent = Task.getParent(),
                IParent    = false;

            if ( !Instance ) {
                return;
            }

            if ( Task.getTaskbar() ) {
                IParent = Task.getTaskbar().getParent();
            }

            /*
            if ( IParent &&
                 IParent.getId() == this.getId() )
            {
                // if the panel is already in the panel
                // then we do nothing
                if ( this.$Container
                         .getElement( '[data-quiid="'+ Instance.getId() +'"]' ) )
                {
                    return;
                }
            }
            */

            // clear old tasks parent binds
            if ( IParent && IParent.getType() == 'QUI.controls.desktop.Tasks') {
                IParent.$removeTask( Task );
            }

            Instance.setAttribute( 'height', this.$Container.getSize().y );
            Instance.setAttribute( 'collapsible', false );

            Instance.inject( this.$Container );
            Instance.setParent( this );

            Instance.getElm().setStyles({
                position : 'absolute',
                top      : 0,
                left     : (this.$Container.getSize().x + 10) * -1
            });

            // not the best solution
            Instance.__destroy = Instance.destroy;
            Instance.destroy   = this.$onInstanceDestroy.bind( this, Instance );

            // delete the own task destroy event
            // so the tasks panel can destroy the instance
            Task.removeEvent( 'onDestroy', Task.$onDestroy );

            if ( Taskbar )
            {
                Task.removeEvent( 'refresh', Taskbar.$onTaskRefresh );
                Task.removeEvent( 'destroy', Taskbar.$onTaskDestroy );
                Task.removeEvent( 'click', Taskbar.$onTaskClick );
            }

            // add the new events of the panel to the task
            Task.addEvents({
                onActivate : this.$activateTask,
                onDestroy  : this.$destroyTask
            });

            if ( !TaskParent ||
                 TaskParent &&
                 TaskParent.getType() !== 'QUI.controls.taskbar.Group' )
            {
                (function()
                {
                    Task.click();
                }).delay( 100, [ this ] );
            }
        },

        /**
         * Remove a task from the tasks panel and remove all binded events
         *
         * @param {QUI.controls.taskbar.Task} Task
         */
        $removeTask : function(Task)
        {
            if ( this.$LastTask &&
                 this.$LastTask.getId() == Task.getId() )
            {
                this.$LastTask = null;
            }

            Task.removeEvents({
                onActivate : this.$activateTask,
                onDestroy  : this.$destroyTask
            });

            if ( Task.isActive() )
            {
                this.$Active = null;

                if ( this.$LastTask )
                {
                    this.$LastTask.click();
                } else
                {
                    this.lastChild().click();
                }
            }

            this.getTaskbar().removeChild( Task );
        },

        /**
         * if the instance have been destroyed
         */
        $onInstanceDestroy : function(Instance)
        {
            Instance.__destroy();

            var Task = Instance.getAttribute( 'Task' );

            if ( Task && Task.getElm() ) {
                Task.destroy();
            }
        }

    });

    return QUI.controls.desktop.Tasks;
});