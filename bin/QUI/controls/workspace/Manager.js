
/**
 * Workspace Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/workspace/Manager
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require qui/controls/desktop/Workspace
 * @require qui/controls/desktop/Column
 * @require qui/controls/desktop/Panel
 * @require qui/controls/desktop/Tasks
 * @require qui/controls/windows/Popup
 * @require qui/controls/windows/Submit
 * @require qui/controls/messages/Panel
 * @require controls/welcome/Panel
 * @require controls/desktop/panels/Help
 * @require controls/desktop/panels/Bookmarks
 * @require controls/projects/project/Panel
 * @require Ajax
 * @require UploadManager
 * @require css!controls/workspace/Manager.css
 *
 * @event onWorkspaceLoaded [ {self} ]
 * @event onLoadWorkspace [ {self} ]
 */

define([

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/desktop/Workspace',
    'qui/controls/desktop/Column',
    'qui/controls/desktop/Panel',
    'qui/controls/desktop/Tasks',
    'qui/controls/windows/Popup',
    'qui/controls/windows/Confirm',
    'qui/controls/messages/Panel',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Seperator',
    'qui/utils/Controls',

    'controls/welcome/Panel',
    'controls/desktop/panels/Help',
    'controls/desktop/panels/Bookmarks',
    'controls/projects/project/Panel',
    'controls/grid/Grid',
    'Ajax',
    'UploadManager',

    'css!controls/workspace/Manager.css'

], function()
{
    "use strict";

    var QUI             = arguments[ 0 ],
        QUIControl      = arguments[ 1 ],
        QUILoader       = arguments[ 2 ],
        QUIWorkspace    = arguments[ 3 ],
        QUIColumn       = arguments[ 4 ],
        QUIPanel        = arguments[ 5 ],
        QUITasks        = arguments[ 6 ],
        QUIWindow       = arguments[ 7 ],
        QUIConfirm      = arguments[ 8 ],
        QUIMessagePanel = arguments[ 9 ],
        QUIContextmenuItem      = arguments[ 10 ],
        QUIContextmenuSeperator = arguments[ 11 ],
        QUIControlUtils         = arguments[ 12 ],

        WelcomePanel  = arguments[ 13 ],
        HelpPanel     = arguments[ 14 ],
        BookmarkPanel = arguments[ 15 ],
        ProjectPanel  = arguments[ 16 ],
        Grid          = arguments[ 17 ],
        Ajax          = arguments[ 18 ],
        UploadManager = arguments[ 19 ];


    return new Class({

        Extends : QUIControl,
        Type    : 'controls/workspace/Manager',

        Binds : [
            'resize',
            'save',
            '$onInject',
            '$onColumnContextMenu',
            '$onColumnContextMenuBlur'
        ],

        options : {
            autoResize  : true, // resize workspace on window resize
            workspaceId : false
        },

        initialize : function(options)
        {
            var self = this;

            this.parent( options );

            this.Loader    = new QUILoader();
            this.Workspace = new QUIWorkspace({
                events : {
                    onColumnContextMenu : this.$onColumnContextMenu
                }
            });

            this.$spaces = {};

            this.$minWidth   = false;
            this.$minHeight  = false;
            this.$ParentNode = null;

            this.$availablePanels = null; // cache
            this.$resizeDelay     = null;

            this.$resizeQuestionWindow = false; // if resize quesion window open?

            this.addEvents({
                onInject : this.$onInject
            });

            if ( this.getAttribute( 'autoResize' ) )
            {
                window.addEvent( 'resize', function()
                {
                    // delay,
                    if ( self.$resizeDelay ) {
                        clearTimeout( self.$resizeDelay );
                    }

                    this.$resizeDelay = (function() {
                        self.resize();
                    }).delay( 200 );
                });
            }

            // @todo besser als onChange event von den panels
            // ansonsten kann es sein das es so aussieht das das browser fenster sich nicht schließen lässt
            // bei langsamer verbindung
            window.addEvent( 'beforeunload', this.save );
        },

        /**
         * Create the DOMNode Element
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'qui-workspace-manager',
                styles : {
                    overflow : 'hidden'
                }
            });


            this.Loader.inject( this.$Elm );
            this.Workspace.inject( this.$Elm );

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            this.$ParentNode = this.$Elm.getParent();

            this.load();
        },

        /**
         * resize the workspace
         */
        resize : function()
        {
            if ( this.$resizeQuestionWindow ) {
                return;
            }

            if ( !this.$ParentNode ) {
                return;
            }

            console.log( 11 );

            var size   = this.$ParentNode.getSize(),
                width  = size.x,
                height = size.y,

                rq = false;

            this.$Elm.setStyle( 'overflow', null );

            if ( this.$minWidth && width < this.$minWidth )
            {
                width = this.$minWidth;
                rq    = true;

                this.$Elm.setStyle( 'overflow', 'auto' );
            }

            if ( this.$minHeight && height < this.$minHeight )
            {
                height = this.$minHeight;
                rq     = true;

                this.$Elm.setStyle( 'overflow', 'auto' );
            }

            this.Workspace.setWidth( width );
            this.Workspace.setHeight( height );
            this.Workspace.resize();

            if ( !rq ) {
                return;
            }

            // resize question, workspace not fit in
            this.$resizeQuestionWindow = true;

            var self = this;

            new QUIConfirm({
                title     : 'Der Arbeitsbereich zu gross',
                autoclose : false,
                maxWidth  : 600,
                events    :
                {
                    onOpen : function(Win)
                    {
                        var i, Select;
                        var Content = Win.getContent();

                        Content.set(
                            'html',

                            '<h1>Der Arbeitsbereich ist zu gross</h1>'+
                            '<p>Der Arbeitsbereich ist leider zu gross für Ihr Browserfenster.' +
                            'Bitte wählen Sie bitte einen passenden Arbeitsbereich.</p><br />'+
                            '<select></select>'
                        );

                        Select = Content.getElement( 'select' );

                        Select.setStyles({
                            clear   : 'both',
                            display : 'block',
                            margin  : '0px auto',
                            width   : 200
                        });

                        for ( i in self.$spaces )
                        {
                            new Element('option', {
                                value : self.$spaces[ i ].id,
                                html  : self.$spaces[ i ].title
                            }).inject( Select );
                        }
                    },

                    onSubmit : function()
                    {

                    },

                    onClose : function()
                    {
                        this.$resizeQuestionWindow = false;
                    }
                }
            }).open();
        },

        /**
         * load the workspace for the user
         *
         * @param {Function} callback - [optional] callback function
         */
        load : function(callback)
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_desktop_workspace_load', function(list)
            {
                if ( !list || !list.length )
                {
                    // create default workspaces
                    var colums2, colums3;

                    var Workspace = new QUIWorkspace(),
                        Parent    = self.$Elm.clone();

                    Workspace.inject( Parent );

                    // 2 columns
                    self.$loadDefault2Column( Workspace );

                    colums2 = {
                        title     : '2 Spalten',
                        data      : JSON.encode( Workspace.serialize() ),
                        minHeight : self.$minHeight,
                        minWidth  : self.$minWidth
                    };

                    // 3 columns
                    Workspace.clear();

                    self.$loadDefault3Column( Workspace );

                    colums3 = {
                        title     : '3 Spalten',
                        data      : JSON.encode( Workspace.serialize() ),
                        minHeight : self.$minHeight,
                        minWidth  : self.$minWidth
                    };

                    // add workspaces
                    self.add( colums2, function()
                    {
                        self.add( colums3, function() {
                            self.load( callback );
                        });
                    });

                    return;
                }

                self.$spaces = {};


                var Standard = false;

                for ( var i = 0, len = list.length; i < len; i++ )
                {
                    self.$spaces[ list[ i ].id ] = list[ i ];

                    if ( list[ i ].standard &&
                         ( list[ i ].standard ).toInt() === 1 )
                    {
                        Standard = list[ i ];
                    }
                }

                self.fireEvent( 'workspaceLoaded', [ self ] );

                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                // ask which workspace
                if ( !Standard )
                {
                    self.$openWorkspaceListWindow();
                    return;
                }

                // load standard workspace
                self.$loadWorkspace( Standard.id );
            });
        },

        /**
         * Return the workspace list, available workspaces
         *
         * @return {Object} List
         */
        getList : function()
        {
            return this.$spaces;
        },


        /**
         * Insert a control into a Column
         *
         * @param {String} panelRequire - panel require
         * @param {qui/controls/desktop/Column} Column - Parent Column
         */
        appendControlToColumn : function(panelRequire, Column)
        {
            require([ panelRequire ], function(cls)
            {
                if ( QUI.Controls.isControl( cls ) )
                {
                    Column.appendChild( cls );
                    return;
                }

                Column.appendChild( new cls() );
            });
        },

        /**
         * Return all available panels
         *
         * @param {Function} callback - callback function
         */
        getAvailablePanels : function(callback)
        {
            if ( this.$availablePanels )
            {
                callback( this.$availablePanels );
                return;
            }

            var self = this;

            // loads available panels
            Ajax.get('ajax_desktop_workspace_getAvailablePanels', function(panels)
            {
                self.$availablePanels = panels;

                callback( panels );
            });
        },

        /**
         * load another Workspace
         * Saves the current workspace and load the new wanted
         *
         * @param {Integer} id - workspace id
         */
        loadWorkspace : function(id)
        {
            if ( typeof this.$spaces[ id ] === 'undefined' )
            {
                QUI.getMessageHandler(function(MH) {
                    MH.addError( 'Workspace not found' );
                });

                return;
            }

            var self = this;

            this.Loader.show();

            this.save();
            this.Workspace.clear();

            this.Workspace.unserialize(
                JSON.decode( this.$spaces[ id ].data )
            );

            this.Workspace.fix();
            this.Workspace.resize();

            this.setAttribute( 'workspaceId', id );

            Ajax.post('ajax_desktop_workspace_setStandard', function()
            {
                self.fireEvent( 'loadWorkspace', [ self ] );
                self.Loader.hide();
                self.Workspace.focus();
            }, {
                'package' : 'quiqqer/tags',
                id        : id
            });
        },

        /**
         * Load a workspace
         *
         * @param {Integer} id
         */
        $loadWorkspace : function(id)
        {
            this.Loader.show();

            if ( !id || id === '' )
            {
                this.$useBestWorkspace();
                return;
            }

            if ( typeof this.$spaces[ id ] === 'undefined' )
            {
                QUI.getMessageHandler(function(MH) {
                    MH.addError( 'Workspace not found' );
                });

                return;
            }

            var workspace = this.$spaces[ id ];

            this.$minWidth  = workspace.minWidth;
            this.$minHeight = workspace.minHeight;

            this.Workspace.clear();
            this.Workspace.unserialize(
                JSON.decode( workspace.data )
            );

            this.Workspace.fix();
            this.Workspace.resize();
            this.setAttribute( 'workspaceId', id );

            this.Loader.hide();
        },

        /**
         * Opens a window with the workspace list
         * the user can choose the standard workspace
         */
        $openWorkspaceListWindow : function()
        {
            var self = this;

            new QUIWindow({
                title     : 'Standard Arbeitsbereich wählen',
                maxHeight : 200,
                maxWidth  : 500,
                autoclose : false,
                buttons   : false,
                events    :
                {
                    onOpen : function(Win)
                    {
                        var Body = Win.getContent().set(
                                'html',

                                '<p>Bitte wählen Sie einen Arbeitsbereich aus welchen Sie nutzen möchten</p>' +
                                '<select></select>'
                            ),

                            Select = Body.getElement( 'select' );

                        Select.setStyles({
                            display : 'block',
                            margin  : '10px auto',
                            width   : 200
                        });

                        Select.addEvents({
                            change : function()
                            {
                                var value = this.value;

                                Win.Loader.show();

                                Ajax.post('ajax_desktop_workspace_setStandard', function()
                                {
                                    self.$loadWorkspace( value );
                                    Win.close();
                                }, {
                                    id : value
                                });
                            }
                        });

                        new Element('option', {
                            html  : '',
                            value : ''
                        }).inject( Select );

                        for ( var i in self.$spaces )
                        {
                            new Element('option', {
                                html  : self.$spaces[ i ].title,
                                value : self.$spaces[ i ].id
                            }).inject( Select );
                        }
                    },

                    onCancel : function() {
                        self.$useBestWorkspace();
                    }
                }
            }).open();
        },

        /**
         * Search the best workspace that fits in space
         */
        $useBestWorkspace : function()
        {

        },

        /**
         * Save the workspace
         */
        save : function()
        {
            Ajax.syncRequest('ajax_desktop_workspace_save', 'post', {
                data : JSON.encode( this.Workspace.serialize() ),
                id   : this.getAttribute( 'workspaceId' )
            });
        },

        /**
         * Add a Workspace
         *
         * @param {Object} data - workspace data {
         * 		title
         * 		data
         * 		minWidth
         * 		minHeight
         * }
         * @param {Function} callback - callback function
         */
        add : function(data, callback)
        {
            Ajax.post('ajax_desktop_workspace_add', function(result)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

            }, {
                data : JSON.encode( data )
            });
        },

        /**
         * Edit a Workspace
         *
         * @param {Integer} id - Workspace-ID
         * @param {Object} data - workspace data {
         * 		title [optional]
         * 		data [optional]
         * 		minWidth [optional]
         * 		minHeight [optional]
         * }
         * @param {Function} callback - callback function
         */
        edit : function(id, data, callback)
        {
            Ajax.post('ajax_desktop_workspace_edit', function(result)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

            }, {
                id   : id,
                data : JSON.encode( data )
            });
        },

        /**
         * Delete workspaces
         *
         * @param {Array} ids - list of workspace ids
         * @param {Function} callback - [optional] callback function
         */
        del : function(ids, callback)
        {
            Ajax.post('ajax_desktop_workspace_delete', function(result)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

            }, {
                ids : JSON.encode( ids )
            });
        },

        /**
         * unfix the workspace
         */
        unfix : function()
        {
            this.Workspace.unfix();
        },

        /**
         * fix the worksapce
         */
        fix : function()
        {
            this.Workspace.fix();
        },

        /**
         * load the default 3 column workspace
         *
         * @param {qui/controls/desktop/Workspace} Workspace
         */
        $loadDefault3Column : function(Workspace)
        {
            this.$minWidth  = 1000;
            this.$minHeight = 500;

            var self   = this,
                size   = this.$Elm.getSize(),
                panels = this.$getDefaultPanels();

            // Columns
            var LeftColumn = new QUIColumn({
                    height : size.y
                }),

                MiddleColumn = new QUIColumn({
                    height : size.y,
                    width  : size.x * 0.7
                }),

                RightColumn = new QUIColumn({
                    height : size.y,
                    width  : size.x * 0.3
                });


            Workspace.appendChild( LeftColumn );
            Workspace.appendChild( MiddleColumn );
            Workspace.appendChild( RightColumn );

            // panels
            panels.Bookmarks.setAttribute( 'height', 400 );
            panels.Messages.setAttribute( 'height', 100 );
            panels.Uploads.setAttribute( 'height', 300 );
            panels.Help.setAttribute( 'height', 400 );

            // insert panels
            LeftColumn.appendChild( panels.Projects );
            LeftColumn.appendChild( panels.Bookmarks );

            MiddleColumn.appendChild( panels.Tasks );

            RightColumn.appendChild( panels.Messages );
            RightColumn.appendChild( panels.Uploads );
            RightColumn.appendChild( panels.Help );

            panels.Help.minimize();

            Workspace.fix();
        },

        /**
         * loads the default 2 column workspace
         *
         * @param {qui/controls/desktop/Workspace} Workspace
         */
        $loadDefault2Column : function(Workspace)
        {
            this.$minWidth  = 800;
            this.$minHeight = 500;

            var self   = this,
                size   = this.$Elm.getSize(),
                panels = this.$getDefaultPanels(),

                LeftColumn = new QUIColumn({
                    height : size.y
                }),

                MiddleColumn = new QUIColumn({
                    height : size.y,
                    width  : size.x - 400
                });


            Workspace.appendChild( LeftColumn );
            Workspace.appendChild( MiddleColumn );

            panels.Bookmarks.setAttribute( 'height', 300 );
            panels.Messages.setAttribute( 'height', 100 );
            panels.Uploads.setAttribute( 'height', 100 );
            panels.Help.setAttribute( 'height', 100 );

            LeftColumn.appendChild( panels.Projects );
            LeftColumn.appendChild( panels.Bookmarks );
            LeftColumn.appendChild( panels.Messages );
            LeftColumn.appendChild( panels.Uploads );
            LeftColumn.appendChild( panels.Help );

            MiddleColumn.appendChild( panels.Tasks );

            panels.Help.minimize();

            Workspace.fix();
        },

        /**
         * Return the default panels
         *
         * @return {Object}
         */
        $getDefaultPanels : function()
        {
            var Bookmarks = new BookmarkPanel({
                title  : 'Bookmarks',
                icon   : 'icon-bookmark',
                name   : 'qui-bookmarks',
                events :
                {
                    onInject : function(Panel)
                    {
                        Panel.Loader.show();

                        require(['Users'], function(Users)
                        {
                            var User = Users.get( USER.id );

                            User.load(function()
                            {
                                var data = JSON.decode( User.getAttribute( 'qui-bookmarks' ) );

                                if ( !data )
                                {
                                    Panel.Loader.hide();
                                    return;
                                }

                                Panel.unserialize( data );
                                Panel.Loader.hide();
                            });
                        });
                    },

                    onAppendChild : function(Panel, Item)
                    {
                        Panel.Loader.show();

                        require(['Users'], function(Users)
                        {
                            var User = Users.get( USER.id );

                            User.setAttribute( 'qui-bookmarks', JSON.encode( Panel.serialize() ) );

                            User.save(function() {
                                Panel.Loader.hide();
                            });
                        });
                    },

                    onRemoveChild : function(Panel)
                    {
                        Panel.Loader.show();

                        require(['Users'], function(Users)
                        {
                            var User = Users.get( USER.id );

                            User.setExtra( 'qui-bookmarks', JSON.encode( Panel.serialize() ) );

                            User.save(function() {
                                Panel.Loader.hide();
                            });
                        });
                    }
                }
            });


            // task panel
            var Tasks = new QUITasks({
                title : 'My Panel 1',
                icon  : 'icon-heart',
                name  : 'tasks'
            });

            Tasks.appendChild( new WelcomePanel() );


            return {
                Projects  : new ProjectPanel(),
                Bookmarks : Bookmarks,
                Tasks     : Tasks,
                Messages  : new QUIMessagePanel(),
                Uploads   : UploadManager,
                Help      : new HelpPanel()
            };
        },

        /**
         * Column helpers
         */

        /**
         * event : on workspace context menu -> on column context menu
         * Create the contextmenu for the column edit
         *
         * @param {qui/controls/desktop/Workspace} Workspace
         * @param {qui/controls/desktop/Column} Column
         * @param {DOMEvent}
         */
        $onColumnContextMenu : function(Workspace, Column, event)
        {
            event.stop();

            Column.highlight();

            var self   = this,
                Menu   = Column.$ContextMenu,
                panels = Column.getChildren();

            Menu.addEvents({
                onBlur : this.$onColumnContextMenuBlur
            });

            Menu.clearChildren();
            Menu.setTitle( 'Column' );

            // add panels
            Menu.appendChild(
                new QUIContextmenuItem({
                    text   : 'Panels hinzufügen',
                    icon   : 'icon-plus',
                    name   : 'addPanelsToColumn',
                    events :
                    {
                        onClick : function() {
                            self.openPanelList( Column );
                        }
                    }
                })
            );


            // remove panels
            if ( Object.getLength( panels ) )
            {
                // remove panels
                var RemovePanels = new QUIContextmenuItem({
                    text : 'Panel löschen',
                    name : 'removePanelOfColumn',
                    icon : 'icon-trash'
                });

                Menu.appendChild( RemovePanels );

                Object.each( panels, function(Panel)
                {
                    RemovePanels.appendChild(
                        new QUIContextmenuItem({
                            text   : Panel.getAttribute( 'title' ),
                            icon   : Panel.getAttribute( 'icon' ),
                            name   : Panel.getAttribute( 'name' ),
                            Panel  : Panel,
                            events : {
                                onActive    : self.$onEnterRemovePanel,
                                onNormal    : self.$onLeaveRemovePanel,
                                onMouseDown : self.$onClickRemovePanel
                            }
                        })
                    );
                });
            }

            Menu.appendChild( new QUIContextmenuSeperator() );

            // add columns
            var AddColumn = new QUIContextmenuItem({
                text : 'Spalte hinzufügen',
                name : 'add_columns',
                icon : 'icon-plus'
            });

            AddColumn.appendChild(
                new QUIContextmenuItem({
                    text   : 'Spalte davor einfügen',
                    name   : 'addColumnBefore',
                    icon   : 'icon-long-arrow-left',
                    events :
                    {
                        onClick : function()
                        {
                            self.Workspace.appendChild(
                                new QUIColumn({
                                    height : '100%',
                                    width  : 200
                                }),
                                'before',
                                Column
                            );
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    text   : 'Spalte danach einfügen',
                    name   : 'addColumnAfter',
                    icon   : 'icon-long-arrow-right',
                    events :
                    {
                        onClick : function()
                        {
                            self.Workspace.appendChild(
                                new QUIColumn({
                                    height : '100%',
                                    width  : 200
                                }),
                                'after',
                                Column
                            );
                        }
                    }
                })
            );

            Menu.appendChild( AddColumn );


            // remove column
            Menu.appendChild(
                new QUIContextmenuItem({
                    text   : 'Spalte löschen',
                    icon   : 'icon-trash',
                    name   : 'removeColumn',
                    events :
                    {
                        onClick : function() {
                            Column.destroy();
                        }
                    }
                })
            );


            Menu.setPosition(
                event.page.x,
                event.page.y
            ).show().focus();
        },

        /**
         * event : column context onBlur
         *
         * @param {qui/controls/contextmenu/Menu}
         */
        $onColumnContextMenuBlur : function(Menu)
        {
            Menu.getAttribute( 'Column' ).normalize();
            Menu.removeEvent( 'onBlur', this.$onColumnContextMenuBlur );
        },

        /**
         * event : on mouse enter at a contextmenu item -> remove panel
         *
         * @param {qui/controls/contextmenu/Item} Item
         */
        $onEnterRemovePanel : function(Item)
        {
            Item.getAttribute( 'Panel' ).highlight();
        },

        /**
         * event : on mouse leave at a contextmenu item -> remove panel
         *
         * @param {qui/controls/contextmenu/Item} Item
         */
        $onLeaveRemovePanel : function(Item)
        {
            Item.getAttribute( 'Panel' ).normalize();
        },

        /**
         * event : on mouse click at a contextmenu item -> remove panel
         *
         * @param {qui/controls/contextmenu/Item} Item
         */
        $onClickRemovePanel : function(ContextItem)
        {
            ContextItem.getAttribute( 'Panel' ).destroy();

            this.focus();
        },


        /**
         * windows
         */

        /**
         * Opens the create workspace window
         */
        openCreateWindow : function()
        {
            var self = this;

            new QUIConfirm({
                title     : 'Neuen Arbeitsbereich erstellen',
                icon      : 'icon-rocket',
                maxWidth  : 400,
                maxHeight : 500,
                autoclose : false,
                ok_button : {
                    text      : 'Erstellen',
                    textimage : 'icon-ok'
                },
                cancel_button : {
                    text      : 'Abbrechen',
                    textimage : 'icon-remove'
                },
                events    :
                {
                    onOpen : function(Win)
                    {
                        var Content = Win.getContent(),
                            id      = Win.getId(),
                            size    = document.getSize();

                        Content.addClass( 'qui-workspace-manager-window' );

                        Content.set(
                            'html',

                            '<table class="data-table">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th colspan="2">Arbeitsbereich Einstellungen</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>' +

                                    '<tr class="odd">' +
                                        '<td><label for="workspace-title-'+ id +'">Titel</label></td>' +
                                        '<td><input id="workspace-title-'+ id +'" name="workspace-title" type="text" value="" /></td>' +
                                    '</tr>' +
                                    '<tr class="even">' +
                                        '<td><label for="workspace-columns-'+ id +'">Spalten</label></td>' +
                                        '<td><input id="workspace-columns-'+ id +'" name="workspace-columns" type="number" min="1" value="1" /></td>' +
                                    '</tr>' +

                                '</tbody>' +
                            '</table>' +

                            '<table class="data-table">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th colspan="2">Nutzung bei</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>' +

                                    '<tr class="odd">' +
                                        '<td><label for="workspace-minWidth-'+ id +'">Fenster Breite</label></td>' +
                                        '<td><input id="workspace-minWidth-'+ id +'" name="workspace-minWidth" type="number" min="1" value="'+ size.x +'" /></td>' +
                                    '</tr>' +
                                    '<tr class="even">' +
                                        '<td><label for="workspace-minHeight-'+ id +'">Fenster Höhe</label></td>' +
                                        '<td><input id="workspace-minHeight-'+ id +'" name="workspace-minHeight" type="number" min="1" value="'+ size.y +'" /></td>' +
                                    '</tr>' +

                                '</tbody>' +
                            '</table>'
                        );
                    },

                    onSubmit : function(Win)
                    {
                        var Content = Win.getContent(),
                            id      = Win.getId(),
                            size    = document.getSize(),

                            Title      = Content.getElement( 'input[name="workspace-title"]' ),
                            Columns    = Content.getElement( 'input[name="workspace-columns"]' ),
                            minWidth   = Content.getElement( 'input[name="workspace-minWidth"]' ),
                            minHeight  = Content.getElement( 'input[name="workspace-minHeight"]' );

                        if ( Title.value === '' ) {
                            return;
                        }

                        if ( Columns.value === '' ) {
                            return;
                        }


                        Win.Loader.show();

                        // create workspace for serialize
                        var Workspace = new QUIWorkspace(),
                            Parent    = self.$Elm.clone(),
                            columns   = ( Columns.value ).toInt();

                        Workspace.inject( Parent );

                        for ( var i = 0; i < columns; i++ )
                        {
                            Workspace.appendChild(
                                new QUIColumn({
                                    height : size.y,
                                    width  : ( size.x / columns ).ceil()
                                })
                            );
                        }

                        self.add({
                            title     : Title.value,
                            data      : JSON.encode( Workspace.serialize() ),
                            minWidth  : minWidth.value,
                            minHeight : minHeight.value
                        }, function()
                        {
                            Win.close();

                            self.load();
                        });
                    }
                }
            }).open();
        },

        /**
         * Open available panel window
         *
         * @param {qui/controls/desktop/Column} Column - parent column
         */
        openPanelList : function(Column)
        {
            if ( typeof Column === 'undefined' ) {
                return;
            }

            var self = this;

            new QUIWindow({
                title     : 'Panel Liste',
                buttons   : false,
                maxWidth  : 500,
                maxHeight : 700,
                events    :
                {
                    onResize : function() {
                        Column.highlight();
                    },

                    onOpen : function(Win)
                    {
                        Win.Loader.show();

                        Column.highlight();

                        // loads available panels
                        self.getAvailablePanels(function(panels)
                        {
                            var i, len, Elm, Icon;
                            var Content = Win.getContent();

                            for ( i = 0, len = panels.length; i < len; i++ )
                            {
                                Icon = null;

                                Elm = new Element('div', {
                                    html : '<h2>'+ panels[ i ].title +'</h2>'+
                                           '<p>'+ panels[ i ].text +'</p>',
                                    'class' : 'qui-controls-workspace-panelList-panel smooth',
                                    'data-require' : panels[ i ].require,
                                    events :
                                    {
                                        click : function()
                                        {
                                            self.appendControlToColumn(
                                                this.get( 'data-require' ),
                                                Column
                                            );
                                        }
                                    }
                                }).inject( Content );


                                if ( QUIControlUtils.isFontAwesomeClass( panels[ i ].image ) )
                                {
                                    Icon = new Element('div', {
                                        'class' : 'qui-controls-workspace-panelList-panel-icon'
                                    });

                                    Icon.addClass( panels[ i ].image );
                                    Icon.inject( Elm, 'top' );
                                }
                            }


                            Win.Loader.hide();
                        });
                    },

                    onCancel : function() {
                        Column.normalize();
                    }
                }
            }).open();
        },

        /**
         * opens the workspace edit window
         * edit / delete your workspaces
         */
        openWorkspaceEdit : function()
        {
            var self = this;

            new QUIWindow({
                title     : 'Arbeitsbereiche',
                buttons   : false,
                maxWidth  : 500,
                maxHeight : 700,
                events    :
                {
                    onOpen : function(Win)
                    {
                        Win.Loader.show();

                        var Content       = Win.getContent(),
                            size          = Content.getSize(),
                            GridContainer = new Element( 'div' ).inject( Content );

                        new Element('p', {
                            html : 'Editieren Sie per Doppelklick ihre Arbeitsbereiche',
                            styles : {
                                marginBottom : 10
                            }
                        }).inject( Content, 'top' );

                        var EditGrid = new Grid(GridContainer, {
                            columnModel : [{
                                dataIndex : 'id',
                                dataType  : 'Integer',
                                hidden  : true
                            }, {
                                header    : 'Title',
                                dataIndex : 'title',
                                dataType  : 'string',
                                width     : 200,
                                editable  : true
                            }, {
                                header    : 'Fenster Breite',
                                dataIndex : 'minWidth',
                                dataType  : 'string',
                                width     : 100,
                                editable  : true
                            }, {
                                header    : 'Fenster Höhe',
                                dataIndex : 'minHeight',
                                dataType  : 'string',
                                width     : 100,
                                editable  : true
                            }],
                            buttons : [{
                                text      : 'Markierte Arbeitsbereiche löschen',
                                textimage : 'icon-trash',
                                disabled  : true,
                                events    :
                                {
                                    onClick : function(Btn)
                                    {
                                        // delete selected workspaces
                                        var Grid = Btn.getAttribute( 'Grid' ),
                                            data = Grid.getSelectedData(),
                                            ids  = [];

                                        for ( var i = 0, len = data.length; i < len; i++ ) {
                                            ids.push( data[ i ].id );
                                        }

                                        Win.close();

                                        new QUIConfirm({
                                            icon   : 'icon-trash',
                                            title  : 'Arbeitsbereiche löschen?',
                                            text   : 'Möchten Sie folgende Arbeitsbereiche wirklich löschen?',
                                            information : ids.join( ',' ),
                                            maxWidth  : 500,
                                            maxHeight : 400,
                                            autoclose : false,
                                            events :
                                            {
                                                onCancel : function() {
                                                    self.openWorkspaceEdit();
                                                },
                                                onSubmit : function(Win)
                                                {
                                                    Win.Loader.show();

                                                    self.del(ids, function()
                                                    {
                                                        self.load(function()
                                                        {
                                                            Win.close();

                                                            self.openWorkspaceEdit();
                                                        });
                                                    });
                                                }
                                            }
                                        }).open();
                                    }
                                }
                            }],
                            showHeader : true,
                            sortHeader : true,
                            width      : size.x - 40,
                            height     : size.y - 60,
                            multipleSelection : true,
                            editable          : true,
                            editondblclick    : true
                        });

                        var workspaces = self.getList(),
                            data       = [];

                        Object.each( workspaces, function(Workspace) {
                            data.push( Workspace )
                        });

                        EditGrid.setData({
                            data : data
                        });

                        var DelButton = EditGrid.getButtons()[ 0 ];

                        EditGrid.addEvents({
                            onClick : function(event)
                            {
                                var sels = EditGrid.getSelectedData();

                                if ( sels.length )
                                {
                                    DelButton.enable();
                                } else
                                {
                                    DelButton.disable();
                                }
                            },

                            onEditComplete : function(data)
                            {
                                Win.Loader.show();

                                var newValue = data.input.value,
                                    oldValue = data.oldvalue,
                                    index    = data.columnModel.dataIndex,
                                    Data     = EditGrid.getDataByRow( data.row ),
                                    newData  = {};

                                newData[ index ] = newValue;

                                self.edit( Data.id, newData, function()
                                {
                                    self.load(function() {
                                        Win.Loader.hide();
                                    });
                                });
                            }
                        });

                        Win.Loader.hide();
                    }
                }
            }).open();
        }
    });

});
