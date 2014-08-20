/**
 * Media ContextMenu for a Media Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module classes/projects/project/media/panel/ContextMenu
 */

define('classes/projects/project/media/panel/ContextMenu', [

    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Seperator',
    'qui/utils/Elements'

], function(QUIContextmenuItem, QUIContextmenuSeperator, QUIElementUtil)
{
    "use strict";

    /**
     * @class classes/projects/project/media/panel/ContextMenu
     * @param {controls/projects/project/media/Panel} MediaPanel
     *
     * @memberof! <global>
     */
    return new Class({

        Type : 'classes/projects/project/media/panel/ContextMenu',

        /**
         * @constructor
         *
         * @param {controls/projects/project/media/Panel} MediaPanel
         */
        initialize : function(MediaPanel)
        {
            this.$MediaPanel = MediaPanel;
        },

        /**
         * Return the parent media panel
         *
         * @return {controls/projects/project/media/Panel}
         */
        getPanel : function()
        {
            return this.$MediaPanel;
        },

        /**
         * Show the menu for the specific event.target
         *
         * @param {DOMEvent} event - oncontextmenu DOMEvent
         */
        show : function(event)
        {
            event.stop();

            var Menu;
            var Elm = event.target;

            if ( Elm.nodeName == 'SPAN' ) {
                Elm = Elm.getParent('div');
            }

            if ( Elm.get('data-type') === 'folder' )
            {
                Menu = this.getFolderMenu( Elm );
            } else
            {
                Menu = this.getFileMenu( Elm );
            }

            // zIndex
            Menu.getElm().setStyle(
                'zIndex',
                QUIElementUtil.getComputedZIndex( Elm ) + 1
            );

            Menu.setPosition( event.page.x, event.page.y )
                .setTitle( Elm.get('title') )
                .show()
                .focus();
        },

        /**
         * Create the context menu for the panel
         *
         * @method classes/projects/project/media/panel/ContextMenu#createPanelMenu
         * @param {qui/controls/contextmenu/Menu} Menu
         */
        createPanelMenu : function(Menu)
        {
            Menu.clearChildren()
                .setTitle( this.getPanel().$File.getAttribute('name') )
                .appendChild(
                    new QUIContextmenuItem({
                        name    : 'create_folder',
                        text    : 'Neuen Ordner erstellen',
                        icon    : 'icon-folder',
                        Control : this,
                        events  :
                        {
                            onMouseDown : function(Item, event)
                            {
                                Item.getAttribute('Control')
                                    .getPanel()
                                    .createFolder();
                            }
                        }
                    })
                );
        },

        /**
         * Return the context menu für a media item
         *
         * @param {DOMNode} DOMNode - DOM media item element
         * @return {qui/controls/contextmenu/Menu}
         */
        getFileMenu : function(DOMNode)
        {
            var self = this,
                Menu = this.getPanel().getContextMenu();

            Menu.clearChildren();


            if ( DOMNode.get('data-active').toInt() === 0 )
            {
                Menu.appendChild(
                    this.getActivateItem( DOMNode )
                );
            } else
            {
                Menu.appendChild(
                    this.getDeActivateItem( DOMNode )
                );
            }


            var Trash;
            var sels = this.getPanel().getSelectedItems();

            if ( !sels.length || sels.length == 1 )
            {
                Trash = new QUIContextmenuItem({
                    name   : 'delete',
                    text   : 'In den Mülleimer werfen',
                    icon   : 'icon-trash',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            self.getPanel().deleteItem( DOMNode );
                        }
                    }
                });
            } else
            {
                Trash = new QUIContextmenuItem({
                    name : 'delete',
                    text : 'In den Mülleimer werfen',
                    icon : 'icon-trash'
                });

                Trash.appendChild(
                    new QUIContextmenuItem({
                        name   : 'delete',
                        text   : DOMNode.get('title'),
                        events :
                        {
                            onMouseDown : function(Item, event)
                            {
                                if ( !DOMNode ) {
                                    return;
                                }

                                self.getPanel().deleteItem( DOMNode );
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuItem({
                        name   : 'delete',
                        text   : 'Alle markierte Elemente',
                        events :
                        {
                            onMouseDown : function(Item, event)
                            {
                                var Panel   = self.getPanel(),
                                    sels    = Panel.getSelectedItems();

                                self.getPanel().deleteItems( sels );
                            }
                        }
                    })
                );
            }


            Menu.appendChild(
                new QUIContextmenuSeperator()
            ).appendChild(
                Trash
            ).appendChild(
                new QUIContextmenuSeperator()
            );

            Menu.appendChild(
                new QUIContextmenuItem({
                    name   : 'replace',
                    text   : 'Datei ersetzen ...',
                    icon   : 'icon-retweet',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            self.getPanel().replaceItem( DOMNode );
                        }
                    }
                })
            );

            // if no error, you can download the file
            if ( !DOMNode.get('data-error').toInt() )
            {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name   : 'download',
                        text   : 'Datei herunterladen',
                        icon   : 'icon-download',
                        events :
                        {
                            onMouseDown : function(Item, event)
                            {
                                if ( !DOMNode ) {
                                    return;
                                }

                                self.getPanel().downloadFile(
                                    DOMNode.get('data-id')
                                );
                            }
                        }
                    })
                );
            }

            return Menu;
        },

        /**
         * If the DragDrop was dropped to a droppable element
         *
         * @param {DOMNode|File} Element   - the dropabble element (media item div or File)
         * @param {DOMNode} Droppable - drop box element (folder)
         * @param {DOMEvent} event
         */
        showDragDropMenu : function(Element, Droppable, event)
        {
            if ( !Droppable ) {
                return;
            }

            var self  = this,
                Menu  = this.getPanel().getContextMenu(),
                title = Droppable.get('title'),

                pos   =  {
                    x : event.page.x,
                    y : event.page.y
                };


            if ( !pos.x && !pos.y )
            {
                pos = Droppable.getPosition();

                pos.x = pos.x + 50;
                pos.y = pos.y + 50;
            }

            Menu.clearChildren()
                .setPosition( pos.x, pos.y )
                .setTitle( title );


            if ( Droppable.get('data-type') != 'folder' )
            {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name : 'copy-files',
                        text : 'Datei ersetzen mit '+ Element.name,
                        icon : 'icon-retweet',
                        events :
                        {
                            onMouseDown : function(Item, event)
                            {
                                event.stop();

                                var Panel = self.getPanel();

                                Panel.$Media.replace(
                                    Droppable.get('data-id'),
                                    Element,
                                    function(File)
                                    {
                                        Panel.refresh();
                                    }
                                );
                            }
                        }
                    })

                ).appendChild(
                    new QUIContextmenuSeperator()
                ).appendChild(
                    new QUIContextmenuItem({
                        name : 'cancel',
                        text : 'Abbrechen',
                        icon : 'icon-remove'
                        // do nothing ^^
                    })
                );

                Menu.show().focus();

                return;
            }

            if ( instanceOf( Element, File ) ||
                 instanceOf( Element, FileList ) ||
                 instanceOf( Element, Array ) )
            {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name : 'upload-files',
                        text : 'An diese Stelle hochladen',
                        icon : 'icon-upload',
                        events :
                        {
                            onMouseDown : function(Item, event)
                            {
                                event.stop();

                                var Media = self.getPanel().getMedia();

                                Media.get( Droppable.get('data-id'), function(Item)
                                {
                                    Item.uploadFiles( Element, function() {
                                        self.getPanel().refresh();
                                    });
                                });
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuSeperator()
                ).appendChild(
                    new QUIContextmenuItem({
                        name : 'cancel',
                        text : 'Abbrechen',
                        icon : 'icon-remove'
                        // do nothing ^^
                    })
                );

                Menu.show().focus();

                return;
            }


            var ids = Element.get('data-ids').split(','),
                id  = Droppable.get('data-id');

            // show choices
            Menu.appendChild(
                new QUIContextmenuItem({
                    name : 'copy-files',
                    text : 'An diese Stelle kopieren',
                    icon : 'icon-copy',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            event.stop();

                            self.getPanel().copyTo( id, ids );
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name : 'cut-files',
                    text : 'An diese Stelle verschieben',
                    icon : 'icon-cut',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            event.stop();

                            self.getPanel().moveTo( id, ids );
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuSeperator()
            ).appendChild(
                new QUIContextmenuItem({
                    name : 'cancel',
                    text : 'Abbrechen',
                    icon : 'icon-remove'
                    // do nothing ^^
                })
            );

            Menu.show().focus();
        },

        /**
         * Return the context menu for the folder
         *
         * @param {DOMNode} DOMNode - DOM media item element
         * @return {qui/controls/contextmenu/Menu}
         */
        getFolderMenu : function(DOMNode)
        {
            var self = this,
                Menu = this.getPanel().getContextMenu();

            Menu.clearChildren();

            if ( DOMNode.get('data-active').toInt() === 0 )
            {
                Menu.appendChild(
                    this.getActivateItem( DOMNode )
                );
            } else
            {
                Menu.appendChild(
                    this.getDeActivateItem( DOMNode )
                );
            }

            Menu.appendChild(
                new QUIContextmenuSeperator()
            ).appendChild(
                new QUIContextmenuItem({
                    name   : 'rename',
                    text   : 'Umbenennen',
                    icon   : 'icon-font',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            self.getPanel().renameItem( DOMNode );
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name   : 'delete',
                    text   : 'In den Mülleimer werfen',
                    icon   : 'icon-trash',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            self.getPanel().deleteItem( DOMNode );
                        }
                    }
                })
            );

            return Menu;
        },

        /**
         * Return the activation menu item
         *
         * @param {DOMNode} DOMNode - DOM media item element
         * @return {qui/controls/contextmenu/Item}
         */
        getActivateItem : function(DOMNode)
        {
            var self = this,
                sels = this.getPanel().getSelectedItems();

            if ( !sels.length || sels.length == 1 )
            {
                return new QUIContextmenuItem({
                    name   : 'activate',
                    text   : 'Aktivieren',
                    icon   : 'icon-ok',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            self.getPanel().activateItem( DOMNode );
                        }
                    }
                });
            }

            var Activate = new QUIContextmenuItem({
                name    : 'activate',
                text    : 'Aktivieren',
                icon    : 'icon-ok'
            });

            Activate.appendChild(
                new QUIContextmenuItem({
                    name   : 'activate',
                    text   : DOMNode.get('title'),
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().activateItem( DOMNode );
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name   : 'activate',
                    text   : 'Alle markierte Elemente',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Panel   = self.getPanel(),
                                sels    = Panel.getSelectedItems();

                            self.getPanel().activateItems( sels );
                        }
                    }
                })
            );

            return Activate;
        },

        /**
         * Return the deactivation menu item
         *
         * @param {DOMNode} DOMNode - DOM media item element
         * @return {qui/controls/contextmenu/Item}
         */
        getDeActivateItem : function(DOMNode)
        {
            var self = this,
                sels = this.getPanel().getSelectedItems();

            if ( !sels.length || sels.length == 1 )
            {
                return new QUIContextmenuItem({
                    name    : 'deactivate',
                    text    : 'Deaktivieren',
                    icon    : 'icon-remove',
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            self.getPanel().deactivateItem( DOMNode );
                        }
                    }
                });
            }

            var Deactivate = new QUIContextmenuItem({
                name    : 'deactivate',
                text    : 'Deaktivieren',
                icon    : 'icon-remove'
            });

            Deactivate.appendChild(
                new QUIContextmenuItem({
                    name   : 'deactivate',
                    text   : DOMNode.get('title'),
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            if ( !DOMNode ) {
                                return;
                            }

                            self.getPanel().deactivateItem( DOMNode );
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name   : 'deactivate',
                    text   : 'Alle markierte Elemente',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Panel   = self.getPanel(),
                                sels    = Panel.getSelectedItems();

                            self.getPanel().deactivateItems( sels );
                        }
                    }
                })
            );

            return Deactivate;
        }
    });
});