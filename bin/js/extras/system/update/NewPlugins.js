/**
 * PCSG Neue Plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('extras/system/update/NewPlugins', [

    'controls/buttons/Button'

], function()
{
    QUI.namespace('extras/system/Update');

    QUI.extras.system.Update.NewPlugins =
    {
        load : function(Win)
        {
            Win.Loader.show();

            QUI.Ajax.get('ajax_update_getplugins', function(result, Ajax)
            {
                var Win = Ajax.getAttribute('Win');

                Win.setBody('Es stehen keine Plugins zur Verfügung');

                if (result.length)
                {
                    Win.setBody('<div id="plugins-server-list"></div>');

                    var i, len, Elm;

                    var func_btn_install = function(Btn)
                    {
                        QUI.extras.system.Update.download(
                            Btn.getAttribute('Elm'),
                            function(result, Ajax) // installieren
                            {
                                QUI.extras.system.Update.NewPlugins.install( this );
                            }.bind( Btn )
                        );
                    };

                    for (i = 0, len = result.length; i < len; i++)
                    {
                        if (!result[i].description) {
                            result[i].description = '';
                        }

                        Elm = new Element('div', {
                            'class'     : 'server-plugin',
                            'data-name' : result[i].file.replace('.zip', ''),
                            html :
                                '<h2>'+ result[i].name +'</h2>'+
                                '<div class="version">Version: '+ result[i].file.replace('.zip', '') +'</div>'+
                                '<div class="description">'+ result[i].description +'</div>' +
                                '<div class="loaderbar"></div>'
                        });

                        new QUI.controls.buttons.Button({
                            text      : 'installieren',
                            textimage : URL_BIN_DIR +'16x16/plugins.png',
                            Elm       : Elm,
                            Win       : Win,
                            styles    : {
                                'float' : 'right'
                            },
                            onclick   : func_btn_install
                        }).create().inject( Elm );

                        Elm.inject( $('plugins-server-list') );
                    }
                }

                Win.Loader.hide();
            }, {
                Win     : Win,
                onError : function(Exception, Ajax)
                {
                    Ajax.getAttribute('Win').Loader.hide();

                    QUI.triggerError( Exception );
                }
            });
        },

        install : function(Btn)
        {
            var Elm = Btn.getAttribute('Elm');

            Elm.getElement('.loaderbar').set('html',
                '<img src="'+ URL_BIN_DIR +'images/loader.gif" style="float: left;" /> ' +
                '<span style="line-height: 16px;">Installation startet ...</span>'
            );

            QUI.Ajax.post('ajax_update_plugins_install_newplugin', function(result, Ajax)
            {
                Ajax.getAttribute('Elm').getElement('.loaderbar').set('html', '');

                QUI.MH.addInformation(
                    'Installation war erfolgreich. Damit das Plugin verfügbar ist müssen Sie dieses aktivieren.'
                );

                QUI.extras.system.Update.NewPlugins.load(
                    Ajax.getAttribute('Btn').getAttribute('Win')
                );
            }, {
                file : Elm.get('data-name'),
                Elm  : Elm,
                Btn  : Btn,
                onError : function(Exception, Ajax)
                {
                    Ajax.getAttribute('Win').Loader.hide();

                    QUI.triggerError( Exception );
                }
            });
        }
    };

    return QUI.extras.system.Update.NewPlugins;
});
