/*
 ---

 name: Taskbar

 script: Taskbar.js

 description: MUI.Taskbar - Implements the taskbar. Enables window minimize.

 copyright: (c) 2011 Contributors in (/AUTHORS.txt).

 license: MIT-style license in (/MIT-LICENSE.txt).

 requires:
 - MochaUI/MUI
 - MochaUI/MUI.Desktop

 provides: [MUI.Taskbar]


 ** Events **

 Tab Events:
 - tabClose
 - tabCreated

 ...

 @todo new implementation with qui controls
 */

define('mocha/Controls/taskbar/taskbar', [

    'mocha/Controls/desktop/desktop',
    'mocha/Controls/window/window',
    'controls/contextmenu/Item',
    'controls/buttons/Button',
    'controls/taskbar/Task',
    'controls/taskbar/Group'

],
function()
{
    MUI.Taskbar = (MUI.Taskbar || new NamedClass('MUI.Taskbar', {}));

    MUI.Taskbar.implement({

        Implements: [Events, Options],
        css       : ['{theme}css/taskbar.css'],

        options: {
            id:          'taskbar',
            container:   null,
            drawOnInit:  true,

            useControls: true,            // Toggles autohide and taskbar placement controls.
            position:    'bottom',        // Position the taskbar starts in, top or bottom.
            visible:     true,            // is the taskbar visible
            autoHide:    false,            // True when taskbar autohide is set to on, false if set to off
            menuCheck:   'taskbarCheck',    // the name of the element in the menu that needs to be checked if taskbar is shown
            cssClass:    'taskbar'

            //onDrawBegin:    null,
            //onDrawEnd:    null,
            //onMove:        null,
            //onTabCreated:    null,
            //onTabSet:        null,
            //onHide:        null,
            //onShow:        null
        },

        initialize: function(options)
        {
            this.setOptions( options );

            this.el      = {};
            this._tabs   = {};
            this._sels   = [];
            this._groups = [];

            // If taskbar has no ID, give it one.
            this.id = this.options.id = this.options.id || 'taskbar' + (++MUI.idCount);
            MUI.set( this.id, this );

            if ( options.desktop ) {
                this.desktop = options.desktop;
            }

            if ( this.options.drawOnInit ) {
                this.draw();
            }
        },

        draw: function()
        {
            var o = this.options;
            this.fireEvent('drawBegin', [this]);

            if ( this.desktop ) {
                this.desktopFooter = this.desktop.el.footer;
            }

            var isNew = false,
                  div = o.element ? o.element : $(o.id + 'Wrapper');

            if ( !div )
            {
                div = new Element('div', {
                    'id': o.id + 'Wrapper'
                }).inject( o.container );

                isNew = true;
            }

            div.set( 'class', o.cssClass + 'Wrapper' );
            div.empty();

            this.el.wrapper = this.el.element = div.store('instance', this);

            var defaultBottom = this.desktopFooter ? this.desktopFooter.offsetHeight : 0;

            this.el.wrapper.setStyles({
                'display'  : 'block',
                'position' : 'absolute',
                'top'      : null,
                'bottom'   : defaultBottom,
                'left'     : 0,
                'class'    : o.cssClass +'Wrapper'
            });

            this.el.taskbar = new Element('div', {
                'id'    : o.id,
                'class' : o.cssClass,
                styles : {
                    outline: 'none'
                },
                tabindex : -1,
                events   :
                {
                    blur : function()
                    {
                        var sels = this._sels;

                        for ( var prop in sels )
                        {
                            if ( sels.hasOwnProperty( prop ) ) {
                                sels[ prop ].unselect();
                            }
                        }

                        this._sels = [];
                    }.bind( this ),

                    contextmenu : function(event)
                    {
                        this.$Menu
                            .deselectItems()
                            .setPosition( event.page.x + 2, event.page.y + 2)
                            .show()
                            .focus();

                        event.stop();
                    }.bind( this )

                }
            }).inject( this.el.wrapper );

            // menu
            this.$Menu = new QUI.controls.contextmenu.Menu({
                events :
                {
                    onBlur : function(Menu) {
                        Menu.hide();
                    }
                }
            }).inject( document.body );

            this.$Menu.appendChild(
                new QUI.controls.contextmenu.Item({
                    text   : 'Neue Task Gruppe erstellen',
                    events :
                    {
                        onClick : function(Item)
                        {
                            this.createGroup();
                        }.bind( this )
                    }
                })
            );

            this.$Menu.hide();


            // contextmenu button
            this.$ContextButton = new QUI.controls.buttons.Button({
                styles : {
                    width  : 24,
                    height : 24
                }
            }).inject( this.el.taskbar );

            this.$ContextButton.getContextMenu().addEvent('onShow', function(Menu)
            {
                var Button = Menu.getParent(),
                    pos    = Button.getElm().getPosition();

                pos.y = pos.y - Menu.getElm().getSize().y;

                Menu.setPosition( pos.x, pos.y );
            });


            if ( o.useControls )
            {
                this.el.placement = new Element('div', {
                    'id'    : o.id +'Placement',
                    'class' : o.cssClass +'Placement'
                }).inject( this.el.taskbar ).setStyle('cursor', 'default');

                this.el.autohide = new Element('div', {
                    'id'    : o.id +'AutoHide',
                    'class' : o.cssClass +'AutoHide'
                }).inject( this.el.taskbar ).setStyle('cursor', 'default');
            }

            this.el.sort = new Element('div', {
                'id' : o.id +'Sort'
            }).inject( this.el.taskbar );

            /*
            this.el.clear = new Element('div', {
                'id'    : o.id +'Clear',
                'class' : 'clear'
            }).inject( this.el.sort );
            */

            this._initialize();
            this.fireEvent('drawEnd', [this]);
        },

        focus : function()
        {
            if ( this.el.taskbar ) {
                this.el.taskbar.focus();
            }
        },

        setTaskbarColors: function()
        {
            var enabled = Asset.getCSSRule('.mui-taskbarButtonEnabled');

            if ( enabled && enabled.style.backgroundColor ) {
                this.enabledButtonColor = new Color( enabled.style.backgroundColor );
            }

            var disabled = Asset.getCSSRule('.mui-taskbarButtonDisabled');

            if ( disabled && disabled.style.backgroundColor ) {
                this.disabledButtonColor = new Color( disabled.style.backgroundColor );
            }

            var color = Asset.getCSSRule('.mui-taskbarButtonTrue');

            if ( color && color.style.backgroundColor ) {
                this.trueButtonColor = new Color(color.style.backgroundColor);
            }

            this._renderTaskControls();
        },

        getHeight: function()
        {
            return this.el.wrapper.offsetHeight;
        },

        move: function(position)
        {
            var ctx = this.el.canvas.getContext('2d');
            // Move taskbar to top position
            if ( position == 'top' || this.el.wrapper.getStyle('position') != 'relative' )
            {
                if ( position == 'top' ) {
                    return;
                }

                this.el.wrapper.setStyles({
                    'position' : 'relative',
                    'bottom'   : null
                }).addClass('top');

                if ( this.desktop ) {
                    this.desktop.setDesktopSize();
                }

                this.el.wrapper.setProperty('position', 'top');

                ctx.clearRect( 0, 0, 100, 100 );
                MUI.Canvas.circle( ctx, 5, 4, 3, this.enabledButtonColor, 1.0 );
                MUI.Canvas.circle( ctx, 5, 14, 3, this.disabledButtonColor, 1.0 );

                $( this.options.id + 'Placement' ).setProperty(
                    'title',
                    'Position Taskbar Bottom'
                );

                $( this.options.id + 'AutoHide' ).setProperty(
                    'title',
                    'Auto Hide Disabled in Top Taskbar Position'
                );

                this.options.autoHide = false;
                this.options.position = 'top';

            } else
            {
                if ( position == 'bottom' ) {
                    return;
                }

                // Move taskbar to bottom position
                this.el.wrapper.setStyles({
                    'position' : 'absolute',
                    'bottom'   : this.desktopFooter ? this.desktopFooter.offsetHeight : 0
                }).removeClass('top');

                if ( this.desktop ) {
                    this.desktop.setDesktopSize();
                }

                this.el.wrapper.setProperty( 'position', 'bottom' );

                ctx.clearRect( 0, 0, 100, 100 );
                MUI.Canvas.circle( ctx, 5, 4, 3, this.enabledButtonColor, 1.0 );
                MUI.Canvas.circle( ctx, 5, 14, 3, this.enabledButtonColor, 1.0 );

                $( this.options.id + 'Placement' ).setProperty(
                    'title',
                    'Position Taskbar Top'
                );

                $( this.options.id + 'AutoHide' ).setProperty(
                    'title',
                    'Turn Auto Hide On'
                );

                this.options.position = 'bottom';
            }

            this.fireEvent('move', [this, this.options.position]);
        },

        /**
         * Create a new Task Tab
         */
        createTask: function(Instance)
        {
            if ( !this._intialized ) {
                return;
            }

            // create task
            var closeable = false,
                dragable  = false;

            if ( Instance.existAttribute('closeable') &&
                 Instance.getAttribute('closeable') )
            {
                closeable = true;
            }

            if ( Instance.existAttribute('dragable') &&
                 Instance.getAttribute('dragable') )
            {
                dragable = true;
            }

            /*
            var Task = new QUI.controls.taskbar.Task( Instance, {
                closeable : closeable,
                dragable  : dragable
            });
            */

            this.insertTask(
                new QUI.controls.taskbar.Task( Instance, {
                    closeable : closeable,
                    dragable  : dragable
                })
            );


            // console.log( this.el.sort );
            // console.log( Task.getElm() );

            /*
            var titleText = instance.el.title.innerHTML;

            var taskbarTab = new Element('div', {
                'id'    : instance.options.id +'_taskbarTab',
                'class' : this.options.cssClass +'Tab',
                'title' : titleText
            }).inject( $(this.options.id + 'Clear'), 'before' );

            instance._taskBar = this;

            taskbarTab.addEvent('mousedown', function(event)
            {
                event.stop();
                this.timeDown = Date.now();

            }.bind( instance ));

            taskbarTab.addEvent('mousedown', function(event)
            {
                this.$mousehold = (function()
                {
                    this.fireEvent('mousehold', [this]);
                }).delay( 800, this );
            });

            document.addEvent('mouseup', function()
            {
                clearTimeout( this.$mousehold );
            }.bind( taskbarTab ));

            taskbarTab.addEvent('mouseup', function()
            {
                this.sortables.detach();
            }.bind( this ));

            taskbarTab.addEvent('mouseup', function()
            {
                this.timeUp = Date.now();

                if ( (this.timeUp - this.timeDown) < 275 )
                {
                    // If the visibility of the windows on the page are toggled off, toggle visibility on.
                    if ( !MUI.Windows.windowsVisible )
                    {
                        MUI.Windows.toggleAll();

                        if ( this.isMinimized )
                        {
                            this._restoreMinimized.delay(25, this);
                        } else
                        {
                            this.focus();
                        }

                        return;
                    }
                    // If window is minimized, restore window.

                    if ( this.isMinimized )
                    {
                        this._restoreMinimized.delay(25, this);
                    } else
                    {
                        var windowEl = this.el.windowEl;

                        if ( windowEl.hasClass('isFocused') && this.options.minimizable )
                        {
                            this.minimize.delay(25, this);
                        } else
                        {
                            this.focus();
                        }

                        // if the window is not minimized and is outside the viewport, center it in the viewport.
                        var coordinates = document.getCoordinates();

                        if ( windowEl.getStyle('left').toInt() > coordinates.width ||
                             windowEl.getStyle('top').toInt() > coordinates.height )
                        {
                            this.center();
                        }
                    }
                }
            }.bind( instance ));

            taskbarTab.addEvent('mousehold', function(TaskbarTab)
            {
                this.sortables.attach();
            }.bind( this ));

            this.sortables.addItems( taskbarTab );
            this.sortables.detach();

            if ( !titleText.match('<img ') ) {
                titleText = titleText.substring(0, 19) + (titleText.length > 19 ? '...' : '');
            }

            var TabElm = new Element('div', {
                'id'    : instance.options.id + '_taskbarTabText',
                'class' : this.options.cssClass + 'Text',
                'html'  : titleText
            }).inject( taskbarTab );

            // add to contextbutton
            var icon = false;

            if ( TabElm.getElement('img') ) {
                icon = TabElm.getElement('img').get('src');
            }

            this.$ContextButton.appendChild(
                new QUI.controls.contextmenu.Item({
                    icon : icon,
                    text : titleText.replace(new RegExp('<img([^>]*)>', 'g'), ''),
                    iid  : instance.options.id,
                    instance : instance,
                    events   :
                    {
                        onMouseDown : function(event, Item) {
                            Item.getAttribute( 'instance' ).show();
                        }
                    }
                })
            );

            if ( instance.options.closeable )
            {
                new Element('div', {
                    'class': this.options.cssClass + 'Close',
                    'html' : '<span>x</span>',
                    events :
                    {
                        click : function(event)
                        {
                            taskbarTab.fireEvent('tabClose', [taskbarTab]);
                            event.stop();
                        }.bind(taskbarTab)
                    }
                }).inject( taskbarTab );
            }

            // Need to resize everything in case the taskbar wraps when a new tab is added
            if ( this.desktop ) {
                this.desktop.setDesktopSize();
            }

            this.fireEvent('tabCreated', [this, instance]);
            */
        },

        insertTask : function(Task)
        {
            var Instance = Task.getInstance();

            if ( Task.getElm().getParent() != this.el.sort )
            {
                Task.inject( this.el.sort );
            } else
            {
                Task.normalize();
            }

            Task.setParent( this );

            if ( this._tabs[ Instance.getId() ] ) {
                return;
            }

            this._tabs[ Instance.getId() ] = Task;

            Task.addEvents({
                onClick : function(event, Task)
                {
                    if ( typeof event !== 'undefined' && event.control )
                    {
                        if ( Task.isSelected() )
                        {
                            Task.unselect();
                        } else
                        {
                            Task.select();
                        }

                        return;
                    }

                    Task.getInstance().show();
                },

                onRefresh : function(Task)
                {
                    var Item = Task.getTaskbar()._getContextItemByInstance(
                        Task.getInstance()
                    );

                    if ( Item )
                    {
                        Item.setAttribute( 'text', Task.getTitle() );
                        Item.setAttribute( 'icon', Task.getIcon() );
                    }
                },

                onDestroy : function(Task)
                {
                    var Taskbar  = Task.getTaskbar(),
                        Instance = Task.getInstance();

                    if ( Taskbar.$ContextButton )
                    {
                        var i, len;
                        var children = Taskbar.$ContextButton.getChildren();

                        for ( i = 0, len = children.length; i < len; i++ )
                        {
                            if ( children[ i ].getAttribute('Task') == Task ) {
                                children[ i ].destroy();
                            }
                        }
                    }

                    if ( typeof Taskbar._tabs[ Instance.getId() ] !== 'undefined') {
                        delete Taskbar._tabs[ Instance.getId() ];
                    }

                },

                onClose : function(event, Task)
                {
                    Task.getInstance().hide(function()
                    {
                        var Taskbar = Task.getTaskbar(),
                            First   = Taskbar.firstChild();

                        Task.getInstance().close();

                        if ( First ) {
                            First.click();
                        }
                    });
                },

                onSelect : function(Task)
                {
                    var Taskbar = Task.getTaskbar();

                    Taskbar._sels[ Task.getId() ] = Task;
                    Taskbar.focus();
                },

                onUnselect : function(Task)
                {
                    var Taskbar = Task.getTaskbar();

                    if ( Taskbar._sels[ Task.getId() ] ) {
                        delete Taskbar._sels[ Task.getId() ];
                    }

                    Taskbar.focus();
                },

                onContextMenu : function(event, Task)
                {


                }
            });

            Instance.addEvent('onShow', function()
            {
                if ( typeOf( this.getParent() ) == 'QUI.controls.taskbar.Group' )
                {
                    this.getParent().refresh( this );
                    this.getParent().activate();

                    return;
                }

                this.activate();

            }.bind( Task ));

            Instance.addEvent('onHide', function()
            {
                if ( typeOf( this.getParent() ) == 'QUI.controls.taskbar.Group' )
                {
                    this.getParent().normalize();
                    return;
                }

                this.normalize();
            }.bind( Task ));

            // create context menu entry
            this.$ContextButton.appendChild(
                new QUI.controls.contextmenu.Item({
                    icon   : Instance.getAttribute('icon'),
                    text   : Instance.getAttribute('title'),
                    Task   : Task,
                    events :
                    {
                        onMouseDown : function(event, Item) {
                            Item.getAttribute( 'Task' ).click();
                        }
                    }
                })
            );
        },

        /**
         * Create a new taskbar group
         */
        createGroup : function()
        {
            var Group = new QUI.controls.taskbar.Group();

            Group.inject( this.el.sort );
            Group.setParent( this );

            this._groups.push( Group );

            return Group;
        },

        /**
         * Get Taskgroup by its dom node
         *
         * @return {false|QUI.controls.taskbar.Group}
         */
        getGroupByDOM : function(DOMNode)
        {
            for ( var i = 0, len = this._groups.length; i < len; i++ )
            {
                if ( this._groups[i].getElm() == DOMNode  ) {
                    return this._groups[i];
                }
            }

            return false;
        },

        /**
         * refresh a tab by instance
         */
        refreshTab: function(Instance)
        {
            var Task = this.getTab( Instance );

            if ( Task ) {
                Task.refresh();
            }
        },

        /**
         * Return the Task of the Instance
         *
         * @return {undefined|QUI.controls.taskbar.Task}
         */
        getTab: function(Instance)
        {
            return this._tabs[ Instance.getId() ];
        },

        makeTabActive: function(Instance)
        {
            var Task = this.getTab( Instance );

            if ( Task ) {
                Task.activate();
            }

            /*
            return;

            var css = this.options.cssClass;

            if ( !instance )
            {
                // getWindowWithHighestZindex is used in case the currently focused window is closed.
                var windowEl = MUI.Windows._getWithHighestZIndex();
                instance = windowEl.retrieve( 'instance' );
            }

            this.makeTabsNormal();

            if ( instance.isMinimized !== true )
            {
                instance.el.windowEl.addClass( 'isFocused' );
                var currentButton = $( instance.options.id + '_taskbarTab' );

                if ( currentButton !== null ) {
                    currentButton.addClass('activeTab');
                }
            } else
            {
                instance.el.windowEl.removeClass('isFocused');
            }

            var Item = this._getContextItemByInstance( instance );

            if ( Item ) {
                Item.setActive();
            }


            this.fireEvent('tabSet', [this,instance]);
            */
        },
        /*
        removeTab: function(Instance)
        {
            var Tab = this.getTab( Instance );

            if ( !Tab ) {
                return;
            }

            Tab.close();

            this.sortables.removeItems( Tab ).destroy();

            var Item = this._getContextItemByInstance( Instance );

            if ( Item ) {
                Item.destroy();
            }

            if ( this._tabs[ Instance.getId() ] )
            {
                this._tabs[ Instance.getId() ].destroy();
                delete this._tabs[ Instance.getId() ];
            }

            // Need to resize everything in case the taskbar becomes smaller when a tab is removed
            // @todo neccessary?
            MUI.desktop.setDesktopSize();
        },
        */
        /**
         * Return the first Task in the taskbar
         *
         * @return {false|QUI.controls.taskbar.Task}
         */
        firstChild: function()
        {
            for ( var i in this._tabs )
            {
                if ( this._tabs.hasOwnProperty(i) ) {
                    return this._tabs[ i ];
                }
            }

            return false;
        },

        show: function()
        {
            this.el.wrapper.setStyle( 'display', 'block' );
            this.options.visible = true;

            if ( this.desktop ) {
                this.desktop.setDesktopSize();
            }

            this.fireEvent( 'show', [this] );
        },

        hide: function()
        {
            this.el.wrapper.setStyle( 'display', 'none' );
            this.options.visible = false;

            if ( this.desktop ) {
                this.desktop.setDesktopSize();
            }

            this.fireEvent( 'hide', [this] );
        },

        toggle: function()
        {
            if ( !this.options.visible )
            {
                this.show();
            } else
            {
                this.hide();
            }
        },

        /**
         * Return the appropriate context menu item
         *
         * @return {QUI.controls.contextmenu.Item|false}
         */
        _getContextItemByInstance : function(Instance)
        {
            var Task        = this.getTab( Instance ),
                contextlist = this.$ContextButton.getChildren();

            for ( var i = 0, len = contextlist.length; i <  len; i++ )
            {
                if ( contextlist[i].getAttribute( 'Task' ) == Task ) {
                    return contextlist[i];
                }
            }

            return false;
        },

        _initialize: function()
        {
            var css = this.options.cssClass;

            if ( this.options.useControls )
            {
                // Insert canvas
                this.el.canvas = new Element('canvas', {
                    'id'     : this.options.id +'Canvas',
                    'width'  : 15,
                    'height' : 18,
                    'class'  : css +'Canvas'
                }).inject(this.el.taskbar);

                // Dynamically initialize canvas using excanvas. This is only required by IE
                if ( Browser.ie && MUI.ieSupport == 'excanvas' ) {
                    G_vmlCanvasManager.initElement( this.el.canvas );
                }
            }

            // Position top or bottom selector
            if ( this.el.placement )
            {
                this.el.placement.setProperty('title', 'Position Taskbar Top');

                // Attach event
                this.el.placement.addEvent('click', function(){
                    this.move();
                }.bind( this ));
            }

            // Auto Hide toggle switch
            if ( this.el.autohide )
            {
                this.el.autohide.setProperty('title', 'Turn Auto Hide On');

                // Attach event Auto Hide
                this.el.autohide.addEvent('click', function(){
                    this._doAutoHide();
                }.bind( this ));
            }

            this.setTaskbarColors.delay(100,this);

            if ( this.options.position == 'top' ) {
                this.move();
            }

            // Add check mark to menu if link exists in menu
            if ( $(this.options.menuCheck) )
            {
                this.sidebarCheck = new Element('div', {
                    'class' : 'check',
                    'id'    : this.options.id +'_check'
                }).inject( $(this.options.menuCheck) );
            }

            this.sortables = new Sortables('.' + css + 'Sort', {
                opacity   : 0.5,
                constrain : false,
                revert    : false,
                clone     : false
                /*function(event)
                {
                    return false;

                    var cords;
                    var Target   = event.target,
                        cssClass = this.options.cssClass + 'Tab';

                    if (!Target.hasClass( cssClass )) {
                        Target = Target.getParent('.'+ cssClass);
                    }

                    cords = Target.getCoordinates( Target.getParent() );

                    return new Element('div', {
                        'class' : 'taskbar-dragdrop radius5',
                        styles  : {
                            top        : cords.top,
                            left       : cords.left,
                            width      : cords.width,
                            height     : cords.height,
                            position   : 'absolute',
                            zIndex     : 1000,
                            background : 'rgba(0,0,0, 0.5)'
                        }
                    });

                }.bind(this)*/
            });

            if ( this.options.autoHide ) {
                this._doAutoHide( true );
            }

            if ( this.desktopFooter ) {
                this.desktop.setDesktopSize();
            }

            this._intialized = true;
            var tabs         = this._tabs;
            this._tabs       = {};

            for ( var i = 0, len = this._tabs.length; i < len; i++ ) {
                this.createTask( this._tabs[i] );
            }
        },

        _doAutoHide: function(notoggle)
        {
            if ( this.el.wrapper.getProperty('position') == 'top' ) {
                return false;
            }

            var ctx = this.el.canvas.getContext('2d');

            if ( !notoggle ) {
                this.options.autoHide = !this.options.autoHide;    // Toggle
            }

            if ( this.options.autoHide )
            {
                $(this.options.id + 'AutoHide').setProperty('title', 'Turn Auto Hide Off');
                MUI.Canvas.circle(ctx, 5, 14, 3, this.trueButtonColor, 1.0);
                document.addEvent('mousemove', this._autoHideEvent.bind(this));
            } else
            {
                $(this.options.id + 'AutoHide').setProperty('title', 'Turn Auto Hide On');
                MUI.Canvas.circle(ctx, 5, 14, 3, this.enabledButtonColor, 1.0);
                document.removeEvent('mousemove', this._autoHideEvent.bind(this));
            }
        },

        _autoHideEvent: function(event)
        {
            if ( !this.options.autoHide ) {
                return;
            }

            var hotspotHeight;

            if ( !this.desktopFooter )
            {
                hotspotHeight = this.el.wrapper.offsetHeight;

                if ( hotspotHeight < 25 ) {
                    hotspotHeight = 25;
                }

            } else if ( this.desktopFooter )
            {
                hotspotHeight = this.el.wrapper.offsetHeight + this.desktopFooter.offsetHeight;

                if ( hotspotHeight < 25 ) {
                    hotspotHeight = 25;
                }
            }


            var docHeight = document.getCoordinates().height;

            if ( !this.desktopFooter &&
                 event.client.y > (docHeight - hotspotHeight) )
            {
                if ( !this.options.visible ) {
                    this.show();
                }

            } else if ( this.desktopFooter &&
                        event.client.y > (docHeight - hotspotHeight) )
            {
                if ( !this.options.visible ) {
                    this.show();
                }

            } else if ( this.options.visible )
            {
                this.hide();
            }
        },

        _renderTaskControls: function()
        {
            if ( !this.el.canvas ) {
                return;
            }

            // Draw taskbar controls
            var ctx = this.el.canvas.getContext('2d');
            ctx.clearRect(0, 0, 100, 100);
            MUI.Canvas.circle(ctx, 5, 4, 3, this.enabledButtonColor, 1.0);

            if ( this.el.wrapper.getProperty('position') == 'top' )
            {
                MUI.Canvas.circle(ctx, 5, 14, 3, this.disabledButtonColor, 1.0);
            } else if ( this.options.autoHide )
            {
                MUI.Canvas.circle(ctx, 5, 14, 3, this.trueButtonColor, 1.0);
            } else
            {
                MUI.Canvas.circle(ctx, 5, 14, 3, this.enabledButtonColor, 1.0);
            }
        }
    });

    MUI.Windows = Object.append((MUI.Windows || {}),
    {
        minimizeAll: function()
        {
            $$('.mui-window').each(function(windowEl)
            {
                var instance = windowEl.retrieve('instance');

                if ( !instance.isMinimized && instance.options.minimizable ) {
                    instance.minimize();
                }
            }.bind( this ));
        }
    });

    MUI.Window = (MUI.Window || new NamedClass('MUI.Window', {}));
    MUI.Window.implement({

        minimize: function()
        {
            if ( this.isMinimized ) {
                return this;
            }

            this.isMinimized = true;

            // Hide iframe
            // Iframe should be hidden when minimizing, maximizing, and moving for performance and Flash issues
            if ( this.el.iframe )
            {
                // Some elements are still visible in IE8 in the iframe when the iframe's visibility is set to hidden.
                if ( !Browser.ie )
                {
                    this.el.iframe.setStyle('visibility', 'hidden');
                } else
                {
                    this.el.iframe.hide();
                }
            }

            this.hide(); // Hide window and add to taskbar

            // Fixes a scrollbar issue in Mac FF2
            if ( Browser.Platform.mac && Browser.firefox && Browser.version < 3 ) {
                this.el.contentWrapper.setStyle('overflow', 'hidden');
            }

            if ( this.desktop ) {
                this.desktop.setDesktopSize();
            }

            // Have to use timeout because window gets focused when you click on the minimize button
            setTimeout(function()
            {
                //this.el.windowEl.setStyle('zIndex', 1);
                this.el.windowEl.removeClass('isFocused');
                MUI.desktop.taskbar.makeTabActive( this );
            }.bind( this ), 100);

            this.fireEvent('minimize', [this]);
            return this;
        },

        _restoreMinimized: function()
        {
            if ( !this.isMinimized ) {
                return;
            }

            if ( !MUI.Windows.windowsVisible ) {
                MUI.Windows.toggleAll();
            }

            this.show(); // show the window
            MUI.desktop.setDesktopSize();

            if ( this.options.scrollbars && !this.el.iframe ) {
                this.el.contentWrapper.setStyle('overflow', 'auto'); // Part of Mac FF2 scrollbar fix
            }

            if ( this.isCollapsed ) {
                this.collapseToggle();
            }

            if ( this.el.iframe )
            {   // Show iframe
                if ( !Browser.ie )
                {
                    this.el.iframe.setStyle('visibility', 'visible');
                } else
                {
                    this.el.iframe.show();
                }
            }

            this.isMinimized = false;
            this.focus();
            this.fireEvent('restore', [this]);
        }
    });

    return MUI.Taskbar;
});