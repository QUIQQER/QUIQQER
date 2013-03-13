/**
 * Control Manager
 *
 * @author www.pcsg.de (Henning Leutz)

 * @module lib/Controls
 * @package QUI.lib.Controls
 * @namespace QUI.lib
 */
/*
define('lib/Controls', function()
{
    QUI.namespace('lib');

    QUI.lib.Controls =
    {
        /**
         * Is the Object a QUI Control?
         *
         * @method QUI.lib.Controls#isControl
         * @return {Bool}
         */
/*
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
         * Search in html for controls
         *
         * @method QUI.lib.Controls#parse
         *
         * @param {DOMNode} Parent - eq FORM
         */
/*
        parse : function(Parent)
        {
            require([

                'lib/media/Controls',
                'controls/projects/TypeInput'

            ], function(MediaControl, TypeInput)
            {
                var i, len, Elm;
                var children = this.getElements('input');

                if ( !children.length ) {
                    return;
                }

                for ( i = 0, len = children.length; i < len; i++ )
                {
                    Elm = children[i];

                    if ( Elm.hasClass('media-file') ||
                         Elm.hasClass('media-image') )
                    {
                        MediaControl.InputButton( Elm );
                        continue;
                    }

                    if ( Elm.hasClass('media-folder') )
                    {
                        MediaControl.InputButton( Elm );
                        continue;
                    }

                    if ( Elm.hasClass('date') )
                    {
                        QUI.lib.Controls.Calendar( Elm );
                        continue;
                    }

                    if ( Elm.hasClass('project-types') )
                    {
                        new TypeInput( Elm );
                        continue;
                    }

                    // disabled fields
                    if ( Elm.disabled && Elm.type === 'text' )
                    {
                        Elm.setStyle('display', 'none');

                        new Element('span.data', {
                            html   : Elm.value,
                            styles : {
                                margin  : '2px 5px',
                                opacity : 0.8
                            }
                        }).inject( Elm.getParent() );

                        continue;
                    }
                }

            }.bind( Parent ));
        }

        /**
         * Create an Input to an Calendar Input
         *
         * @method QUI.lib.Controls#Calendar
         *
         * @param {DOMNode} Input
         */
        /*
        Calendar : function(Input)
        {
            require([
                'controls/calendar/Calendar', 'controls/buttons/Button'
            ], function(QUI_Calendar, QUI_Button)
            {
                new Element('div').wraps( Input );

                Input.setStyles({
                    'float'  : 'left',
                    'cursor' : 'pointer'
                });

                new QUI_Calendar(Input, {
                    lang : 'de'
                });

                new QUI_Button({
                    image   : URL_BIN_DIR +'10x10/cancel.png',
                    alt     : 'Datum leeren',
                    title   : 'Datum leeren',
                    Input   : Input,
                    events  :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute('Input').value = '';
                        }
                    },
                    styles : {
                        top : 1
                    }
                }).inject(
                    Input.getParent()
                );
            });
        },

        /**
         * Creates an Grid
         *
         * @method QUI.lib.Controls#Grid
         *
         * @param DOMNode Container - Parent Element of the Grid
         * @param Object options - Grid options
         * @param Function callback - Callback Function if all is loaded
         */
        /*
        Grid : function(Container, options, callback)
        {
            require(['controls/grid/Grid'], function(QUI_Grid)
            {
                var Grid = new QUI_Grid(Container, options);

                if ( typeOf( callback ) === 'function' ) {
                    callback( Grid );
                }
            });
        }

    };

    return QUI.lib.Controls;
});

*/
