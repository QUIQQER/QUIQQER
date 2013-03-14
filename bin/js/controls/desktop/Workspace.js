/**
 * Workspace
 *
 * You can append the Workspace with columns and panels
 * Save the Workspace and load the workspace
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/Workspace
 * @package pcsg.qui.js.controls.desktop.workspace
 * @namespace QUI.controls.desktop
 *
 * @event onLoad - if the workspace is loaded
 */

define('controls/desktop/Workspace', [

    'controls/Control',
    'controls/loader/Loader'

], function(QUI_Control)
{
    QUI.namespace( 'controls.desktop' );

    /**
     * @class QUI.controls.desktop.Workspace
     *
     * @param {DOMNode} Parent - Parent node
     * @param {Object} options . QDOM params
     */
    QUI.controls.desktop.Workspace = new Class({

        Implements : [ QUI_Control ],

        initialize : function(Parent, options)
        {
            this.init( options );

            this.$Parent = Parent;
            this.$Loader = null;

            this.$available_panels = {};
        },

        /**
         * Load the Workspace
         *
         * @method QUI.controls.desktop.Workspace#load
         * @return {this}
         */
        load : function()
        {
            this.inject( this.$Parent );

            this.$Loader.show();

            require([

                "classes/desktop",
                "controls/desktop/Column",
                "controls/desktop/Panel",
                "Bookmarks"

            ], function()
            {
                var workspace = QUI.Storage.get( 'qui.workspace' );

                if ( workspace ) {
                    workspace = JSON.decode( workspace );
                }

                if ( !workspace ) {
                    workspace = [];
                }


                if ( workspace.length )
                {
                    var i, len, Column;

                    var width     = 0,
                        max_width = this.$Elm.getSize().x;

                    // make columns
                    for ( i = 0, len = workspace.length; i < len; i++ )
                    {
                        Column = new QUI.controls.desktop.Column(
                            workspace[ i ].attributes
                        );

                        Column.setParent( this );

                        if ( workspace[ i ].children ) {
                            Column.unserialize( workspace[ i ] );
                        }

                        Column.inject( this.$Elm );
                    }
                } else
                {
                    this.defaultSpace();
                }

                this.fireEvent( 'load' );
                this.$Loader.hide();

            }.bind( this ));

            return this;
        },

        /**
         * Save the Workspace to the localstorage
         *
         * @return {Bool}
         */
        save : function()
        {
            var i, len, p, plen,
                panels, children, Column;

            var columns = this.$Elm.getChildren( '.qui-column' ),
                result  = [];

            for ( i = 0, len = columns.length; i < len; i++ )
            {
                Column = QUI.Controls.getById(
                    columns[i].get( 'data-quiid' )
                );

                result.push( Column.serialize() );
            }

            QUI.Storage.set( 'qui.workspace', JSON.encode( result ) );

            return true;
        },

        /**
         * Create the DOMNode
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div.qui-workspace', {
                styles : {
                    height : '100%',
                    width  : '100%',
                    'float' : 'left'
                }
            });

            this.$Loader = new QUI.controls.loader.Loader({
                styles : {
                    zIndex : 100
                }
            }).inject( this.$Elm );

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            return this.$Elm;
        },

        /**
         * Add a available panel
         *
         * @param {Object} params - parameter {
         *     require : '',
         *     text    : '',
         *     icon    : ''
         * }
         *
         * @return {this}
         */
        addAvailablePanel : function(params)
        {
            if ( typeof params.require === 'undefined' ) {
                return this;
            }

            if ( typeof params.text === 'undefined' ) {
                return this;
            }

            if ( typeof params.icon === 'undefined' ) {
                return this;
            }


            if ( typeof this.$available_panels[ params.require ] !== 'undefined' ) {
                return this;
            }

            this.$available_panels[ params.require ] = params;

            return this;
        },

        /**
         * Return all available Panels for that Workbench
         *
         * @return {Array}
         */
        getAvailablePanel : function()
        {
            var panels = [],
                list   = this.$available_panels;

            for ( var i in list ) {
                panels.push( list[ i ] );
            }

            return panels;
        },

        /**
         * load the default workspace
         */
        defaultSpace : function()
        {
            var content_width = this.$Parent.getSize().x,
                control_width = content_width / 3;

            if ( control_width > 300 ) {
                control_width = 300;
            }

            /**
             * left column
             */
            new QUI.controls.desktop.Column({
                name        : 'control-colum',
                placement   : 'left',
                width       : control_width,
                resizeLimit : [100, 300],
                closable    : true,
                events      :
                {
                    onCreate : function(Column)
                    {
                        require([
                            'controls/projects/Panel',
                            'controls/desktop/panels/Bookmarks'
                        ],

                        function(QUI_ProjectsPanel, QUI_Bookmark)
                        {
                            Column.appendChild(
                                new QUI_ProjectsPanel()
                            );

                            // favourite start
                            var Bookmars = new QUI_Bookmark({
                                title  : 'Lesezeichen',
                                header : true,
                                height : 300
                            });

                            Column.appendChild( Bookmars );


                            Bookmars.appendChild(
                                new QUI.controls.contextmenu.Item({
                                    text     : 'Zu den Benutzern',
                                    icon     : URL_BIN_DIR +'16x16/user.png',
                                    bookmark : 'QUI.Bookmarks.openUsers'
                                })
                            ).appendChild(
                                new QUI.controls.contextmenu.Item({
                                    text     : 'Zu den Gruppen',
                                    icon     : URL_BIN_DIR +'16x16/groups.png',
                                    bookmark : 'QUI.Bookmarks.openGroups'
                                })
                            ).appendChild(
                                new QUI.controls.contextmenu.Item({
                                    text     : 'Zum Mülleimer',
                                    icon     : URL_BIN_DIR +'16x16/trashcan_empty.png',
                                    bookmark : 'QUI.Bookmarks.openTrash'
                                })
                            );

                        });
                    }
                }
            }).inject( this.$Elm );

            /**
             * middle column
             */
            var ContentColumn = new QUI.controls.desktop.Column({
                name        : 'content-colum',
                width       : content_width - control_width - 250,
                resizeLimit : [200, content_width - 210],
                placement   : 'main'
            }).inject( this.$Elm );

            require(['controls/desktop/Tasks'], function(Taskpanel)
            {
                ContentColumn.appendChild(
                    new Taskpanel({
                        name : 'content-panel'
                    })
                );

                // create the desktop
                require(['controls/desktop/panels/Desktop'], function(QUI_Desktop)
                {
                    var panels = QUI.Controls.get( 'content-panel' );

                    if ( !panels.length ) {
                        return;
                    }

                    panels[ 0 ].appendChild(
                        new QUI_Desktop({
                            closeable : false
                        })
                    );

                    panels[ 0 ].firstChild().click();
                });

            }.bind( ContentColumn ));

            /**
             * Right column
             */
            var RightColumn = new QUI.controls.desktop.Column({
                name        : 'right-colum',
                placement   : 'right',
                width       : 250,
                resizeLimit : [200, content_width],
                closable    : true
            }).inject( this.$Elm );

            RightColumn.appendChild(
                new QUI.controls.desktop.Panel({
                    name   : 'error-console',
                    title  : 'Meldungen',
                    header : true,
                    height : 300,
                    events :
                    {
                        onCreate : function(Panel)
                        {
                            Panel.getBody().addClass('box-sizing');
                            Panel.getBody().setStyles({
                                padding : 0,
                                width   : '100%'
                            });
                        }
                    }
                })
            );

            RightColumn.appendChild(
                new QUI.controls.desktop.Panel({
                    name   : 'help',
                    title  : 'Hilfe'
                })
            );
        }
    });

    return QUI.controls.desktop.Workspace;
});