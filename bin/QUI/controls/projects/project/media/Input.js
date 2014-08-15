
/**
 * Image input
 *
 * @author www.pcsg.de (Henning Leutz)
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
     * @param {DOMNode Input} Input [optional] -> if no input given, one would be created
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
         * @return {DOMNode}
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

            this.$ClearButton = new QUIButton({
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

                self.$Preview.setStyle( 'background', 'url('+ result +') no-repeat center center' );

            }, {
                fileurl   : value,
                maxWidth  : 40,
                maxHeight : 40
            });
        }
    });

});