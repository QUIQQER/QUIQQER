/**
 * Media ContextMenu for a Media Panel
 *
 * @module classes/projects/project/media/panel/ContextMenu
 * @author www.pcsg.de (Henning Leutz)
 */
define('classes/projects/project/media/panel/ContextMenu', [

    'qui/QUI',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Separator',
    'qui/utils/Elements',
    'Locale'

], function (QUI, QUIContextmenuItem, QUIContextmenuSeparator, QUIElementUtil, QUILocale) {
    "use strict";

    const lg = 'quiqqer/core';

    /**
     * @class classes/projects/project/media/panel/ContextMenu
     * @param {Object} MediaPanel - controls/projects/project/media/Panel
     *
     * @memberof! <global>
     */
    return new Class({

        Type: 'classes/projects/project/media/panel/ContextMenu',

        /**
         * @constructor
         *
         * @param {Object} MediaPanel - controls/projects/project/media/Panel
         */
        initialize: function (MediaPanel) {
            this.$MediaPanel = MediaPanel;
        },

        /**
         * Return the parent media panel
         *
         * @return {Object} controls/projects/project/media/Panel
         */
        getPanel: function () {
            return this.$MediaPanel;
        },

        /**
         * Show the menu for the specific event.target
         *
         * @param {DOMEvent} event - oncontextmenu DOMEvent
         */
        show: function (event) {
            event.stop();

            let Menu;
            let Elm = event.target;

            if (Elm.nodeName === 'SPAN') {
                Elm = Elm.getParent('div');
            }

            if (Elm.getParent('.qui-project-media-folderViewer-item')) {
                Elm = Elm.getParent('.qui-project-media-folderViewer-item');
            }

            if (Elm.get('data-type') === 'folder') {
                Menu = this.getFolderMenu(Elm);
            } else {
                Menu = this.getFileMenu(Elm);
            }

            // zIndex
            Menu.getElm().setStyle('zIndex', QUIElementUtil.getComputedZIndex(Elm) + 1);

            Menu.setPosition(event.page.x, event.page.y)
                .setTitle(Elm.get('title'))
                .show()
                .focus();
        },

        /**
         * Create the context menu for the panel
         *
         * @method classes/projects/project/media/panel/ContextMenu#createPanelMenu
         * @param {Object} Menu - qui/controls/contextmenu/Menu
         */
        createPanelMenu: function (Menu) {
            Menu.clearChildren()
                .setTitle(this.getPanel().$File.getAttribute('name'))
                .appendChild(
                    new QUIContextmenuItem({
                        name   : 'create_folder',
                        text   : QUILocale.get(lg, 'projects.project.site.media.panel.btn.create'),
                        icon   : 'fa fa-folder',
                        Control: this,
                        events : {
                            onMouseDown: function (Item) {
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
         * @param {HTMLElement} DOMNode - DOM media item element
         * @return {qui/controls/contextmenu/Menu}
         */
        getFileMenu: function (DOMNode) {
            let Trash;

            const self  = this,
                  Panel = this.getPanel(),
                  Menu  = Panel.getContextMenu();

            Menu.clearChildren();

            const isFolderViewer = DOMNode.hasClass('qui-project-media-folderViewer-item');

            if (parseInt(DOMNode.get('data-active')) === 0) {
                Menu.appendChild(
                    this.getActivateItem(DOMNode)
                );
            } else {
                Menu.appendChild(
                    this.getDeActivateItem(DOMNode)
                );
            }

            let sels = this.getPanel().getSelectedItems();
            const Content = Panel.getContent();

            if (!sels.length || sels.length === 1) {
                Trash = new QUIContextmenuItem({
                    name  : 'delete',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.trash'),
                    icon  : 'fa fa-trash-o',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().deleteItem(DOMNode);
                        }
                    }
                });
            } else {
                Trash = new QUIContextmenuItem({
                    name: 'delete',
                    text: QUILocale.get(lg, 'projects.project.panel.media.contextMenu.trash'),
                    icon: 'fa fa-trash-o'
                });

                Trash.appendChild(
                    new QUIContextmenuItem({
                        name  : 'delete',
                        text  : DOMNode.get('title'),
                        events: {
                            onMouseDown: function () {
                                if (!DOMNode) {
                                    return;
                                }

                                self.getPanel().deleteItem(DOMNode);
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuItem({
                        name  : 'delete',
                        text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.allElements'),
                        events: {
                            onMouseDown: function () {
                                const Panel = self.getPanel(),
                                      sels  = Panel.getSelectedItems();

                                self.getPanel().deleteItems(sels);
                            }
                        }
                    })
                );
            }

            const Move = new QUIContextmenuItem({
                name  : 'move',
                text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.move'),
                icon  : 'fa fa-cut',
                events: {
                    onMouseDown: function () {
                        if (!DOMNode) {
                            return;
                        }

                        if (!sels.length) {
                            sels = [DOMNode];
                        }

                        self.getPanel().moveItems(sels);
                    }
                }
            });


            Menu.appendChild(new QUIContextmenuSeparator());

            if (!sels.length) {
                sels = [DOMNode];
            }

            this.addHiddenMenu(Menu, sels);

            Menu.appendChild(
                new QUIContextmenuItem({
                    name  : 'rename',
                    text  : QUILocale.get('quiqqer/core', 'rename'),
                    icon  : 'fa fa-font',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().renameItem(DOMNode);
                        }
                    }
                })
            );

            Menu.appendChild(
                new QUIContextmenuItem({
                    name  : 'replace',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.replace'),
                    icon  : 'fa fa-retweet',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().replaceItem(DOMNode);
                        }
                    }
                })
            );

            // if no error, you can download the file
            if (!parseInt(DOMNode.get('data-error'))) {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name  : 'download',
                        text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.download'),
                        icon  : 'fa fa-download',
                        events: {
                            onMouseDown: function () {
                                if (!DOMNode) {
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

            Menu.appendChild(new QUIContextmenuSeparator());

            if (!isFolderViewer) {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name  : 'select-all',
                        text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.markAll'),
                        icon  : 'fa fa-hand-grab-o',
                        events: {
                            onMouseDown: function () {
                                Content.getElements('.qui-media-item').each(function (Item) {
                                    Item.addClass('selected');
                                    Panel.$selected.push(Item);
                                });
                            }
                        }
                    })
                );

                Menu.appendChild(new QUIContextmenuSeparator());
                Menu.appendChild(Move);
            }

            Menu.appendChild(Trash);

            return Menu;
        },

        /**
         *
         * @param event
         */
        showMediaMenu: function (event) {
            const Panel   = this.getPanel(),
                  Content = Panel.getContent(),
                  Menu    = Panel.getContextMenu();

            const pos = {
                x: event.page.x,
                y: event.page.y
            };

            Menu.clearChildren()
                .setPosition(pos.x, pos.y)
                .setTitle(Panel.getAttribute('title'));

            Menu.appendChild(
                new QUIContextmenuItem({
                    name  : 'select-all',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.markAll'),
                    icon  : 'fa fa-hand-grab-o',
                    events: {
                        onMouseDown: function () {
                            Content.getElements('.qui-media-item').each(function (Item) {
                                Item.addClass('selected');
                                Panel.$selected.push(Item);
                            });
                        }
                    }
                })
            );

            Menu.show().focus();
        },

        /**
         * If the DragDrop was dropped to a droppable element
         *
         * @param {HTMLElement|File} Element   - the dropabble element (media item div or File)
         * @param {HTMLElement} Droppable - drop box element (folder)
         * @param {DOMEvent} event
         */
        showDragDropMenu: function (Element, Droppable, event) {
            if (!Droppable) {
                return;
            }

            let id, ids;

            // check if it's the same id
            if (typeOf(Element) === 'element') {
                ids = Element.get('data-ids').split(',');
                id = Droppable.get('data-id');

                if (ids.length === 1 && ids[0] === id) {
                    return;
                }
            }

            const self  = this,
                  Menu  = this.getPanel().getContextMenu(),
                  title = Droppable.get('title');

            let pos = {
                x: event.page.x,
                y: event.page.y
            };


            if (!pos.x && !pos.y) {
                pos = Droppable.getPosition();

                pos.x = pos.x + 50;
                pos.y = pos.y + 50;
            }

            Menu.clearChildren()
                .setPosition(pos.x, pos.y)
                .setTitle(title);

            Menu.getElm().setStyle('zIndex', QUI.Windows.$getmaxWindowZIndex() + 10);

            if (Droppable.get('data-type') !== 'folder') {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name  : 'copy-files',
                        text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.replaceFile', {
                            file: Element.name
                        }),
                        icon  : 'fa fa-retweet',
                        events: {
                            onMouseDown: function (Item, event) {
                                event.stop();

                                const Panel = self.getPanel();

                                Panel.$Media.replace(
                                    Droppable.get('data-id'),
                                    Element,
                                    function () {
                                        Panel.refresh();
                                    }
                                );
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuSeparator()
                ).appendChild(
                    new QUIContextmenuItem({
                        name: 'cancel',
                        text: QUILocale.get(lg, 'cancel'),
                        icon: 'fa fa-remove'
                        // do nothing ^^
                    })
                );

                Menu.show().focus();

                return;
            }

            if (instanceOf(Element, File) ||
                instanceOf(Element, FileList) ||
                instanceOf(Element, Array)) {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name  : 'upload-files',
                        text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.uploadLocation'),
                        icon  : 'fa fa-upload',
                        events: {
                            onMouseDown: function (Item, event) {
                                event.stop();

                                const Media = self.getPanel().getMedia();

                                Media.get(Droppable.get('data-id'), function (Item) {
                                    Item.uploadFiles(Element, function () {
                                        self.getPanel().refresh();
                                    });
                                });
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuSeparator()
                ).appendChild(
                    new QUIContextmenuItem({
                        name: 'cancel',
                        text: QUILocale.get(lg, 'cancel'),
                        icon: 'fa fa-remove'
                        // do nothing ^^
                    })
                );

                Menu.show().focus();

                return;
            }


            ids = Element.get('data-ids').split(',');
            id = Droppable.get('data-id');

            // show choices
            Menu.appendChild(
                new QUIContextmenuItem({
                    name  : 'copy-files',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.copyLocation'),
                    icon  : 'fa fa-copy',
                    events: {
                        onMouseDown: function (Item, event) {
                            event.stop();

                            self.getPanel().copyTo(id, ids);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'cut-files',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.moveLocation'),
                    icon  : 'fa fa-cut',
                    events: {
                        onMouseDown: function (Item, event) {
                            event.stop();

                            self.getPanel().moveTo(id, ids);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuSeparator()
            ).appendChild(
                new QUIContextmenuItem({
                    name: 'cancel',
                    text: QUILocale.get(lg, 'cancel'),
                    icon: 'fa fa-remove'
                    // do nothing ^^
                })
            );

            Menu.show().focus();
        },

        /**
         * Return the context menu for the folder
         *
         * @param {HTMLElement} DOMNode - DOM media item element
         * @return {Object} qui/controls/contextmenu/Menu
         */
        getFolderMenu: function (DOMNode) {
            const self    = this,
                  Panel   = this.getPanel(),
                  Menu    = Panel.getContextMenu(),
                  Content = Panel.getContent();

            Menu.clearChildren();

            let sels = this.getPanel().getSelectedItems();

            if (parseInt(DOMNode.get('data-active')) === 0) {
                Menu.appendChild(
                    this.getActivateItem(DOMNode)
                );
            } else {
                Menu.appendChild(
                    this.getDeActivateItem(DOMNode)
                );
            }

            Menu.appendChild(
                new QUIContextmenuSeparator()
            );

            if (!sels.length) {
                sels = [DOMNode];
            }

            this.addHiddenMenu(Menu, sels);

            Menu.appendChild(
                new QUIContextmenuItem({
                    name  : 'rename',
                    text  : QUILocale.get('quiqqer/core', 'rename'),
                    icon  : 'fa fa-font',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().renameItem(DOMNode);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'download',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.folder.download'),
                    icon  : 'fa fa-download',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().downloadFile(DOMNode.get('data-id'));
                        }
                    }
                })
            );

            Menu.appendChild(new QUIContextmenuSeparator());

            Menu.appendChild(
                new QUIContextmenuItem({
                    name  : 'select-all',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.markAll'),
                    icon  : 'fa fa-hand-grab-o',
                    events: {
                        onMouseDown: function () {
                            Content.getElements('.qui-media-item').each(function (Item) {
                                Item.addClass('selected');
                                Panel.$selected.push(Item);
                            });
                        }
                    }
                })
            );

            Menu.appendChild(new QUIContextmenuSeparator());

            if (!sels.length || sels.length === 1) {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name  : 'delete',
                        text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.trash'),
                        icon  : 'fa fa-trash-o',
                        events: {
                            onMouseDown: function () {
                                if (!DOMNode) {
                                    return;
                                }

                                self.getPanel().deleteItem(DOMNode);
                            }
                        }
                    })
                );
            } else {
                const Trash = new QUIContextmenuItem({
                    name: 'delete',
                    text: QUILocale.get(lg, 'projects.project.panel.media.contextMenu.trash'),
                    icon: 'fa fa-trash-o'
                });

                Trash.appendChild(
                    new QUIContextmenuItem({
                        name  : 'delete',
                        text  : DOMNode.get('title'),
                        events: {
                            onMouseDown: function () {
                                if (!DOMNode) {
                                    return;
                                }

                                self.getPanel().deleteItem(DOMNode);
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuItem({
                        name  : 'delete',
                        text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.allElements'),
                        events: {
                            onMouseDown: function () {
                                const Panel = self.getPanel(),
                                      sels  = Panel.getSelectedItems();

                                self.getPanel().deleteItems(sels);
                            }
                        }
                    })
                );

                Menu.appendChild(Trash);
            }


            Menu.appendChild(
                new QUIContextmenuSeparator()
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'properties',
                    text  : QUILocale.get(lg, 'properties'),
                    icon  : 'fa fa-folder-open-o',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            const Parent = self.getPanel().getParent();

                            const type    = DOMNode.get('data-type'),
                                  id      = DOMNode.get('data-id'),
                                  project = DOMNode.get('data-project');

                            if (type !== 'folder') {
                                return;
                            }

                            require([
                                'controls/projects/project/media/FolderPanel'
                            ], function (FolderPanel) {
                                new FolderPanel({
                                    folderId: id,
                                    project : project
                                }).inject(Parent);
                            });
                        }
                    }
                })
            );

            return Menu;
        },

        /**
         * Return the activation menu item
         *
         * @param {HTMLElement} DOMNode - DOM media item element
         * @return {Object} qui/controls/contextmenu/Item
         */
        getActivateItem: function (DOMNode) {
            const self = this,
                  sels = this.getPanel().getSelectedItems();

            if (!sels.length || sels.length === 1) {
                return new QUIContextmenuItem({
                    name  : 'activate',
                    text  : QUILocale.get('quiqqer/core', 'activate'),
                    icon  : 'fa fa-check',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().activateItem(DOMNode);
                        }
                    }
                });
            }

            const Activate = new QUIContextmenuItem({
                name: 'activate',
                text: QUILocale.get('quiqqer/core', 'activate'),
                icon: 'fa fa-check'
            });

            Activate.appendChild(
                new QUIContextmenuItem({
                    name  : 'activate',
                    text  : DOMNode.get('title'),
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().activateItem(DOMNode);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'activate',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.allElements'),
                    events: {
                        onMouseDown: function () {
                            const Panel = self.getPanel(),
                                  sels  = Panel.getSelectedItems();

                            self.getPanel().activateItems(sels);
                        }
                    }
                })
            );

            return Activate;
        },

        /**
         * Return the deactivation menu item
         *
         * @param {HTMLElement} DOMNode - DOM media item element
         * @return {Object} qui/controls/contextmenu/Item
         */
        getDeActivateItem: function (DOMNode) {
            const self = this,
                  sels = this.getPanel().getSelectedItems();

            if (!sels.length || sels.length === 1) {
                return new QUIContextmenuItem({
                    name  : 'deactivate',
                    text  : QUILocale.get('quiqqer/core', 'deactivate'),
                    icon  : 'fa fa-remove',
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().deactivateItem(DOMNode);
                        }
                    }
                });
            }

            const Deactivate = new QUIContextmenuItem({
                name: 'deactivate',
                text: QUILocale.get('quiqqer/core', 'deactivate'),
                icon: 'fa fa-remove'
            });

            Deactivate.appendChild(
                new QUIContextmenuItem({
                    name  : 'deactivate',
                    text  : DOMNode.get('title'),
                    events: {
                        onMouseDown: function () {
                            if (!DOMNode) {
                                return;
                            }

                            self.getPanel().deactivateItem(DOMNode);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'deactivate',
                    text  : QUILocale.get(lg, 'projects.project.panel.media.contextMenu.allElements'),
                    events: {
                        onMouseDown: function () {
                            const Panel = self.getPanel(),
                                  sels  = Panel.getSelectedItems();

                            self.getPanel().deactivateItems(sels);
                        }
                    }
                })
            );

            return Deactivate;
        },

        /**
         * add hide menu
         *
         * @param Menu
         * @param sels
         */
        addHiddenMenu: function (Menu, sels) {
            const self = this;

            const getIds = function (nodes) {
                return nodes.map(function (elm) {
                    return parseInt(elm.get('data-id'));
                });
            };

            if (sels.length === 1) {
                const isHidden = parseInt(sels[0].get('data-hidden'));

                Menu.appendChild(
                    new QUIContextmenuItem({
                        name    : 'hide',
                        text    : isHidden ? QUILocale.get('quiqqer/core', 'media.item.visible') :
                            QUILocale.get('quiqqer/core', 'media.item.hide'),
                        icon    : isHidden ? 'fa fa-eye' : 'fa fa-eye-slash',
                        isHidden: isHidden,
                        events  : {
                            onMouseDown: function (Itm) {
                                if (parseInt(Itm.getAttribute('isHidden'))) {
                                    self.getPanel().getMedia().setVisible(
                                        getIds(sels)
                                    );
                                } else {
                                    self.getPanel().getMedia().setHidden(
                                        getIds(sels)
                                    );
                                }
                            }
                        }
                    })
                );
            } else {
                Menu.appendChild(
                    new QUIContextmenuItem({
                        name  : 'hide',
                        text  : QUILocale.get('quiqqer/core', 'media.items.hide'),
                        icon  : 'fa fa-eye-slash',
                        events: {
                            onMouseDown: function () {
                                self.getPanel().getMedia().setHidden(
                                    getIds(sels)
                                );
                            }
                        }
                    })
                );

                Menu.appendChild(
                    new QUIContextmenuItem({
                        name  : 'visible',
                        text  : QUILocale.get('quiqqer/core', 'media.items.visible'),
                        icon  : 'fa fa-eye',
                        events: {
                            onMouseDown: function () {
                                self.getPanel().getMedia().setVisible(
                                    getIds(sels)
                                );
                            }
                        }
                    })
                );
            }
        }
    });
});
