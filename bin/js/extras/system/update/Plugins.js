/**
 * PCSG Plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('extras/system/update/Plugins', [

    'controls/buttons/Button'

], function()
{
    QUI.namespace('extras.system.Update');

    QUI.extras.system.Update.Plugins =
    {
        $buttons : {},

        load : function(Win)
        {
            Win.Loader.show();

            QUI.Ajax.get('ajax_update_updatetpl', function(result, Ajax)
            {
                Ajax.getAttribute('Win').setBody( result );

                var i, len, Sort,
                    Table, TBody, Row, Cell,
                    Download, Settings, Update, Activate;

                var tables   = $('admin-update-plugin-list').getElements('table'),
                    $buttons = QUI.extras.system.Update.Plugins.$buttons,
                    letters  = {};

                new QUI.controls.buttons.Button({
                    text      : 'Alle Plugins auf Updates prüfen',
                    textimage : URL_BIN_DIR +'16x16/search.png',
                    events    :
                    {
                        onClick   : function()
                        {
                            QUI.extras.system.Update.Plugins.getAllVersions();
                        }
                    }
                }).inject( $('admin-update-plugin-btns') );

                for (i = 0, len = tables.length; i < len; i++)
                {
                    Table = tables[i];
                    TBody = Table.getElement('tbody');
                    Row   = TBody.rows[1];
                    Cell  = Row.cells[1];

                    letters[ Table.get('data-name').substring(0, 1).toLowerCase() ] = true;

                    Update = new QUI.controls.buttons.Button({
                        name      : 'update_check',
                        text      : 'Updates suchen',
                        textimage : URL_BIN_DIR +'16x16/search.png',
                        Cell      : Cell,
                        Row       : Row,
                        Table     : TBody.getParent(),
                        Win       : Win,
                        styles    : {
                            'float' : 'right'
                        },
                        events : {
                            onClick : QUI.extras.system.Update.Plugins.getVersions
                        }

                    });

                    Update.inject( Cell );

                    Activate = new QUI.controls.buttons.Button({
                        name      : 'plugin_activate',
                        text      : 'Plugin ist aktiviert',
                        alt       : 'Per Klick Plugin deaktivieren.',
                        title     : 'Per Klick Plugin deaktivieren.',
                        textimage : URL_BIN_DIR +'16x16/apply.png',
                        Cell      : Cell,
                        Row       : Row,
                        Table     : TBody.getParent(),
                        Win       : Win,
                        styles    : {
                            'float' : 'right'
                        },
                        events :
                        {
                            onClick : function(Btn)
                            {
                                Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

                                QUI.extras.system.Update.deactivate(
                                    Btn.getAttribute('Table').get('data-plugin'),
                                    function(result, Ajax)
                                    {
                                        QUI.extras.system.Update.Plugins.load(
                                            this.getAttribute('Win')
                                        );
                                    }.bind( Btn )
                                );
                            }
                        }
                    });

                    Activate.inject( Cell );

                    if (Table.get('data-active') != 1)
                    {
                        Activate.setAttribute('alt', 'Per Klick Plugin aktivieren.');
                        Activate.setAttribute('title', 'Per Klick Plugin aktivieren.');
                        Activate.setAttribute('text', 'Plugin ist deaktiviert');
                        Activate.setAttribute('textimage', URL_BIN_DIR +'16x16/cancel.png');
                        Activate.addEvent('onClick', function(Btn)
                        {
                            QUI.extras.system.Update.activate(
                                Btn.getAttribute('Table').get('data-plugin'),
                                function(result, Ajax)
                                {
                                    QUI.extras.system.Update.Plugins.load(
                                        this.getAttribute('Win')
                                    );
                                }.bind( Btn )
                            );
                        });
                    }


                    if (Table.get('data-settings') == 1)
                    {
                        Settings = new QUI.controls.buttons.Button({
                            name      : 'settings',
                            alt       : 'Einstellungen',
                            title     : 'Einstellungen',
                            textimage : URL_BIN_DIR +'16x16/settings.png',
                            Cell      : Cell,
                            Row       : Row,
                            Table     : TBody.getParent(),
                            styles    : {
                                'float' : 'right'
                            },
                            events :
                            {
                                onClick : function(Btn)
                                {
                                    QUI.Plugins.Settings.open(
                                        Btn.getAttribute('Table').get('data-plugin')
                                    );
                                }
                            }
                        });

                        Settings.inject( Cell );
                    }


                    $buttons[ Table.get('data-plugin') ] = {
                        Update : Update
                    };
                }

                // Sortierung
                Sort = new Element('select', {
                    styles : {
                        'float' : 'right'
                    },
                    events :
                    {
                        change : function()
                        {
                            var i, len, letter, table;
                            var tables   = $('admin-update-plugin-list').getElements('table'),
                                value    = this.value,
                                active   = $('show-active-plugins').checked,
                                deactive = $('show-deactive-plugins').checked;

                            for (i = 0, len = tables.length; i < len; i++)
                            {
                                table = tables[i];

                                // wenn deaktivierte nicht anzeigen
                                if (!deactive && table.get('data-active') != 1)
                                {
                                    table.setStyle('display', 'none');
                                    continue;
                                }

                                // wenn aktivierte nicht anzeigen
                                if (!active && table.get('data-active') == 1)
                                {
                                    table.setStyle('display', 'none');
                                    continue;
                                }

                                if (value === '')
                                {
                                    table.setStyle('display', '');
                                    continue;
                                }

                                letter = table.get('data-name').substring(0, 1).toUpperCase();

                                if (letter == value)
                                {
                                    table.setStyle('display', '');
                                } else
                                {
                                    table.setStyle('display', 'none');
                                }
                            }
                        }
                    }
                });

                new Element('option', {
                    html  : 'Alle Plugins anzeigen',
                    value : ''
                }).inject(Sort);

                for (i in letters)
                {
                    new Element('option', {
                        html  : 'Plugins '+ i.toUpperCase() +'*',
                        value : i.toUpperCase()
                    }).inject( Sort );
                }

                Sort.inject( $('admin-update-plugin-btns') );

                // sortierungs einstellungen
                new QUI.controls.buttons.Button({
                    name   : 'sort_settings',
                    title  : 'Sotierungs Einstellungen',
                    alt    : 'Sotierungs Einstellungen',
                    image  : URL_BIN_DIR +'16x16/settings.png',
                    styles : {
                        'float' : 'right'
                    },
                    events :
                    {
                        onClick : function(Btn)
                        {
                            if (document.forms['admin-update-plugin-sort-settings']) {
                                document.forms['admin-update-plugin-sort-settings'].setStyle('display', '');
                            }
                        }
                    }
                }).inject(Sort, 'before');

                new QUI.controls.buttons.Button({
                    name   : 'close_sort_settings',
                    text   : 'schließen',
                    styles : {
                        width  : 100,
                        margin : '10px 0 0'
                    },
                    events :
                    {
                        onClick : function()
                        {
                            if (document.forms['admin-update-plugin-sort-settings']) {
                                document.forms['admin-update-plugin-sort-settings'].setStyle('display', 'none');
                            }

                            $('admin-update-plugin-btns').getElement('select').fireEvent('change');
                        }
                    }
                }).inject( $('admin-update-plugin-sort-close') );


                Win.Loader.hide();

            }, {
                Win     : Win,
                tpltype : 'plugin'
            });
        },

        getVersions : function(Btn)
        {
            var Table = Btn.getAttribute('Table');

            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

            QUI.extras.system.Update.getVersion(Table.get('data-plugin'), function(result, Ajax)
            {
                // Neustes Update und Updates auflisten
                var i, len, Ul, Li, Tr, Download, Install;
                var trs = this.getAttribute('Table').getElements('tr');

                this.setAttribute('textimage', URL_BIN_DIR +'16x16/search.png');

                // kein Update
                if (!result.length)
                {
                    this.setAttribute('text', 'Keine Updates verfügbar');
                    return;
                }


                if (!trs[2])
                {
                    Tr = new Element('tr', {
                        'class' : 'update-listing',
                        html    : '<td colspan="2"></td>'
                    });

                    Tr.inject( trs[0].getParent() );
                } else
                {
                    Tr = trs[2];
                    Tr.set('html', '<td colspan="2"></td>');
                }

                Ul = new Element('ul', {
                    style : {
                        margin : 10
                    }
                });

                for (i = 0, len = result.length; i < len; i++)
                {
                    Li = new Element('li', {
                        'data-name'   : result[i].version,
                        'data-installed'  : result[i].installed ? 1 : 0,
                        'data-downloaded' : result[i].downloaded ? 1 : 0,
                        html : '<span class="version">' + result[i].version +'</span>'
                    });

                    if (i !== 0)
                    {
                        Li.setStyle('display', 'none');
                        Li.addClass('supp');
                    }

                    // es gibt weitere Updates
                    if (len > 1 && i === 0)
                    {
                        new QUI.controls.buttons.Button({
                            text    : '+',
                            title   : 'Weitere Updates auflisten',
                            alt     : 'Weitere Updates auflisten',
                            width   : 32,
                            styles  : {
                                'margin-right' : 10
                            },
                            Li     : Li,
                            events :
                            {
                                onClick : function(Btn)
                                {
                                    if (Btn.getAttribute('text') === '+')
                                    {
                                        Btn.setAttribute('text', '-');
                                        Btn.getAttribute('Li').getParent('ul')
                                            .getChildren('li').each(function(Elm) {
                                                Elm.setStyle('display', '');
                                            });

                                        return;
                                    }

                                    Btn.setAttribute('text', '+');
                                    Btn.getAttribute('Li').getParent('ul')
                                        .getChildren('li').each(function(Elm)
                                        {
                                            if (Elm.hasClass('supp')) {
                                                Elm.setStyle('display', 'none');
                                            }
                                        });
                                }
                            }
                        }).inject(Li, 'top');
                    }

                    Download = new QUI.controls.buttons.Button({
                        textimage : URL_BIN_DIR +'16x16/down.png',
                        text      : 'download',
                        alt       : 'Update herrunter laden',
                        title     : 'Update herrunter laden',
                        Li        : Li,
                        events :
                        {
                            onClick : function(Btn)
                            {
                                Btn.getAttribute('Install').setDisable();

                                QUI.extras.system.Update.download(
                                    Btn.getAttribute('Li'), function()
                                    {
                                        this.getAttribute('Install').setEnable();
                                        this.setAttribute('textimage', URL_BIN_DIR +'16x16/apply.png');
                                    }.bind( Btn )
                                );
                            },
                            onCreate : function(Btn)
                            {
                                if (Btn.getAttribute('Li').get('data-downloaded') == 1) {
                                    Btn.setAttribute('textimage', URL_BIN_DIR +'16x16/apply.png');
                                }
                            }
                        }
                    });

                    Install = new QUI.controls.buttons.Button({
                        textimage : URL_BIN_DIR +'16x16/install.png',
                        text      : 'install',
                        alt       : 'Update installieren',
                        title     : 'Update installieren',
                        Li        : Li,
                        events    :
                        {
                            onClick : function(Btn)
                            {
                                QUI.extras.system.Update.install(
                                    Btn.getAttribute('Li'), function(result, Ajax) {

                                    }
                                );
                            },
                            onCreate : function(Btn)
                            {
                                if (Btn.getAttribute('Li').get('data-downloaded') != 1)
                                {
                                    Btn.setDisable();
                                    return;
                                }
                            }
                        }
                    });

                    Download.setAttribute('Install', Install);
                    Download.inject( Li );
                    Install.inject( Li );

                    new Element('div.loaderbar').inject( Li );

                    Li.inject( Ul );
                }

                Ul.inject( Tr.getElement('td') );

            }.bind(Btn));
        },

        getAllVersions : function()
        {
            var i, len;
            var tables   = $('admin-update-plugin-list').getElements('table'),
                $buttons = QUI.extras.system.Update.Plugins.$buttons;

            // Alle Plugins anzeigen lassen
            $('admin-update-plugin-btns').getElement('select')
                .set('value', '')
                .fireEvent('change');

            // Jede Version anfragen
            for (i = 0, len = tables.length; i < len; i++)
            {
                if (!$buttons[ tables[i].get('data-plugin') ]) {
                    continue;
                }

                if (!$buttons[ tables[i].get('data-plugin') ].Update) {
                    continue;
                }

                $buttons[ tables[i].get('data-plugin') ].Update.onclick();
            }
        }
    };

    return QUI.extras.system.Update.Plugins;
});
