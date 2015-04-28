
/**
 * Link Plugin for a WYSIWYG Editor
 *
 * @author www.pcsg.de (Henning Leutz)
 * @depricated maybe
 */

define('controls/editors/windows/ImageWindow', [

      'qui/controls/windows/Confirm',

      'css!controls/editors/windows/ImageWindow.css'

], function(QUIPopup)
{
    "use strict";

    return new Class({

        Extends : QUIPopup,
        Type    : 'controls/editors/windows/ImageWindow',

        options : {
            maxWidth : 600,
            maxHeight : 400,
            title : 'Bild einfügen',
            icon : 'fa fa-picture-o',
            Node : false
        },

        initialize : function(options)
        {
            this.parent( options );
        },

        /**
         * Open the window
         */
        open : function()
        {
            this.parent();

            var self    = this,
                Content = this.getContent(),
                html    = '';

            html = '<div class="qui-editor-window-image">' +
                        '<label for="qui-editor-window-image-url">'+
                            'Bild-Adresse / Bild-URL' +
                        '</label>' +
                        '<input type="text" name="" id="qui-editor-window-image-url" />' +
                        '<div class="qui-editor-window-image-media-button"></div>'+

                        '<label for="qui-editor-window-image-alt">'+
                            'Alternativ Text' +
                        '</label>' +
                        '<input type="text" name="" id="qui-editor-window-image-alt" />' +

                        '<label for="qui-editor-window-image-width">'+
                            'Breite' +
                        '</label>' +
                        '<input type="text" name="" id="qui-editor-window-image-width" />' +

                        '<label for="qui-editor-window-image-height">'+
                            'Höhe' +
                        '</label>' +
                        '<input type="text" name="" id="qui-editor-window-image-height" />' +

                        '<label for="qui-editor-window-image-select">'+
                            'Styles' +
                        '</label>' +
                        '<select name="" id="qui-editor-window-image-select">' +
                            '<option>test</option>' +
                        '</select>' +
                    '</div>';

            Content.set( 'html', html );


            new Element('button', {
                'class' : 'qui-button',
                html : '<span class="fa fa-picture-o"></span>',
                events :
                {
                    click : function() {
                        self.openMedia();
                    }
                }
            }).inject(
                Content.getElement( '.qui-editor-window-image-media-button' )
            );
        },

        /**
         * Submit the window
         *
         * @method qui/controls/windows/Confirm#submit
         */
        submit : function()
        {
            this.fireEvent( 'submit', [ this, this.$getParams() ] );
            this.close();
        },

        /**
         * Open the Meda Popup for Image insertion
         */
        openMedia : function()
        {
            var self = this;

            require(['controls/projects/project/media/Popup'], function(Popup)
            {
                new Popup({
                    events :
                    {
                        onSubmit : function(Popup, imageData)
                        {
                            document.id(
                                'qui-editor-window-image-url'
                            ).value = imageData.url;

                        }
                    }
                }).open();
            });
        },

        /**
         * Returns the image params
         */
        $getParams : function()
        {
            return {
                url : document.id( 'qui-editor-window-image-url' ).value,
                alt : document.id( 'qui-editor-window-image-alt' ).value,
                height : document.id( 'qui-editor-window-image-height' ).value,
                width : document.id( 'qui-editor-window-image-width' ).value
            };
        }
    });

});