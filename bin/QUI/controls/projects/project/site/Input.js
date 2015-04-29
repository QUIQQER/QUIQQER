
/**
 * Select a site input field
 *
 * @module controls/projects/project/site/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require controls/projects/Popup
 * @require css!controls/projects/project/site/Input.css
 */

define('controls/projects/project/site/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/projects/Popup',

    'css!controls/projects/project/site/Input.css'

], function(QUIControl, QUIButton, ProjectPopup)
{
    "use strict";

    /**
     * @class controls/projects/Input
     *
     * @param {Object} options
     * @param {HTMLElement} [Input] - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/site/Input',

        Binds : [
            '$onCreate',
            '$onImport'
        ],

        options : {
            name     : '',
            styles   : false,
            external : false // external sites allowed?
        },

        initialize : function(options, Input)
        {
            this.parent( options );

            this.$Input      = Input || null;
            this.$SiteButton = null;

            this.addEvents({
                onImport : this.$onImport
            });
        },

        /**
         * Create the DOMNode
         *
         * @return {HTMLElement}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'      : 'qui-controls-project-site-input box',
                'data-quiid' : this.getId()
            });

            if ( !this.$Input )
            {
                this.$Input = new Element('input', {
                    name : this.getAttribute('name')
                }).inject( this.$Elm );

            } else
            {
                this.$Elm.wraps( this.$Input );

                if ( this.$Input.get( 'data-external' ) ) {
                    this.setAttribute( 'external', true );
                }
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.$Input.setStyles({
                'float' : 'left'
            });

            if ( !this.getAttribute( 'external' ) ) {
                this.$Input.setStyle( 'cursor', 'pointer' );
            }

            var self = this;

            this.$SiteButton = new QUIButton({
                icon   : 'fa fa-file-o icon-file-alt',
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

            new QUIButton({
                icon   : 'icon-remove',
                alt    : Locale.get( 'quiqqer/system', 'projects.project.site.input.clear' ),
                title  : Locale.get( 'quiqqer/system', 'projects.project.site.input.clear' ),
                events :
                {
                    onClick : function() {
                        self.$Input.value = '';
                    }
                }
            }).inject( this.$Elm );


            if ( !self.getAttribute( 'external' ) )
            {
                this.$Input.addEvents({
                    focus: function () {
                        self.$SiteButton.click();
                    }
                });
            }

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onImport : function()
        {
            this.$Input = this.$Elm;
            this.create();
        }
    });
});
