
/**
 * Image input
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/projects/project/media/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/projects/project/media/Popup',
    'Ajax',
    'Locale',

    'css!controls/projects/project/media/Input.css'

], function(QUIControl, QUIButton, MediaPopup, Ajax, Locale)
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

            this.$Input = Input || null;
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
                'float' : 'left'
            });

            this.$MediaButton = new QUIButton({
                icon   : 'icon-picture',
                alt    : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.alt'),
                title  : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.title'),
                events :
                {
                    onClick : function()
                    {
                        var project = '';

                        if ( self.$Input.get( 'data-project' ) ) {
                            project = self.$Input.get( 'data-project' );
                        }

                        new MediaPopup({
                            project : project,
                            events :
                            {
                                onSubmit : function(Popup, params) {
                                    self.$Input.value = params.url;
                                }
                            }
                        }).open();
                    }
                }
            }).inject( this.$Elm );

            this.$ClearButton = new QUIButton({
                icon : 'icon-remove',
                alt    : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
                title  : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
                events :
                {
                    onClick : function() {
                        self.$Input.value = '';
                    }
                }
            }).inject( this.$Elm );


            this.$Input.addEvents({
                focus : function() {
                    self.$MediaButton.click();
                }
            });

            return this.$Elm;
        }
    });

});