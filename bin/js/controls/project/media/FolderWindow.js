/**
 * A Media Center Sitemap Window Control
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/project/media/FolderWindow
 * @package com.pcsg.qui.js.controls.project.media
 * @namespace QUI.controls.project.media
 */

define('controls/project/media/FolderWindow', [

    'controls/Control',
    'controls/windows/Submit',
    'controls/project/media/Sitemap'

], function(QUI_Control)
{
    QUI.namespace('controls.project.media');

    /**
     * @class QUI.controls.project.media.FolderWindow
     */
    QUI.controls.project.media.FolderWindow = new Class({

        Implements: [QUI_Control],

        options : {
            startid     : 1,
            onlyfolders : true,
            autoclose   : true,
            information : ''
        },

        initialize : function(Media, options)
        {
            this.init( options );

            this.$Media = Media;
            this.$Map   = null;
            this.$Win   = null;
        },

        Loader :
        {
            show : function()
            {
                if ( this.$Win ) {
                    this.$Win.Loader.show();
                }
            },

            hide : function()
            {
                if ( this.$Win ) {
                    this.$Win.Loader.hide();
                }
            }
        },

        /**
         * Create the window
         */
        create : function()
        {
            this.$Win = QUI.Windows.create('submit', {
                title  : this.$Media.getProject().getName() +' Media',
                width  : 400,
                height : 600,
                autoclose : this.getAttribute( 'autoclose' ),

                events :
                {
                    onDrawEnd : function(Win)
                    {
                        var Media   = this.$Media,
                            Project = Media.getProject();

                        this.$Map = new QUI.controls.project.media.Sitemap({
                            project : Project.getName(),
                            lang    : Project.getAttribute('lang'),
                            id      : this.getAttribute('startid')
                        });

                        this.$Map.inject( Win.getBody() );
                        this.$Map.open();

                    }.bind( this ),

                    onSubmit : function(Win)
                    {
                        if ( !this.$Map ) {
                            return;
                        }

                        var sels = this.$Map.getSelectedChildren();

                        if ( !sels.length ) {
                            return;
                        }

                        var i, len;
                        var ids = [];

                        for ( i = 0, len = sels.length; i < len; i++ )
                        {
                            ids.push(
                                sels[i].getAttribute('value')
                            );
                        }

                        this.fireEvent( 'submit', [ids, this] );

                    }.bind( this )
                }
            });
        }
    });

    return QUI.controls.project.media.FolderWindow;
});