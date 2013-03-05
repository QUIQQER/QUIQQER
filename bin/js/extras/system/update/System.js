/**
 * PCSG System Check
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('extras/system/update/System', [

    'controls/buttons/Button'

], function()
{
    QUI.namespace('extras.system.Update');

    QUI.extras.system.Update.System =
    {
        load : function(Win)
        {
            QUI.Ajax.get('ajax_update_updatetpl', function(result, Ajax)
            {
                Ajax.getAttribute('Win').setBody( result );

                // Setup Button
                new QUI.controls.buttons.Button({
                    name      : 'setup',
                    text      : 'System Setup ausführen',
                    textimage : URL_BIN_DIR +'16x16/setup.png',
                    Win       : Win,
                    events    :
                    {
                        onClick : function(Btn)
                        {
                            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

                            QUI.extras.system.Update.System.setup(function(result, Ajax)
                            {
                                this.setAttribute('textimage', URL_BIN_DIR +'16x16/setup.png');

                                QUI.MH.addInformation('Setup wurde erfolgreich durchgeführt');

                            }.bind(Btn));
                        }
                    }
                }).inject( $('admin-update-cms-btns') );

                // Optimierungs Button
                new QUI.controls.buttons.Button({
                    name      : 'setup',
                    text      : 'Datenbank optimieren',
                    textimage : URL_BIN_DIR +'16x16/database.png',
                    Win       : Win,
                    events    :
                    {
                        onClick   : function(Btn)
                        {
                            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

                            QUI.extras.system.Update.System.optimize(function(result, Ajax)
                            {
                                this.setAttribute('textimage', URL_BIN_DIR +'16x16/database.png');

                                QUI.MH.addInformation('Datenbank Optimierung wurde erfolgreich durchgeführt');

                            }.bind(Btn));
                        }
                    }
                }).inject( $('admin-update-cms-btns') );



                Ajax.getAttribute('Win').Loader.hide();

                // CMS Version laden
                QUI.extras.system.Update.getVersion(false, function(result, AJax)
                {
                    if (!$('admin-update-cms-list')) {
                        return;
                    }

                    var i, len, TRs, Elm,
                        Download, Install, installed;

                    var TBody = $('admin-update-cms-list').getElement('tbody');

                    // events
                    var func_click_download = function(Btn)
                    {
                        Btn.getAttribute('Install').setDisable();

                        QUI.extras.system.Update.download(
                            Btn.getAttribute('Row'), function()
                            {
                                this.getAttribute('Install').setEnable();
                            }.bind( Btn )
                        );
                    };

                    var func_click_update = function(Btn)
                    {
                        QUI.extras.system.Update.install(
                            Btn.getAttribute('Row'), function(result, Ajax) {

                            }
                        );
                    };

                    var func_on_update_btn_create = function(Btn)
                    {
                        var Row = Btn.getAttribute('Row');

                        if (Row.get('data-downloaded') != 1)
                        {
                            Btn.setDisable();
                            return;
                        }

                        if (Row.cells[1].get('html') === '') {
                            Row.cells[1].set('html', 'herunter geladen');
                        }
                    };

                    /**
                     * Update Liste
                     */
                    $('admin-update-cms-list').getElement('tbody tr').destroy();

                    for (i = 0, len = result.length; i < len; i++)
                    {
                        Elm = new Element('tr', {
                            'data-name'       : result[i].version,
                            'data-installed'  : result[i].installed ? 1 : 0,
                            'data-downloaded' : result[i].downloaded ? 1 : 0,
                            'class' : (i%2) ? '' : 'odd',
                            html    : '<td>'+ result[i].version +'</td>' +
                                    '<td>'+ (result[i].installed ? 'installiert' : '') +'</td>' +
                                    '<td></td>'
                        });

                        if (result[i].installed) {
                            Elm.addClass('installed');
                        }

                        Elm.inject( TBody );
                    }

                    TRs = TBody.getElements('tr');

                    for (i = 0, len = TRs.length; i < len; i++)
                    {
                        Download = new QUI.controls.buttons.Button({
                            textimage : URL_BIN_DIR +'16x16/down.png',
                            text      : 'download',
                            alt       : 'Update herrunter laden',
                            title     : 'Update herrunter laden',
                            Row       : TRs[i],
                            events    : {
                                onClick : func_click_download
                            }
                        });

                        Install = new QUI.controls.buttons.Button({
                            textimage : URL_BIN_DIR +'16x16/install.png',
                            text      : 'install',
                            alt       : 'Update installieren',
                            title     : 'Update installieren',
                            Row       : TRs[i],
                            events    :
                            {
                                onClick  : func_click_update,
                                onCreate : func_on_update_btn_create
                            }
                        });

                        Download.setAttribute('Install', Install);

                        Download.inject( TRs[i].cells[2] );
                        Install.inject( TRs[i].cells[2] );
                    }
                });
            }, {
                Win : Win
            });
        },

        setup : function(onfinish)
        {
            QUI.Ajax.post('ajax_update_systemsetup', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish
            });
        },

        optimize : function(onfinish)
        {
            QUI.Ajax.post('ajax_update_systemoptimize', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish
            });
        }
    };

    return QUI.extras.system.Update.System;
});
