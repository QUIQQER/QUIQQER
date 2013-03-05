/**
 * Media ContextMenu for a Media Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/project/media/PanelContextMenu
 * @package com.pcsg.qui.js.controls.project.media.PanelContextMenu
 * @namespace QUI.controls.project.media
 */

define('controls/project/media/PanelContextMenu', [

    'controls/project/media/Panel'

], function()
{
    QUI.namespace( 'controls.project.media' );

    QUI.controls.project.media.PanelContextMenu = new Class({

        /**
         * @constructor
         *
         * @param {QUI.controls.project.media.Panel} MediaPanel
         */
        initialize : function(MediaPanel)
        {
            this.$MediaPanel = MediaPanel;
        },

        /**
         * Return the parent media panel
         *
         * @return {QUI.controls.project.media.Panel}
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

            Menu.setPosition( event.page.x, event.page.y )
                .setTitle( Elm.get('title') )
                .show()
                .focus();
        },

        /**
         * Create the context menu for the panel
         *
         * @method QUI.controls.project.media.PanelContextMenu#createPanelMenu
         * @param {QUI.controls.contextmenu.Menu} Menu
         */
        createPanelMenu : function(Menu)
        {
            Menu.clearChildren()

                .setTitle( this.getPanel().$File.getAttribute('name') )

                .appendChild(
                    new QUI.controls.contextmenu.Item({
                        name    : 'create_folder',
                        text    : 'Neuen Ordner erstellen',
                        icon    : URL_BIN_DIR +'16x16/folder.png',
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
         * @return {QUI.controls.contextmenu.Menu}
         */
        getFileMenu : function(DOMNode)
        {
            var Menu = this.$MediaPanel.$Panel.getContextMenu();

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
                Trash = new QUI.controls.contextmenu.Item({
                    name    : 'delete',
                    text    : 'In den Mülleimer werfen',
                    icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().deleteItem( DOMNode );
                        }
                    }
                });
            } else
            {
                Trash = new QUI.controls.contextmenu.Item({
                    name : 'delete',
                    text : 'In den Mülleimer werfen',
                    icon : URL_BIN_DIR +'16x16/trashcan_empty.png'
                });

                Trash.appendChild(
                    new QUI.controls.contextmenu.Item({
                        name    : 'delete',
                        text    : DOMNode.get('title'),
                        //icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                        Control : this,
                        DOMNode : DOMNode,
                        events  :
                        {
                            onMouseDown : function(Item, event)
                            {
                                var Control = Item.getAttribute('Control'),
                                    DOMNode = Item.getAttribute('DOMNode');

                                if ( !DOMNode ) {
                                    return;
                                }

                                Control.getPanel().deleteItem( DOMNode );
                            }
                        }
                    })
                ).appendChild(
                    new QUI.controls.contextmenu.Item({
                        name    : 'delete',
                        text    : 'Alle markierte Elemente',
                        //icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                        Control : this,
                        DOMNode : DOMNode,
                        events  :
                        {
                            onMouseDown : function(Item, event)
                            {
                                var Control = Item.getAttribute('Control'),
                                    Panel   = Control.getPanel(),
                                    sels    = Panel.getSelectedItems();

                                Control.getPanel().deleteItems( sels );
                            }
                        }
                    })
                );
            }


            Menu.appendChild(
                new QUI.controls.contextmenu.Seperator()
            ).appendChild(
                Trash
            ).appendChild(
                new QUI.controls.contextmenu.Seperator()
            );

            Menu.appendChild(
                new QUI.controls.contextmenu.Item({
                    name    : 'replace',
                    text    : 'Datei ersetzen ...',
                    icon    : URL_BIN_DIR +'16x16/replace.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().replaceItem( DOMNode );
                        }
                    }
                })
            );

            // if no error, you can download the file
            if ( !DOMNode.get('data-error').toInt() )
            {
                Menu.appendChild(
                    new QUI.controls.contextmenu.Item({
                        name    : 'download',
                        text    : 'Datei herunterladen',
                        icon    : URL_BIN_DIR +'16x16/down.png',
                        Control : this,
                        DOMNode : DOMNode,
                        events  :
                        {
                            onMouseDown : function(Item, event)
                            {
                                var Control = Item.getAttribute('Control'),
                                    DOMNode = Item.getAttribute('DOMNode');

                                if ( !DOMNode ) {
                                    return;
                                }

                                Control.getPanel().downloadFile(
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

            var Menu  = this.$MediaPanel.$Panel.getContextMenu(),
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
                    new QUI.controls.contextmenu.Item({
                        name : 'copy-files',
                        text : 'Datei ersetzen mit '+ Element.name,
                        icon : URL_BIN_DIR +'16x16/replace.png',

                        File    : Element,
                        DOMNode : Droppable,
                        Control : this,

                        events :
                        {
                            onMouseDown : function(Item, event)
                            {
                                event.stop();

                                var File    = Item.getAttribute('File'),
                                    DomNode = Item.getAttribute('DOMNode'),
                                    Control = Item.getAttribute('Control'),
                                    Panel   = Control.getPanel();

                                Panel.$Media.replace(
                                    DomNode.get('data-id'),
                                    File,
                                    function(File)
                                    {
                                        this.refresh();
                                    }.bind( Panel )
                                );
                            }
                        }
                    })

                ).appendChild(
                    new QUI.controls.contextmenu.Seperator()
                ).appendChild(
                    new QUI.controls.contextmenu.Item({
                        name : 'cancel',
                        text : 'Abbrechen',
                        icon : URL_BIN_DIR +'16x16/cancel.png'
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
                    new QUI.controls.contextmenu.Item({
                        name : 'upload-files',
                        text : 'An diese Stelle hochladen',
                        icon : URL_BIN_DIR +'16x16/upload.png',

                        files   : Element,
                        DOMNode : Droppable,
                        Control : this,

                        events :
                        {
                            onMouseDown : function(Item, event)
                            {
                                event.stop();

                                var DOMNode = Item.getAttribute('DOMNode'),
                                    files   = Item.getAttribute('files'),
                                    Control = Item.getAttribute('Control'),
                                    Media   = Control.getPanel().getMedia();

                                Media.get( DOMNode.get('data-id'), function(Item)
                                {
                                    Item.uploadFiles( files, function() {
                                        Control.getPanel().refresh();
                                    });
                                });
                            }
                        }
                    })
                ).appendChild(
                    new QUI.controls.contextmenu.Seperator()
                ).appendChild(
                    new QUI.controls.contextmenu.Item({
                        name : 'cancel',
                        text : 'Abbrechen',
                        icon : URL_BIN_DIR +'16x16/cancel.png'
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
                new QUI.controls.contextmenu.Item({
                    name : 'copy-files',
                    text : 'An diese Stelle kopieren',
                    icon : URL_BIN_DIR +'16x16/copy.png',

                    ids     : ids,
                    id      : id,
                    Control : this,

                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            event.stop();

                            Item.getAttribute('Control')
                                .getPanel()
                                .copyTo(
                                    Item.getAttribute('id'),
                                    Item.getAttribute('ids')
                                );
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name : 'cut-files',
                    text : 'An diese Stelle verschieben',
                    icon : URL_BIN_DIR +'16x16/cut.png',

                    ids     : ids,
                    id      : id,
                    Control : this,

                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            event.stop();

                            Item.getAttribute('Control')
                                .getPanel()
                                .moveTo(
                                    Item.getAttribute('id'),
                                    Item.getAttribute('ids')
                                );
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Seperator()
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name : 'cancel',
                    text : 'Abbrechen',
                    icon : URL_BIN_DIR +'16x16/cancel.png'
                    // do nothing ^^
                })
            );

            Menu.show().focus();
        },

        /**
         * Return the context menu for the folder
         *
         * @param {DOMNode} DOMNode - DOM media item element
         * @return {QUI.controls.contextmenu.Menu}
         */
        getFolderMenu : function(DOMNode)
        {
            var Menu = this.$MediaPanel.$Panel.getContextMenu();

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
                new QUI.controls.contextmenu.Seperator()
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name    : 'rename',
                    text    : 'Umbenennen',
                    icon    : URL_BIN_DIR +'16x16/folder.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().renameItem( DOMNode );
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name    : 'delete',
                    text    : 'In den Mülleimer werfen',
                    icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().deleteItem( DOMNode );
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
         * @return {QUI.controls.contextmenu.Item}
         */
        getActivateItem : function(DOMNode)
        {
            var sels = this.getPanel().getSelectedItems();

            if ( !sels.length || sels.length == 1 )
            {
                return new QUI.controls.contextmenu.Item({
                    name    : 'activate',
                    text    : 'Aktivieren',
                    icon    : URL_BIN_DIR +'16x16/active.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().activateItem( DOMNode );
                        }
                    }
                });
            }

            var Activate = new QUI.controls.contextmenu.Item({
                name    : 'activate',
                text    : 'Aktivieren',
                icon    : URL_BIN_DIR +'16x16/active.png'
            });

            Activate.appendChild(
                new QUI.controls.contextmenu.Item({
                    name    : 'activate',
                    text    : DOMNode.get('title'),
                    //icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().activateItem( DOMNode );
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name    : 'activate',
                    text    : 'Alle markierte Elemente',
                    //icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                Panel   = Control.getPanel(),
                                sels    = Panel.getSelectedItems();

                            Control.getPanel().activateItems( sels );
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
         * @return {QUI.controls.contextmenu.Item}
         */
        getDeActivateItem : function(DOMNode)
        {
            var sels = this.getPanel().getSelectedItems();

            if ( !sels.length || sels.length == 1 )
            {
                return new QUI.controls.contextmenu.Item({
                    name    : 'deactivate',
                    text    : 'Deaktivieren',
                    icon    : URL_BIN_DIR +'16x16/deactive.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().deactivateItem( DOMNode );
                        }
                    }
                });
            }

            var Deactivate = new QUI.controls.contextmenu.Item({
                name    : 'deactivate',
                text    : 'Deaktivieren',
                icon    : URL_BIN_DIR +'16x16/deactive.png'
            });

            Deactivate.appendChild(
                new QUI.controls.contextmenu.Item({
                    name    : 'deactivate',
                    text    : DOMNode.get('title'),
                    //icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                DOMNode = Item.getAttribute('DOMNode');

                            if ( !DOMNode ) {
                                return;
                            }

                            Control.getPanel().deactivateItem( DOMNode );
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name    : 'deactivate',
                    text    : 'Alle markierte Elemente',
                    //icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                    Control : this,
                    DOMNode : DOMNode,
                    events  :
                    {
                        onMouseDown : function(Item, event)
                        {
                            var Control = Item.getAttribute('Control'),
                                Panel   = Control.getPanel(),
                                sels    = Panel.getSelectedItems();

                            Control.getPanel().deactivateItems( sels );
                        }
                    }
                })
            );

            return Deactivate;
        }

    });

    return QUI.controls.project.media.PanelContextMenu;
});