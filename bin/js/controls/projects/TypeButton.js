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
 * @namespace QUI.controls.projects
 */

define('controls/projects/TypeButton', [

    'controls/Control',
    'controls/projects/TypeWindow'

], function(Control, QUI_Window)
{
    "use strict";

    QUI.namespace( 'controls.projects' );

    /**
     * @class QUI.controls.projects.TypeButton
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
      *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.TypeButton = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.projects.TypeButton',

        options : {
            project : false
        },

        initialize : function(options)
        {
            this.init( options );

            this.$Button = null;
            this.$Elm    = null;

            this.create();
        },

        /**
         * Create the type button
         *
         * @method QUI.controls.projects.TypeButton#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Button = new QUI.controls.buttons.Button({
                name     : 'project-types',
                image    : URL_BIN_DIR +'16x16/types.png',
                alt      : 'Seitentypen ändern',
                title    : 'Seitentypen ändern',
                Project  : this.getAttribute('project'),
                Control  : this,
                events   :
                {
                    click : function(Btn)
                    {
                        new QUI.controls.projects.TypeWindow({
                            project : Btn.getAttribute('Project'),
                            Control : Btn.getAttribute('Control'),
                            events  :
                            {
                                onSubmit : function(result, Win)
                                {
                                    if ( result[0] )
                                    {
                                        var Control = Win.getAttribute('Control');

                                        Control.fireEvent('submit', [result[0], Control]);
                                    }
                                },
                                onCancel : function(Win)
                                {
                                    Win.getAttribute('Control').fireEvent('cancel');
                                }
                            }
                        }).create();
                    }
                }
            });

            this.$Elm = this.$Button.create();

            return this.$Elm;
        }
    });

    return QUI.controls.projects.TypeButton;
});