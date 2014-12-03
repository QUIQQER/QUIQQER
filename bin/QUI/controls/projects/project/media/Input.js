
/**
 * Image input
 *
 * @module controls/projects/project/media/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/utils/String
 * @require controls/projects/project/media/Popup
 * @require Ajax
 * @require Locale
 * @require css!controls/projects/project/media/Input.css
 */

define('controls/projects/project/media/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/utils/String',
    'controls/projects/project/media/Popup',
    'Ajax',
    'Locale',

    'css!controls/projects/project/media/Input.css'

], function(QUIControl, QUIButton, QUIStringUtils, MediaPopup, Ajax, Locale)
{
    "use strict";

    /**
     * @class controls/projects/Input
     *
     * @param {Object} options
     * @param {HTMLElement} [Input] - (optional) if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/media/Input',

        Binds : [
            '$onCreate'
        ],

        options : {
            name   : '',
            styles : false
        },

        initialize : function(options, Input)
        {
            this.parent( options );

            this.$Input   = Input || null;
            this.$Preview = null;
        },

        /**
         * Create the DOMNode
         *
         * @return {HTMLElement}
         */
        create : function()
        {
            var self = this;

            this.$Elm = new Element('div', {
                'class' : 'qui-controls-project-media-input box'
            });

            if ( !this.$Input )
            {
                this.$Input = new Element('input', {
                    name : this.getAttribute('name')
                }).inject( this.$Elm );

            } else
            {
                this.$Elm.wraps( this.$Input );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.$Input.setStyles({
                display : 'none'
            });

            // preview
            this.$Preview = new Element('div', {
                'class' : 'qui-controls-project-media-input-preview'
            }).inject( this.$Elm );

            this.$MediaButton = new QUIButton({
                icon   : 'icon-picture',
                alt    : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.alt'),
                title  : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.title'),
                events :
                {
                    onClick : function()
                    {
                        var value   = self.$Input.value,
                            project = '',
                            fileid  = false;

                        if ( self.$Input.get( 'data-project' ) ) {
                            project = self.$Input.get( 'data-project' );
                        }

                        if ( value !== '' )
                        {
                            var urlParams = QUIStringUtils.getUrlParams( value );

                            fileid  = urlParams.id;
                            project = urlParams.project;
                        }

                        new MediaPopup({
                            project : project,
                            fileid  : fileid,
                            events :
                            {
                                onSubmit : function(Popup, params)
                                {
                                    self.$Input.value = params.url;
                                    self.$refreshPreview();
                                }
                            }
                        }).open();
                    }
                }
            }).inject( this.$Elm );

            new QUIButton({
                icon   : 'icon-remove',
                alt    : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
                title  : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
                events :
                {
                    onClick : function()
                    {
                        self.$Input.value = '';
                        self.$refreshPreview();
                    }
                }
            }).inject( this.$Elm );


            this.$Input.addEvents({
                focus : function() {
                    self.$MediaButton.click();
                }
            });

            this.$refreshPreview();

            return this.$Elm;
        },

        /**
         * refresh the preview
         */
        $refreshPreview : function()
        {
            var value = this.$Input.value;

            if ( value === '' )
            {
                this.$Preview.setStyle( 'background', null );
                return;
            }

            var self = this;

            Ajax.get('ajax_media_url_resized', function(result)
            {
                if ( !self.$Preview ) {
                    return;
                }

                if ( result.substr( 0, 10 ) == 'image.php?' ) {
                    result = URL_DIR + result;
                }

                self.$Preview.getElements( '.icon-refresh' ).destroy();
                self.$Preview.getElements( '.icon-warning-sign ' ).destroy();

                // loader image
                var MiniLoader = new Element('div', {
                    'class' : 'icon-refresh icon-spin',
                    styles  : {
                        fontSize  : 18,
                        height    : 20,
                        left      : 4,
                        position  : 'relative',
                        textAlign : 'center',
                        top       : 4,
                        width     : 20

                    }
                }).inject( self.$Preview );

                // load the image
                Asset.image( result, {
                    onLoad : function()
                    {
                        MiniLoader.destroy();
                        self.$Preview.setStyle( 'background', 'url('+ result +') no-repeat center center' );
                    },
                    onError : function()
                    {
                        self.$Preview.getElements( '.icon-refresh' )
                                     .removeClass( 'icon-refresh' )
                                     .removeClass( 'icon-spin' )
                                     .addClass( 'icon-warning-sign' );

                    }
                });

            }, {
                fileurl   : value,
                maxWidth  : 40,
                maxHeight : 40
            });
        }
    });
});
