
/**
 * Select a site input field
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/projects/project/site/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/projects/Popup',
    'Ajax',

    'css!controls/projects/project/site/Input.css'

], function(QUIControl, QUIButton, ProjectPopup, Ajax)
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
        Type    : 'controls/projects/project/site/Input',

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
            this.$SiteButton = null;
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
                'class' : 'qui-controls-project-site-input box'
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

            this.$SiteButton = new QUIButton({
                icon   : 'icon-file-alt',
                events :
                {
                    onClick : function()
                    {
                        new ProjectPopup({
                            events :
                            {
                                onSubmit : function(Popup, params) {
                                    self.$Input.value = params.urls[ 0 ];
                                }
                            }
                        }).open();
                    }
                }
            }).inject( this.$Elm );

            this.$ClearButton = new QUIButton({
                icon : 'icon-remove',
                alt : Locale.get(
                    'quiqqer/system',
                    'projects.project.site.input.clear'
                ),
                title : Locale.get(
                    'quiqqer/system',
                    'projects.project.site.input.clear'
                ),
                events :
                {
                    onClick : function() {
                        self.$Input.value = '';
                    }
                }
            }).inject( this.$Elm );


            this.$Input.addEvents({
                focus : function() {
                    self.$SiteButton.click();
                }
            });

            return this.$Elm;
        }
    });

});