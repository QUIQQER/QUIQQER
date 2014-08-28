
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

    var QUI           = arguments[ 0 ],
        QUIControl    = arguments[ 1 ],
        QUILoader     = arguments[ 2 ],
        QUIWorkspace  = arguments[ 3 ],
        QUIColumn     = arguments[ 4 ],
        QUIPanel      = arguments[ 5 ],
        QUITasks      = arguments[ 6 ],
        MessagePanel  = arguments[ 7 ],
        WelcomePanel  = arguments[ 8 ],
        HelpPanel     = arguments[ 9 ],
        BookmarkPanel = arguments[ 10 ],
        ProjectPanel  = arguments[ 11 ],
        Ajax          = arguments[ 12 ],
        UploadManager = arguments[ 13 ];


    return new Class({

        Extends : QUIControl,
        Type    : 'controls/workspace/Manager',

        Binds : [
            'resize',
            'save',
            '$onInject'
        ],

        options : {
            autoResize : true
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
                    var size = self.$Elm.getSize();

                    if ( size.x < 1300 )
                    {
                        self.$loadDefault2Column();
                    } else
                    {
                        self.$loadDefault3Column();
                    }

                    self.resize();
                    self.Loader.hide();
                    return;
                }

                for ( var i = 0, len = list.length; i < len; i++ ) {
                    self.$spaces[ list[ i ].id ] = list[ i ];
                }



                console.log( list );

                self.resize();
                self.Loader.hide();
            });
        },

        /**
         * Save the workspace
         */
        save : function()
        {
            Ajax.syncRequest('ajax_desktop_workspace_save', 'post', {
                data : JSON.encode( this.Workspace.serialize() )
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
         */
        $loadDefault3Column : function()
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


            this.Workspace.appendChild( LeftColumn );
            this.Workspace.appendChild( MiddleColumn );
            this.Workspace.appendChild( RightColumn );

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

            this.Workspace.fix();
        },

        /**
         * loads the default 2 column workspace
         */
        $loadDefault2Column : function()
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


            this.Workspace.appendChild( LeftColumn );
            this.Workspace.appendChild( MiddleColumn );

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

            this.Workspace.fix();
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
                Messages  : new MessagePanel(),
                Uploads   : UploadManager,
                Help      : new HelpPanel()
            };
        }
    });

});
