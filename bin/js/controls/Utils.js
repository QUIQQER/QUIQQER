/**
 * Utils for the controls
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/Utils
 * @package com.pcsg.qui.js.controls
 * @namespace QUI.controls
 */

define('controls/Utils', function()
{
    "use strict";

    QUI.namespace('controls');


    if ( typeof QUI.controls.Utils !== 'undefined' ) {
        return QUI.controls.Utils;
    }

    QUI.controls.Utils =
    {
        /**
         * Is the Object a QUI Control?
         *
         * @method QUI.lib.Controls#isControl
         * @return {Bool}
         */
        isControl : function(Obj)
        {
            if ( typeof Obj === 'undefined' || !Obj ) {
                return false;
            }

            if ( typeof Obj.getType !== 'undefined' ) {
                return true;
            }

            return false;
        },

        /**
         * Parse an DOM Node Element
         *
         * Search all control elements in the node element
         * and parse it to the specific controls
         */
        parse : function(Elm)
        {
            require([

                'controls/groups/Input',
                'controls/buttons/Button',
                'controls/groups/Sitemap',
                'controls/projects/TypeInput',
                'controls/projects/media/Input',
                'package/quiqqer/calendar/bin/Calendar'

            ], function(
                Qui_GroupInput,
                Qui_Buttons,
                Qui_GroupSitemap,
                Qui_ProjectTypeInput,
                Qui_ProjectMediaInput,
                DatePicker
            ) {
                var i, len, Child, elements;


                var Form = false;

                if ( Elm.nodeName == 'FORM' ) {
                    Form = Elm;
                }

                if ( !Form ) {
                    Form = Elm.getElement( 'form' );
                }

                if ( Form )
                {
                    Form.addEvent('submit', function(event) {
                        event.stop();
                    });
                }

                // Date Buttons
                elements = Elm.getElements( '[type="date"]' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    Child = elements[i];

                    new Element('div').wraps( Child );

                    Child.setStyles({
                        'float'  : 'left',
                        'cursor' : 'pointer'
                    });

                    new DatePicker(Child, {
                        timePicker: false,
                        positionOffset: {
                            x: 5,
                            y: 0
                        },
                        pickerClass: 'datepicker_dashboard'
                    });

                    new QUI.controls.buttons.Button({
                        image   : URL_BIN_DIR +'10x10/cancel.png',
                        alt     : 'Datum leeren',
                        title   : 'Datum leeren',
                        Input   : Child,
                        events  : {
                            onClick : QUI.controls.Utils.$clearDateBtn.bind( this )
                        },
                        styles : {
                            top : 1
                        }
                    }).inject(
                        Child.getParent()
                    );
                }

                // Group Buttons
                elements = Elm.getElements( 'input[class="groups"]' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    new QUI.controls.groups.Input(
                        null,
                        elements[ i ]
                    ).create();
                }

                // buttons
                elements = Elm.getElements( '.btn-button' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    Child = elements[ i ];

                    new QUI.controls.buttons.Button({
                        text   : Elm.get( 'data-text' ),
                        image  : Elm.get( 'data-image' ),
                        click  : Elm.get( 'data-click' )
                    }).inject( Child );
                }

                // types
                elements = Elm.getElements( 'input[class="project-types"]' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    new QUI.controls.projects.TypeInput(
                        null,
                        elements[ i ]
                    ).create();
                }

                // media controls
                elements = Elm.getElements( 'input[class="media-image"]' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    new QUI.controls.projects.media.Input(
                        null,
                        elements[ i ]
                    ).create();
                }



                // hidden fields
                /*
                elements = Elm.getElements( 'input[disabled="disabled"]' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    elements[ i ].setStyles({
                        border : 'none'
                    });
                }*/
            });
        },

        /**
         * the clear action for a date button
         */
        $clearDateBtn : function(Btn)
        {
            Btn.getAttribute('Input').value = '';
        }
    };

    return QUI.controls.Utils;
});