
/**
 * Workspace Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/workspace/Manager
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
    'qui/controls/messages/Panel',

    'controls/welcome/Panel',
    'controls/desktop/panels/Help',
    'controls/desktop/panels/Bookmarks',
    'controls/projects/project/Panel',
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
        QUIMessagePanel = arguments[ 8 ],

        WelcomePanel  = arguments[ 9 ],
        HelpPanel     = arguments[ 10 ],
        BookmarkPanel = arguments[ 11 ],
        ProjectPanel  = arguments[ 12 ],
        Ajax          = arguments[ 13 ],
        UploadManager = arguments[ 14 ];


    return new Class({

        Extends : QUIControl,
        Type    : 'controls/workspace/Manager',

        Binds : [
            'resize',
            'save',
            '$onInject'
        ],

        options : {
            autoResize  : true,
            workspaceId : false
        },

        initialize : function(options)
        {
            var self = this;

            this.Loader    = new QUILoader();
            this.Workspace = new QUIWorkspace();

            this.$spaces = {};

            this.$minWidth   = false;
            this.$minHeight  = false;
            this.$ParentNode = null;

            this.addEvents({
                onInject : this.$onInject
            });

            if ( this.getAttribute( 'autoResize' ) ) {
                window.addEvent( 'resize', this.resize );
            }

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
            if ( !this.$ParentNode ) {
                return;
            }

            var size   = this.$ParentNode.getSize(),
                width  = size.x,
                height = size.y;

            this.$Elm.setStyle( 'overflow', null );

            if ( width < this.$minWidth )
            {
                width = this.$minWidth;

                this.$Elm.setStyle( 'overflow', 'auto' );
            }

            if ( height < this.$minHeight )
            {
                height = this.$minHeight;

                this.$Elm.setStyle( 'overflow', 'auto' );
            }


            this.Workspace.setWidth( width );
            this.Workspace.setHeight( height );
            this.Workspace.resize();
        },

        /**
         * load the workspace for the user
         */
        load : function()
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
                        self.add( colums3, function()
                        {
                            self.load();
                        });
                    });

                    return;
                }

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

            this.Workspace.clear();
            this.Workspace.unserialize(
                JSON.decode( this.$spaces[ id ].data )
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
         * Add an Workspace
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
        }
    });

});
