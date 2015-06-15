
/**
 * Media DOM event handling for a media panel
 *
 * @module classes/projects/project/media/panel/DOMEvents
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/projects/project/media/panel/DOMEvents', [

   'qui/QUI',
   'qui/controls/windows/Prompt',
   'qui/controls/windows/Confirm'

], function(QUI, QUIPrompt, QUIConfirm)
{
    "use strict";

    /**
     * @class classes/projects/project/media/panel/DOMEvents
     * @param {qui/controls/projects/media/Panel} MediaPanel
     *
     * @memberof! <global>
     */
    return new Class({

        Type : "classes/projects/project/media/panel/DOMEvents",

        initialize : function(MediaPanel)
        {
            this.$Panel = MediaPanel;
        },

        /**
         * Return the media of the panel
         *
         * @method classes/projects/project/media/panel/DOMEvents#getMedia
         */
        getMedia : function()
        {
            return this.$Panel.getMedia();
        },

        /**
         * Activate the media item from the DOMNode
         *
         * @method classes/projects/project/media/panel/DOMEvents#activateItem
         * @param {Array} List (DOMNode)
         */
        activate : function(List)
        {
            var self = this;

            this.$createLoaderItem( List );

            this.getMedia().activate( this.$getIds( List ), function()
            {
                self.$destroyLoaderItem( List );

                for ( var i = 0, len = List.length; i < len; i++ )
                {
                    List[ i ].set('data-active', 1);
                    List[ i ].removeClass('qmi-deactive');
                    List[ i ].addClass('qmi-active');
                }
            });
        },

        /**
         * Deactivate the media item from the DOMNode
         *
         * @method classes/projects/project/media/panel/DOMEvents#deactivateItem
         * @param {Array} List (DOMNode)
         */
        deactivate : function(List)
        {
            var self = this;

            this.$createLoaderItem( List );

            this.getMedia().deactivate(this.$getIds( List ), function()
            {
                self.$destroyLoaderItem( List );

                for ( var i = 0, len = List.length; i < len; i++ )
                {
                    List[ i ].set('data-active', 0);
                    List[ i ].removeClass('qmi-active');
                    List[ i ].addClass('qmi-deactive');
                }
            });
        },

        /**
         * Delete the media items from the DOMNode
         *
         * @method classes/projects/project/media/panel/DOMEvents#deactivateItem
         * @param {Array} List (DOMNode)
         */
        del : function(List)
        {
            var self    = this,
                Media   = this.getMedia(),
                items   = [],
                list    = [];

            // #locale
            new QUIConfirm({
                name     : 'delete_item',
                title    : 'Ordner / Datei(en) löschen',
                icon     : 'fa fa-trash-o icon-trash',
                texticon : 'fa fa-trash-o icon-trash',
                text     : 'Möchten Sie folgende(n) Ordner / Datei(en) wirklich löschen?',
                information : '<div class="qui-media-file-delete"></div>',
                events :
                {
                    onOpen : function(Win)
                    {
                        Win.Loader.show();

                        for ( var i = 0, len = List.length; i < len; i++ ) {
                            list.push( List[ i ].get( 'data-id' ) );
                        }

                        Media.get( list ).done(function(result)
                        {
                            var i, len;
                            var information = '<ul>';

                            items = result;

                            for ( i = 0, len = items.length; i < len; i++ )
                            {
                                information = information +
                                    '<li>'+
                                        '#'+ items[ i ].getAttribute('id') +
                                        ' - '+ items[ i ].getAttribute('name') +
                                    '</li>';
                            }

                            information = information +'</ul>';

                            Win.getContent().getElement( '.qui-media-file-delete' ).set(
                                'html',
                                information
                            );

                            Win.Loader.hide();

                        }, function()
                        {
                            var information = '<ul>';

                            for ( var i = 0, len = list.length; i < len; i++ ) {
                                information = information + '<li>#'+ list[i] +'</li>';
                            }

                            information = information +'</ul>';

                            Win.getContent().getElement( '.qui-media-file-delete' ).set(
                                'html',
                                information
                            );

                            Win.Loader.hide();
                        });
                    },

                    onSubmit : function()
                    {
                        if ( !list.length ) {
                            return;
                        }

                        self.$Panel.Loader.show();

                        Media.del( list, function() {
                            self.$Panel.refresh();
                        });
                    }
                }
            }).open();
        },

        /**
         * Rename the folder, show the rename dialoge
         *
         * @method classes/projects/project/media/panel/DOMEvents#renameItem
         * @param {HTMLElement} DOMNode
         */
        rename : function(DOMNode)
        {
            var self = this;

            new QUIPrompt({
                name  : 'rename_item',
                title : 'Ordner umbenennen',
                icon  : URL_BIN_DIR +'16x16/folder.png',
                check : function(Win)
                {
                    Win.fireEvent( 'submit', [ Win.getValue(), Win ] );
                    return false;
                },

                events :
                {
                    onCreate : function(Win) {
                        Win.Loader.show();
                    },

                    onOpen : function(Win)
                    {
                        var itemid = DOMNode.get('data-id');

                        self.getMedia().get( itemid, function(Item)
                        {
                            Win.setValue( Item.getAttribute( 'name' ) );
                            Win.Loader.hide();
                        });
                    },

                    onSubmit : function(result, Win)
                    {
                        var itemid  = DOMNode.get('data-id'),
                            newName = result;

                        self.$createLoaderItem( DOMNode );

                        self.getMedia().get( itemid, function(Item)
                        {
                            Item.rename( newName, function(result)
                            {
                                if ( !DOMNode ) {
                                    return;
                                }

                                DOMNode.set({
                                    alt   : result,
                                    title : result
                                });

                                DOMNode.getElement('span').set( 'html', result );

                                self.$destroyLoaderItem( DOMNode );

                                Win.close();
                            });
                        });
                    },

                    onCancel : function() {
                        self.$destroyLoaderItem( DOMNode );
                    }
                }
            }).open();
        },

        /**
         * replace a file
         *
         * @method classes/projects/project/media/panel/DOMEvents#replace
         * @param {HTMLElement} DOMNode
         */
        replace : function(DOMNode)
        {
            var self = this;

            new QUIConfirm({
                title   : 'Datei ersetzen ...',
                icon    : 'icon-retweet',
                name    : 'replace-media-id-'+ DOMNode.get('data-id'),
                maxHeight : 400,
                maxWidth  : 600,

                text     : 'Datei ersetzen',
                texticon : 'icon-retweet',

                information : 'Wählen Sie eine Datei aus oder ziehen Sie eine Datei in das Fenster.',
                autoclose   : false,
                events :
                {
                    onCreate : function(Win)
                    {
                        var Content = Win.getContent();

                        // upload formular
                        require(['controls/upload/Form'], function(UploadForm)
                        {
                            var Form = new UploadForm({
                                Drops  : [ Content ],
                                styles : {
                                    clear  : 'both',
                                    float  : 'left',
                                    margin : '20px 0 0 0'
                                },
                                events :
                                {
                                    onBegin : function() {
                                        Win.close();
                                    },

                                    onComplete : function()
                                    {
                                        var i, len;

                                        var panels = QUI.Controls.get(
                                                'projects-media-panel'
                                            ),

                                            windows = QUI.Controls.get(
                                                'replace-media-id-'+ DOMNode.get('data-id')
                                            ),

                                            filepanels = QUI.Controls.get(
                                                'projects-media-file-panel-'+ DOMNode.get('data-id')
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
                                    onDragenter: function(event, Elm)
                                    {
                                        Elm.addClass( 'qui-media-drag' );
                                        event.stop();
                                    },

                                    onDragend : function(event, Elm)
                                    {
                                        if ( Elm.hasClass('qui-media-drag') ) {
                                            Elm.removeClass( 'qui-media-drag' );
                                        }
                                    }
                                }
                            });

                            Form.setParam('onstart', 'ajax_media_checkreplace');
                            Form.setParam('onfinish', 'ajax_media_replace');
                            Form.setParam('project', self.getMedia().getProject().getName());
                            Form.setParam('fileid', DOMNode.get('data-id'));

                            Form.inject( Content );

                            Win.setAttribute( 'Form', Form );
                        });
                    },

                    onSubmit : function(Win)
                    {
                        Win.Loader.show();
                        Win.getAttribute('Form').submit();
                    }
                }
            }).open();
        },

        /**
         * Create a loader item in a media item div container
         *
         * @param {HTMLElement|Array} DOMNode - Parent (Media Item) DOMNode
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
         * @param {HTMLElement|Array} DOMNode - Parent (Media Item) DOMNode or DOMNode List
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
});