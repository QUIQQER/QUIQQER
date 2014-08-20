/**
 * control utils - helper for all controls
 *
 * @module utils/Controls
 * @author www.pcsg.de (Henning Leutz)
 */

define({

    /**
     * Parse an DOM Node Element
     *
     * Search all control elements in the node element
     * and parse it to the specific control
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
        if ( Elm.getElement( 'input[type="date"],input[type="datetime"]' ) ) {
            this.parseDate( Elm );
        }

        // Groups
        if ( Elm.getElement( 'input.groups,input.group' ) ) {
            this.parseGroups( Elm );
        }

        // Media Types
        if ( Elm.getElement( 'input.media-image' ) ) {
            this.parseMediaInput( Elm );
        }

        // User And Groups
        if ( Elm.getElement( 'input.users_and_groups' ) ) {
            this.parseUserAndGroups( Elm );
        }

        // User And Groups
        if ( Elm.getElement( 'input.user' ) ) {
            this.parseUser( Elm );
        }

        // projects
        if ( Elm.getElement( 'input.project' ) ) {
            this.parseProject( Elm );
        }

        // Project Types
        if ( Elm.getElement( 'input.project-types' ) ) {
            this.parseProjectTypes( Elm );
        }

        // project site
        if ( Elm.getElement( 'input.project-site' ) ) {
            this.parseProjectSite( Elm );
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
        require(['qui/controls/buttons/Button'], function(QUIButton)
        {
            // buttons
            var i, len, Child, elements;

            elements = Elm.getElements( '.btn-button' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                Child = elements[ i ];

                new QUIButton({
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

        require([
            'package/quiqqer/calendar/bin/Calendar',
            'qui/controls/buttons/Button',
            'qui/utils/Elements'
        ], function(DatePicker, QUIButton, ElementUtils)
        {
            var i, len, elements, datetime,
                Child, Parent, Picker;

            elements = Elm.getElements( 'input[type="date"],input[type="datetime"]' );

            // Date Buttons
            for ( i = 0, len = elements.length; i < len; i++ )
            {
                Child    = elements[i];
                Parent   = new Element( 'div' ).wraps( Child );
                datetime = Parent.getElement( 'input[type="datetime"]' ) ? true : false;

                if ( datetime )
                {
                    Child.placeholder = 'YYYY-MM-DD HH:MM:SS';
                    Child.set( 'data-type', 'datetime' );
                } else
                {
                    Child.placeholder = 'YYYY-MM-DD';
                    Child.set( 'data-type', 'date' );
                }


                Child.autocomplete = 'off';

                Child.setStyles({
                    'float'  : 'left',
                    'cursor' : 'pointer'
                });

                Picker = new DatePicker(Child, {
                    timePicker     : datetime ? true : false,
                    datetime       : datetime,
                    positionOffset : {
                        x: 5,
                        y: 0
                    },
                    pickerClass : 'datepicker_dashboard',
                    onSelect    : function(UserDate, elmList, Obj)
                    {
                        var i, len;

                        if ( typeOf( elmList ) === 'array' )
                        {
                            for ( i = 0, len = elmList.length; i < len; i++ )
                            {
                                if ( elmList[ i ].get( 'data-type' ) == 'date' )
                                {
                                    elmList[ i ].value = UserDate.format( '%Y-%m-%d' );
                                } else
                                {
                                    elmList[ i ].value = UserDate.format( 'db' );
                                }
                            }

                        } else if ( typeOf( elmList ) === 'element' )
                        {
                            if ( elmList.get('data-type') == 'date' )
                            {
                                elmList.value = UserDate.format( '%Y-%m-%d' );
                            } else
                            {
                                elmList.value = UserDate.format( 'db' );
                            }
                        }
                    }
                });

                Picker.picker.setStyles({
                    zIndex : ElementUtils.getComputedZIndex( Child )
                });

                new QUIButton({
                    image   : 'icon-remove',
                    alt     : 'Datum leeren',
                    title   : 'Datum leeren',
                    Input   : Child,
                    events  :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute( 'Input' ).value = '';
                        }
                    },
                    styles : {
                        top : 1
                    }
                }).inject( Child.getParent() );
            }

        }, function(err)
        {
            require(['qui/QUI'], function(QUI)
            {
                QUI.getMessageHandler(function(MH)
                {
                    MH.addAttention(
                        'Das Kalender Packet konnte nicht gefunden werden.' +
                        'Bitte installieren Sie quiqqer/calendar'
                    );
                });
            });
        });
    },

    /**
     * Search all input[class="groups"] and convert it to a control
     *
     * @param {DOMNode} Elm - parent node, this element in which is searched for
     */
    parseGroups : function(Elm)
    {
        require(['controls/groups/Input'], function(GroupInput)
        {
            var i, len, elements;

            elements = Elm.getElements( 'input.groups,input.group' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                new GroupInput(
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
        require(['controls/projects/project/media/Input'], function(ProjectMediaInput)
        {
            var i, len, elements;

            elements = Elm.getElements( 'input.media-image' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                new ProjectMediaInput(
                    null,
                    elements[ i ]
                ).create();
            }
        });
    },

    /**
     * Search all input[class="project"] and convert it to a control
     *
     * @param {DOMNode} Elm - parent node, this element in which is searched for
     */
    parseProject : function(Elm)
    {
        require(['controls/projects/Input'], function(ProjectInput)
        {
            var i, len, elements;

            elements = Elm.getElements( 'input.project' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                new ProjectInput({
                    multible : false,
                }, elements[ i ] ).create();
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
        require(['controls/projects/TypeInput'], function(TypeInput)
        {
            var i, len, elements;

            elements = Elm.getElements( 'input.project-types' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                new TypeInput(
                    null,
                    elements[ i ]
                ).create();
            }
        });
    },

    /**
     * Search all input[class="project-site"] and convert it to a control
     *
     * @param {DOMNode} Elm - parent node, this element in which is searched for
     */
    parseProjectSite : function(Elm)
    {
        require(['controls/projects/project/site/Input'], function(SiteInput)
        {
            var i, len, elements;

            elements = Elm.getElements( 'input.project-site' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                new SiteInput(
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
        require(['controls/usersAndGroups/Input'], function(UserAndGroup)
        {
            var i, len, elements, Label, Control;

            elements = Elm.getElements( '.users_and_groups' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                Control = new UserAndGroup(
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
     * Search all Elements with the class user and convert it to a control
     *
     * @param {DOMNode} Elm - parent node, this element in which is searched for
     */
    parseUser : function(Elm)
    {
        require(['controls/users/Input'], function(UserInput)
        {
            var i, len, elements, Label, Control;

            elements = Elm.getElements( '.user' );

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                Control = new UserInput(
                    {
                        max : 1
                    },
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
    }
});