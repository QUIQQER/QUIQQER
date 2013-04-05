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

    QUI.namespace( 'controls' );

    if ( typeof QUI.controls.Utils !== 'undefined' ) {
        return QUI.controls.Utils;
    }

    QUI.controls.Utils =
    {
        /**
         * Is the Object a QUI Control?
         *
         * @method QUI.lib.Controls#isControl
         * @return {Bool} true or false
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
         * Highlights a control
         *
         * @method QUI.lib.Controls#highlight
         * @param {DOMNode} Element
         */
        highlight : function(Element)
        {
            if ( !Element ) {
                return;
            }

            var quiid = Element.get( 'data-quiid' );

            if ( !quiid ) {
                return;
            }

            QUI.Controls.getById( quiid ).highlight();
        },

        /**
         * Normalize a control, if it is was highlighted
         *
         * @method QUI.lib.Controls#normalize
         * @param {DOMNode} Element
         */
        normalize : function(Element)
        {
            if ( !Element ) {
                return;
            }

            var quiid = Element.get( 'data-quiid' );

            if ( !quiid ) {
                return;
            }

            QUI.Controls.getById( quiid ).normalize();
        },


        /**
         * Parse an DOM Node Element
         *
         * Search all control elements in the node element
         * and parse it to the specific controls
         */
        parse : function(Elm)
        {
            var Form = false;

            if ( Elm.nodeName == 'FORM' ) {
                Form = Elm;
            }

            if ( !Form ) {
                Form = Elm.getElement( 'form' );
            }

            if ( Form )
            {
                // ist that good?
                Form.addEvent('submit', function(event) {
                    event.stop();
                });
            }

            // Button
            if ( Elm.getElement( '.btn-button' ) ) {
                this.parseButtons( Elm );
            }

            // Date
            if ( Elm.getElement( 'input[type="date"]' ) ) {
                this.parseDate( Elm );
            }

            // Groups
            if ( Elm.getElement( 'input.groups' ) ) {
                this.parseGroups( Elm );
            }

            // Media Types
            if ( Elm.getElement( 'input.media-image' ) ) {
                this.parseMediaInput( Elm );
            }

            // Project Types
            if ( Elm.getElement( 'input.project-types' ) ) {
                this.parseProjectTypes( Elm );
            }

            // User And Groups
            if ( Elm.getElement( 'input.users_and_groups' ) ) {
                this.parseUserAndGroups( Elm );
            }

            // disabled fields


            // hidden fields
            /*
            elements = Elm.getElements( 'input[disabled="disabled"]' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                elements[ i ].setStyles({
                    border : 'none'
                });
            }*/
        },

        /**
         * Search all Elements with .btn-button and convert it to a button
         *
         * @param {DOMNode} Elm - parent node, this element in which is searched for
         */
        parseButtons : function(Elm)
        {
            require(['controls/buttons/Button'], function(QUI_Button)
            {
                // buttons
                var i, len, Child, elements;

                elements = Elm.getElements( '.btn-button' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    Child = elements[ i ];

                    new QUI.controls.buttons.Button({
                        text   : Child.get( 'data-text' ),
                        image  : Child.get( 'data-image' ),
                        click  : Child.get( 'data-click' )
                    }).inject( Child );
                }
            });
        },

        /**
         * Search all input[type="date"] and make a control
         *
         * @param {DOMNode} Elm - parent node, this element in which is searched for
         */
        parseDate : function(Elm)
        {
            var self = this;

            require(['package/quiqqer/calendar/bin/Calendar'], function(DatePicker)
            {
                var i, len, elements, Child;

                elements = Elm.getElements( 'input[type="date"]' );

                // Date Buttons
                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    Child = elements[i];

                    new Element( 'div' ).wraps( Child );

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
                            onClick : self.$clearDateBtn.bind( self )
                        },
                        styles : {
                            top : 1
                        }
                    }).inject(
                        Child.getParent()
                    );
                }
            });
        },

        /**
         * Search all input[class="groups"] and convert it to a control
         *
         * @param {DOMNode} Elm - parent node, this element in which is searched for
         */
        parseGroups : function(Elm)
        {
            require(['controls/usersAndGroups/Input'], function()
            {
                var i, len, elements;

                elements = Elm.getElements( 'input.groups' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    new QUI.controls.usersAndGroups.Input(
                        null,
                        elements[ i ]
                    ).create();
                }
            });
        },

        /**
         * Search all input[class="media-image"] and convert it to a control
         *
         * @param {DOMNode} Elm - parent node, this element in which is searched for
         */
        parseMediaInput : function(Elm)
        {
            require(['controls/projects/media/Input'], function(Qui_ProjectMediaInput)
            {
                var i, len, elements;

                elements = Elm.getElements( 'input.media-image' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    new QUI.controls.projects.media.Input(
                        null,
                        elements[ i ]
                    ).create();
                }
            });
        },

        /**
         * Search all input[class="project-types"] and convert it to a control
         *
         * @param {DOMNode} Elm - parent node, this element in which is searched for
         */
        parseProjectTypes : function(Elm)
        {
            require(['controls/projects/TypeInput'], function(QUI_TypeInput)
            {
                var i, len, elements;

                elements = Elm.getElements( 'input.project-types' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    new QUI.controls.projects.TypeInput(
                        null,
                        elements[ i ]
                    ).create();
                }
            });
        },

        /**
         * Search all Elements with the class users_and_groups and convert it to a control
         *
         * @param {DOMNode} Elm - parent node, this element in which is searched for
         */
        parseUserAndGroups : function(Elm)
        {
            require(['controls/usersAndGroups/Input'], function()
            {
                var i, len, elements, Label, Control;

                elements = Elm.getElements( '.users_and_groups' );

                for ( i = 0, len = elements.length; i < len; i++ )
                {
                    Control = new QUI.controls.usersAndGroups.Input(
                        null,
                        elements[ i ]
                    );

                    if ( elements[ i ].id )
                    {
                        Label = document.getElement( 'label[for="'+ elements[ i ].id +'"]' );

                        if ( Label ) {
                            Control.setAttribute( 'label', Label );
                        }
                    }

                    Control.create();
                }
            });
        },

        /**
         * the clear action for a date button
         *
         * @param {DOMNode} Elm - parent node, this element in which is searched for
         */
        $clearDateBtn : function(Btn)
        {
            Btn.getAttribute( 'Input' ).value = '';
        }
    };

    return QUI.controls.Utils;
});