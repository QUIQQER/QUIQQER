/**
 * The type button opens a type window for the project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/projects/TypeWindow
 *
 * @module controls/projects/TypeButton
 * @package com.pcsg.qui.js.controls.projects
 */

define('controls/projects/TypeButton', [

    'qui/controls/Control',
    'controls/projects/TypeWindow',
    'qui/controls/buttons/Button'

], function(QUIControl, TypeWindow, QUIButton)
{
    "use strict";

    /**
     * @class controls/projects/TypeButton
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/TypeButton',

        options : {
            project : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Button = null;
            this.$Elm    = null;
        },

        /**
         * Create the type button
         *
         * @method controls/projects/TypeButton#create
         * @return {DOMNode}
         */
        create : function()
        {
            var self = this;

            this.$Button = new QUIButton({
                name    : 'project-types',
                image   : URL_BIN_DIR +'16x16/types.png',
                alt     : 'Seitentypen ändern',
                title   : 'Seitentypen ändern',
                Project : this.getAttribute('project'),
                events  :
                {
                    click : function(Btn)
                    {
                        new TypeWindow({
                            project : Btn.getAttribute('Project'),
                            Control : Btn.getAttribute('Control'),
                            events  :
                            {
                                onSubmit : function(result, Win)
                                {
                                    if ( result[0] ) {
                                        self.fireEvent( 'submit', [ result[0], Control ] );
                                    }
                                },

                                onCancel : function(Win) {
                                    self.fireEvent( 'cancel' );
                                }
                            }
                        }).open();
                    }
                }
            });

            this.$Elm = this.$Button.create();

            return this.$Elm;
        }
    });
});