/**
 * System Update
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('extras/system/update/Update', [

    'controls/desktop/Panel',
    'controls/buttons/Button',
    'controls/windows'

], function()
{
    QUI.namespace( 'extras.system.update' );

    QUI.css( QUI.config('dir') +'extras/system/update/Update.css' );

    /**
     * System Update
     */
    QUI.extras.system.Update =
    {
        $BARS : {},

        open : function()
        {
            require(['controls/Settings'], function() {
                QUI.extras.system.Update.$open();
            });
        },

        $open : function()
        {
            var Settings = new QUI.classes.Settings({
                name   : 'update',
                title  : 'System Verwaltung',
                onopen : function(Win)
                {
                    //Win.Loader.show();
                },
                submit : false,
                events :
                {
                    onInit : function()
                    {
                        this.appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Systemcheck',
                                text   : 'Systemcheck',
                                image  : URL_BIN_DIR +'22x22/system.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Win)
                                {
                                    Win.Loader.show();

                                    require(['extras/system/update/Systemcheck'], function(Systemcheck) {
                                        Systemcheck.load( this );
                                    }.bind( Win ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'System aktualisieren',
                                text   : 'System aktualisieren',
                                image  : URL_BIN_DIR +'22x22/system.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Win)
                                {
                                    Win.Loader.show();

                                    require(['extras/system/update/System'], function(UpdateSystem) {
                                        UpdateSystem.load( Win );
                                    }.bind( Win ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Plugins verwalten und aktualisieren',
                                alt    : 'Plugins verwalten und aktualisieren',
                                text   : 'Plugins',
                                image  : URL_BIN_DIR +'22x22/configure.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Win)
                                {
                                    Win.Loader.show();

                                    require(['extras/system/update/Plugins'], function(UpdatePlugins) {
                                        UpdatePlugins.load( Win );
                                    }.bind( Win ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Neue Plugins installieren',
                                text   : 'Neue Plugins installieren',
                                image  : URL_BIN_DIR +'22x22/plugins.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Win)
                                {
                                    Win.Loader.show();

                                    require(['extras/system/update/NewPlugins'], function(NewPlugins) {
                                        NewPlugins.load( Win );
                                    }.bind( Win ));
                                }
                            })
                        ).appendChild(
                            new QUI.controls.buttons.Button({
                                title  : 'Plugins löschen',
                                text   : 'Plugins löschen',
                                image  : URL_BIN_DIR +'22x22/trashcan_empty.png',
                                body   : '&nbsp;',
                                onload : function(Btn, Win)
                                {
                                    Win.Loader.show();

                                    require(['extras/system/update/DeletePlugins'], function(DeletePlugins) {
                                        DeletePlugins.load( Win );
                                    }.bind( Win ));
                                }
                            })
                        );
                    }
                }
            });
        },

        getVersion : function(uplugin, onfinish)
        {
            QUI.Ajax.get('ajax_update_getversion', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish,
                uplugin  : uplugin
            });
        },

        download : function(Row, onfinish)
        {
            require(['classes/controls/Progressbar'], function(Progressbar)
            {
                if (Row.nodeName === 'LI' ||
                    Row.nodeName === 'DIV')
                {
                    Row.getElement('.loaderbar').set('html', '');
                } else
                {
                    Row.cells[1].set('html', '');
                }

                var Container = (Row.nodeName === 'LI' || Row.nodeName === 'DIV') ? Row.getElement('.loaderbar') : Row.cells[1],

                    file = Row.get('data-name'),

                    Bar  = new QUI.classes.Progressbar({
                        container   : Container,
                        displayText : true,
                        fx          : false,
                        onComplete  : onfinish || function() {
                            // nothing
                        }
                    });

                QUI.extras.system.Update.$BARS[ file ] = {
                    bar   : Bar,
                    frame : new Element('iframe', {
                        src    : URL_DIR +'admin/bin/update.php?file='+ file +'&',
                        styles : {
                            position : 'absolute',
                            top      : -200,
                            left     : -200,
                            width    : 50,
                            height   : 50
                        }
                    })
                };

                // iframe aufbauen und datei runterladen
                QUI.extras.system.Update.$BARS[ file ].frame.inject( document.body );

            }.bind(this, [Row, onfinish]));
        },

        errorMessage : function(error, file)
        {


            //alert(error);
        },

        setStatus : function(st, file)
        {
            if (typeof QUI.extras.system.Update.$BARS[ file ] === 'undefined') {
                return;
            }

            var Bar = QUI.extras.system.Update.$BARS[ file ].bar;

            if (Bar.get() == st) {
                return;
            }

            Bar.set( st );

            if (status == 100)
            {
                // dl fertig
                QUI.extras.system.Update.$BARS[ file ].frame.destroy();
            }
        },

        install : function(Row, onfinish)
        {
            QUI.Ajax.post('ajax_update_install', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish,
                file     : Row.get('data-name')
            });
        },

        activate : function(plugin, onfinish)
        {
            QUI.Ajax.post('ajax_update_plugins_activate', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish,
                plugin   : plugin
            });
        },

        deactivate : function(plugin, onfinish)
        {
            QUI.Ajax.post('ajax_update_plugins_deactivate', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish,
                plugin   : plugin
            });
        },

        del : function(plugin, onfinish)
        {
            QUI.Windows.create('submit', {
                name     : 'update',
                image    : URL_BIN_DIR +'16x16/trashcan_empty.png',
                title    : 'Plugin wirklich löschen?',
                text     : 'Möchten Sie das Plugin '+ plugin +' wirklich löschen?',
                information :
                    '<p style="margin: 0 0 10px 0;">' +
                        '<input type="checkbox" id="plugin-delete-database-'+ plugin +'" /> ' +
                        '<label for="plugin-delete-database-'+ plugin +'">Datenbankinhalte des Plugins auch löschen</label>' +
                    '</p>'+
                    '<p>Alle Datenbankeinträge, Daten und Einstellungen werden unwiderruflich gelöscht.</p>',
                height   : 200,
                width    : 500,
                onfinish : onfinish,
                plugin   : plugin,
                events   :
                {
                    onSubmit : function(Win)
                    {
                        var id     = 'plugin-delete-database-'+ Win.getAttribute('plugin'),
                            params = {
                                database : false
                            };

                        if ($(id)) {
                            params.database = $(id).checked ? true : false;
                        }

                        QUI.Ajax.post('ajax_update_plugins_delete', function(result, Ajax)
                        {
                            if (Ajax.getAttribute('onfinish')) {
                                Ajax.getAttribute('onfinish')(result, Ajax);
                            }
                        }, {
                            onfinish : Win.getAttribute('onfinish'),
                            plugin   : Win.getAttribute('plugin'),
                            params   : JSON.encode( params )
                        });
                    },
                    onClose : function(Win)
                    {
                        if (Win.getAttribute('onfinish')) {
                            Win.getAttribute('onfinish')(false, Win);
                        }

                        return true;
                    }
                },
                submit : false
            });
        }
    };

    return QUI.extras.system.Update;
});
