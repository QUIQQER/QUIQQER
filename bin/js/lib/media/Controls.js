/**
 * Media Controls
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('lib/media/Controls', function()
{
    QUI.namespace('lib.media.Controls');

    QUI.lib.media.Controls =
    {
        InputButton : function(Input, params)
        {
            if (Input.nodeName != 'INPUT') {
                return false;
            }

            params = QUI.lib.Utils.combine(params, {
                onsubmit : function()
                {

                },
                events :
                {
                    click : function()
                    {
                        console.info( '@mor, es gibt noch kein media' );
                    }
                },
                Input : Input
            });

            var Btn = this.Button( params ),
                Elm = new Element('div', {
                    styles : {
                        'float' : 'left'
                    }
                });

            Input.setStyles({
                'float'  : 'left',
                'cursor' : 'pointer'
            });

            Input.addEvent('click', function(event)
            {
                this.fireEvent('click');
                event.stop();
            }.bind(Btn));

            Elm.wraps( Input );
            Btn.create().inject( Elm );

            return Btn;
        },

        Button : function(params)
        {
            params = QUI.lib.Utils.combine(params, {
                image : URL_BIN_DIR +'16x16/media.png'
            });

            return new QUI.controls.buttons.Button( params );
        }
    };

    return QUI.lib.media.Controls;
});
