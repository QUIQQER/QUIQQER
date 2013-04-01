/**
 * The type button opens a media window
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/projects/media/Button
 * @package com.pcsg.qui.js.controls.projects
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/Button', [

    'controls/Control',
    'controls/buttons/Button',
    'controls/projects/media/Window'

], function(Control, QUI_Window)
{
    "use strict";

    QUI.namespace( 'controls.projects.media' );

    /**
     * @class QUI.controls.projects.media.Button
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
      *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.media.Button = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.projects.media.Button',

        options : {
            project : false
        },

        initialize : function(options)
        {
            this.init( options );

            this.$Button = null;
            this.$Elm    = null;
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
                image    : URL_BIN_DIR +'16x16/media.png',
                alt      : 'Mediadatei verknüpfen',
                title    : 'Mediadatei verknüpfen',
                Project  : this.getAttribute('project'),
                Control  : this,
                events   :
                {
                    click : function(Btn)
                    {
                        new QUI.controls.projects.media.Window({
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

    return QUI.controls.projects.media.Button;
});