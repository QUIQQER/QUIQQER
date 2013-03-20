/**
 * Media DOM event handling for a media panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/media/PanelDOMEvents
 * @package com.pcsg.qui.js.controls.projects.media.PanelDOMEvents
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/PanelDOMEvents', [

    'controls/projects/media/Panel'

], function()
{
    "use strict";

    QUI.namespace( 'controls.projects.media' );

    QUI.controls.projects.media.PanelDOMEvents = new Class({

        initialize : function(MediaPanel)
        {
            this.$MediaPanel = MediaPanel;
            this.$Media      = MediaPanel.$Media;
        },

        /**
         * Activate the media item from the DOMNode
         *
         * @method QUI.controls.projects.media.PanelDOMEvents#activateItem
         * @param {Array} DOMNode List
         */
        activate : function(List)
        {
            this.$createLoaderItem( List );

            this.$Media.activate(this.$getIds( List ), function(items, Request)
            {
                var List = Request.getAttribute('List');

                Request.getAttribute('PDE').$destroyLoaderItem( List );

                for ( var i = 0, len = List.length; i < len; i++ )
                {
                    List[i].set('data-active', 1);
                    List[i].removeClass('qmi-deactive');
                    List[i].addClass('qmi-active');
                }

            }, {
                List : List,
                PDE  : this
            });
        },

        /**
         * Deactivate the media item from the DOMNode
         *
         * @method QUI.controls.projects.media.PanelDOMEvents#deactivateItem
         * @param {Array} DOMNode List
         */
        deactivate : function(List)
        {
            this.$createLoaderItem( List );

            this.$Media.deactivate(this.$getIds( List ), function(items, Request)
            {
                var List = Request.getAttribute('List');

                Request.getAttribute('PDE').$destroyLoaderItem( List );

                for ( var i = 0, len = List.length; i < len; i++ )
                {
                    List[i].set('data-active', 0);
                    List[i].removeClass('qmi-active');
                    List[i].addClass('qmi-deactive');
                }
            }, {
                List : List,
                PDE  : this
            });
        },

        /**
         * Delete the media items from the DOMNode
         *
         * @method QUI.controls.projects.media.PanelDOMEvents#deactivateItem
         * @param {Array} DOMNode List
         */
        del : function(List)
        {
            QUI.Windows.create('submit', {
                name    : 'delete_item',
                title   : 'Ordner / Datei(en) löschen',
                icon    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                information : '',

                PDE     : this,
                Control : this.$MediaPanel,
                List    : List,

                events :
                {
                    onDrawEnd : function(Win)
                    {
                        Win.Loader.show();

                        var list    = [],
                            PDE     = Win.getAttribute( 'PDE' ),
                            Control = Win.getAttribute( 'Control' ),
                            List    = Win.getAttribute( 'List' );

                        for ( var i = 0, len = List.length; i < len; i++ ) {
                            list.push( List[i].get('data-id') );
                        }

                        PDE.$Media.get( list, function(items, Request)
                        {
                            var information = '<ul>';

                            for ( var i = 0, len = items.length; i < len; i++ )
                            {
                                information = information +
                                    '<li>'+
                                        '#'+ items[i].getAttribute('id') +
                                        ' - '+ items[i].getAttribute('name') +
                                    '</li>';
                            }

                            information = information +'</ul>';

                            Win.setAttribute('texticon', URL_BIN_DIR +'32x32/trashcan_empty.png');
                            Win.setAttribute('text', 'Möchten Sie folgende(n) Ordner / Datei(en) wirklich löschen?');
                            Win.setAttribute('information', information);
                            Win.setAttribute('media-items', items);

                            Win.Loader.hide();

                        }.bind( Win ));
                    },

                    onSubmit : function(Win)
                    {
                        if ( Win.getAttribute( 'media-items' ) )
                        {
                            var ids     = [],
                                Control = Win.getAttribute( 'Control' ),
                                items   = Win.getAttribute( 'media-items' ),
                                Media   = Control.getMedia();

                            Control.$Panel.Loader.show();

                            for ( var i = 0, len = items.length; i < len; i++ ) {
                                ids.push( items[i].getId() );
                            }

                            Media.del(ids, function(result, Request)
                            {
                                this.refresh();
                            }.bind( Control ));
                        }
                    }
                }
            });
        },

        /**
         * Rename the folder, show the rename dialoge
         *
         * @method QUI.controls.projects.media.PanelDOMEvents#renameItem
         * @param {DOMNode} DOMNode
         */
        rename : function(DOMNode)
        {
            QUI.Windows.create('prompt', {
                name    : 'rename_item',
                title   : 'Ordner umbenennen',
                icon    : URL_BIN_DIR +'16x16/folder.png',
                Control : this.$MediaPanel,
                PDE     : this,
                DOMNode : DOMNode,

                check : function(Win)
                {
                    Win.fireEvent('submit', [Win.getValue(), Win]);
                    return false;
                },

                events  :
                {
                    onDrawEnd : function(Win)
                    {
                        Win.Loader.show();

                        var PDE     = Win.getAttribute( 'PDE' ),
                            Control = Win.getAttribute( 'Control' ),
                            DOMNode = Win.getAttribute( 'DOMNode' ),
                            itemid  = DOMNode.get('data-id');

                        PDE.$Media.get( itemid, function(Item, Request)
                        {
                            this.setValue( Item.getAttribute( 'name' ) );
                            this.Loader.hide();

                        }.bind( Win ));
                    },

                    onSubmit : function(result, Win)
                    {
                        var PDE     = Win.getAttribute( 'PDE' ),
                            Control = Win.getAttribute( 'Control' ),
                            DOMNode = Win.getAttribute( 'DOMNode' ),
                            itemid  = DOMNode.get('data-id');

                        Win.setAttribute('newname', result);


                        PDE.$createLoaderItem( DOMNode );

                        PDE.$Media.get( itemid, function(Item, Request)
                        {
                            Item.rename(
                                this.getAttribute('newname'),
                                function(result, Request)
                                {
                                    var Win     = Request.getAttribute('Win'),
                                        PDE     = Win.getAttribute( 'PDE' ),
                                        Control = Win.getAttribute( 'Control' ),
                                        DOMNode = Win.getAttribute( 'DOMNode' );

                                    if ( !DOMNode ) {
                                        return;
                                    }

                                    DOMNode.set({
                                        alt   : result,
                                        title : result
                                    });

                                    DOMNode.getElement('span').set( 'html', result );

                                    PDE.$destroyLoaderItem( DOMNode );

                                    Win.close();
                                }, {
                                    Win : this
                                }
                            );

                        }.bind( Win ));
                    },
                    onCancel : function(Win)
                    {
                        var PDE     = Win.getAttribute( 'PDE' ),
                            Control = Win.getAttribute( 'Control' ),
                            DOMNode = Win.getAttribute( 'DOMNode' ),
                            itemid  = DOMNode.get('data-id');

                        if ( !DOMNode ) {
                            return;
                        }

                        PDE.$destroyLoaderItem( DOMNode );
                    }
                }
            });
        },

        /**
         * replace a file
         *
         * @method QUI.controls.projects.media.PanelDOMEvents#replace
         * @param {DOMNode} DOMNode
         */
        replace : function(DOMNode)
        {
            QUI.Windows.create('submit', {
                title   : 'Datei ersetzen ...',
                icon    : URL_BIN_DIR +'16x16/replace.png',
                name    : 'replace-media-id-'+ DOMNode.get('data-id'),
                width   : 500,
                height  : 200,
                DOMNode : DOMNode,
                Control : this,

                text     : 'Datei ersetzen',
                texticon : URL_BIN_DIR +'48x48/replace.png',

                information : 'Wählen Sie eine Datei aus oder ziehen Sie eine Datei in das Fenster.',
                autoclose   : false,
                events :
                {
                    onDrawEnd : function(Win, MuiWin)
                    {
                        var WinBody    = Win.getBody(),
                            WinContent = WinBody.getParent('.mochaContentWrapper');

                        WinContent.addClass( 'smooth' );
                        WinContent.addClass( 'box' );

                        // upload formular
                        require(['controls/upload/Form'], function(UploadForm)
                        {
                            var Control = this.getAttribute('Control'),
                                Node    = this.getAttribute('DOMNode');

                            var Form = new UploadForm({
                                Drops  : [WinContent],
                                styles : {
                                    margin : '20px 0 0 70px',
                                    float  : 'left',
                                    clear  : 'both'
                                },
                                Media   : Control.$Media,
                                Win     : this,
                                Node    : Node,
                                events  :
                                {
                                    onBegin : function(Control) {
                                        Control.getAttribute('Win').close();
                                    },

                                    onComplete : function(Control)
                                    {
                                        var i, len;
                                        var Node    = Control.getAttribute('Node'),

                                            panels = QUI.Controls.get(
                                                'projects-media-panel'
                                            ),

                                            windows = QUI.Controls.get(
                                                'replace-media-id-'+ Node.get('data-id')
                                            ),

                                            filepanels = QUI.Controls.get(
                                                'projects-media-file-panel-'+ Node.get('data-id')
                                            );

                                        // Media panels refresh
                                        for ( i = 0, len = panels.length; i < len; i++ ) {
                                            panels[i].refresh();
                                        }

                                        // Media Windows
                                        for ( i = 0, len = windows.length; i < len; i++ ) {
                                            windows[i].close();
                                        }

                                        // File panels
                                        for ( i = 0, len = filepanels.length; i < len; i++ ) {
                                            filepanels[i].refresh();
                                        }
                                    },

                                    /// drag drop events
                                    onDragenter: function(event, Elm, Upload)
                                    {
                                        if ( !Elm.hasClass('mochaContentWrapper') ) {
                                            Elm = Elm.getParent('.mochaContentWrapper');
                                        }

                                        if ( !Elm || !Elm.hasClass('mochaContentWrapper') ) {
                                            return;
                                        }

                                        Elm.addClass( 'qui-media-drag' );

                                        event.stop();
                                    },

                                    onDragend : function(event, Elm, Upload)
                                    {
                                        if ( Elm.hasClass('qui-media-drag') ) {
                                            Elm.removeClass( 'qui-media-drag' );
                                        }
                                    }
                                }
                            });

                            Form.setParam('onstart', 'ajax_media_checkreplace');
                            Form.setParam('onfinish', 'ajax_media_replace');
                            Form.setParam('project', Control.$Media.getProject().getName());
                            Form.setParam('fileid', Node.get('data-id'));

                            Form.inject( this.getBody() );

                            Win.setAttribute( 'Form', Form );

                        }.bind( Win ));
                    },

                    onSubmit : function(Win)
                    {
                        Win.Loader.show();
                        Win.getAttribute('Form').submit();

                    }
                }
            });
        },

        /**
         * Create a loader item in a media item div container
         *
         * @param {DOMNode|Array} DOMNode - Parent (Media Item) DOMNode
         */
        $createLoaderItem : function(DOMNode)
        {
            var List = DOMNode;

            if ( !List.length ) {
                List = [DOMNode];
            }

            for ( var i = 0, len = List.length; i < len; i++)
            {
                new Element('div.loader', {
                    styles : {
                        background : '#000000 url('+ URL_BIN_DIR +'images/loader-big-black-white.gif) no-repeat center center',
                        position   : 'absolute',
                        top        : 0,
                        left       : 0,
                        height     : '100%',
                        width      : '100%',
                        opacity    : 0.5
                    }
                }).inject( List[i] );
            }
        },

        /**
         * Destroy the loader item in a media item div container
         *
         * @param {DOMNode|Array} DOMNode - Parent (Media Item) DOMNode or DOMNode List
         */
        $destroyLoaderItem : function(DOMNode)
        {
            var i, len, Elm;
            var List = DOMNode;

            if ( !List.length ) {
                List = [DOMNode];
            }

            for ( i = 0, len = List.length; i < len; i++)
            {
                Elm = List[i].getElement('.loader');

                if ( Elm ) {
                    Elm.destroy();
                }
            }
        },

        /**
         * Return the ids from a DOMNode List
         *
         * @param {Array} List - List of DOMNodes
         * @return {Array}
         */
        $getIds : function(List)
        {
            var list = [];

            for ( var i = 0, len = List.length; i < len; i++ ) {
                list.push( List[i].get('data-id') );
            }

            return list;
        }
    });

    return QUI.controls.projects.media.PanelDOMEvents;
});