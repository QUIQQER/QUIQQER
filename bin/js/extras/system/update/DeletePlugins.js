/**
 * PCSG Neue Plugins
 *
 * @author Henning Leutz (PCSG)
 */

define('extras/system/update/DeletePlugins', [

    'controls/buttons/Button'

], function()
{
    QUI.namespace('pcsg.extras.system.Update');

    QUI.extras.system.Update.DeletePlugins =
    {
        load : function(Win)
        {
            Win.Loader.show();

            QUI.Ajax.get('ajax_update_deletetpl', function(result, Ajax)
            {
                var tables, i, len, Btn,
                    func_btn_deactivate;

                var Win = Ajax.getAttribute('Win');

                Win.setBody( result );

                // lösch und deaktivier buttons
                tables = $('admin-delete-plugin-list').getElements('table');

                func_btn_deactivate = function(Btn)
                {
                    Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

                    QUI.extras.system.Update.deactivate(Btn.getAttribute('plugin'), function()
                    {
                        Btn.setAttribute('text', 'Plugin löschen');
                        Btn.setAttribute('textimage', URL_BIN_DIR +'16x16/trashcan_empty.png');
                        Btn.setAttribute('onclick', QUI.extras.system.Update.DeletePlugins.exec);

                    }.bind( Btn ));
                };

                for (i = 0, len = tables.length; i < len; i++)
                {
                    Btn = new QUI.controls.buttons.Button({
                        name      : tables[i].get('data-plugin'),
                        plugin    : tables[i].get('data-plugin'),
                        Table     : tables[i],
                        Win       : Win,
                        textimage : URL_BIN_DIR +'16x16/trashcan_empty.png',
                        text      : 'Plugin löschen',
                        styles    : {
                            'float' : 'right'
                        },
                        onclick : QUI.extras.system.Update.DeletePlugins.exec
                    });

                    Btn.create().inject( tables[i].getElement('td') );

                    if (tables[i].get('data-active') == 1)
                    {
                        Btn.setAttribute('text', 'Plugin deaktivieren');
                        Btn.setAttribute('textimage', URL_BIN_DIR +'16x16/cancel.png');
                        Btn.setAttribute('onclick', func_btn_deactivate);
                    }
                }

                Win.Loader.hide();

            }, {
                Win : Win
            });
        },

        exec : function(Btn)
        {
            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

            QUI.system.Update.del(Btn.getAttribute('plugin'), function()
            {
                QUI.system.Update.DeletePlugins.load(
                    Btn.getAttribute('Win')
                );
            }.bind( Btn ));
        }
    };

    return QUI.extras.system.Update.DeletePlugins;
});
