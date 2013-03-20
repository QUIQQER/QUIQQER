/**
 * Allgemeine Controls für den Adminbereich
 * @author PCSG - Henning
 *
 * @depricated
 */

if (typeof _pcsg === 'undefined') {
    var _pcsg = {};
}

_pcsg.Controls = function(){};

_pcsg.Controls.SiteSearchButton =
{
    get : function(settings)
    {
        "use strict";

        if (typeof _ptools.Button)
        {
            return new _ptools.Button({
                name     : '_SiteSearchButton',
                onclick : function(Btn) {
                    _pcsg.Controls.SiteSearchButton.open(Btn);
                },
                image     : URL_BIN_DIR +'16x16/home.png',
                settings : settings,
                alt      : 'Seiten suche',
                title    : 'Seiten suche'
            });
        }
    },

    open : function(_button)
    {
        "use strict";

        var settings;

        if (typeof _button.getAttribute == 'function')
        {
            settings = _button.getAttribute('settings');
        } else
        {
            settings = _button;
        }

        _pcsg.SiteSearch.open(settings);
    }
};

_pcsg.SiteSearch =
{
    Btn   : null,
    List  : null,
    Win   : null,

    open : function(settings)
    {
        settings = settings || {};
        var onsubmit = false;

        if (typeof settings.onsubmit == 'function'){
            onsubmit = settings.onsubmit;
        }

        _pcsg.SiteSearch.Win = new _ptools.Window({
            title    : 'Seiten Suche',
            image    : URL_BIN_DIR +'16x16/search.png',
            width    : 700,
            onsubmit : onsubmit,
            height   : 400,
            onclose  : function(Win)
            {
                if (_pcsg.SiteSearch.Win) {
                    _pcsg.SiteSearch.Win = null;
                }
            },
            onopen  : function(Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_site_search_template', function(result, Ajax)
                {
                    var Win = Ajax.getAttribute('Win');

                    Win.setBody( result );

                    _pcsg.SiteSearch.Btn = new _ptools.Button({
                        text    : 'suchen',
                        onclick : function(Btn)
                        {
                            var params = {
                                project : $('search_window_project').value,
                                lang    : $('search_window_lang').value
                            };

                            _pcsg.SiteSearch.search($('search_window_searchstring').value, params, function(result, Ajax)
                            {
                                if (!result.length)
                                {
                                    var data = [];

                                    data.push({
                                        name : 'Keine Ergebnisse gefunden'
                                    });

                                    _pcsg.SiteSearch.List.setData({
                                        data : data
                                    });

                                    return;
                                }

                                _pcsg.SiteSearch.List.setData({
                                    data : result
                                });
                            });
                        }
                    });

                    $('site_search_btn').appendChild(
                        _pcsg.SiteSearch.Btn.create()
                    );

                    var buttons = [
                        {
                            name      : 'goto',
                            onclick   : '_pcsg.SiteSearch.gotoSite',
                            text      : 'öffnen',
                            title     : 'Zur Seite springen',
                            disabled  : true,
                            textimage : URL_BIN_DIR +'16x16/1rightarrow.png'
                        },
                        {
                            name      : 'pasteSite',
                            onclick   : '_pcsg.SiteSearch.pasteSite',
                            text      : 'auswählen',
                            title     : 'Seite auswählen und einfügen',
                            disabled  : true,
                            textimage : URL_BIN_DIR +'16x16/paste.png'
                        },
                        {
                            name      : 'copy',
                            onclick   : '_pcsg.SiteSearch.copy',
                            text      : 'kopieren',
                            title     : 'Seite kopieren',
                            disabled  : true,
                            textimage : URL_BIN_DIR +'16x16/copy.png'
                        },
                        {
                            name      : 'linked',
                            onclick   : '_pcsg.SiteSearch.linked',
                            text      : 'verlinken',
                            title     : 'Verknüpfung in die aktuelle Seite einfügen',
                            disabled  : true,
                            textimage :  URL_BIN_DIR +'16x16/linked.png'
                        }
                    ];

                    _pcsg.SiteSearch.List = new omniGrid('site_search_tbl', {
                        columnModel: [
                            {header: 'ID'    , dataIndex: 'id'   ,  dataType  : 'number', width : 50},
                            {header: 'Name'  , dataIndex: 'name' ,  dataType  : 'string', width : 200},
                            {header: 'URL'   , dataIndex: 'rurl',    dataType : 'string', width : 200},
                            {header: ''      , dataIndex: 'icon',   dataType  : 'string', width : 30},
                            {header: 'Typ'   , dataIndex: 'type',   dataType  : 'string', width : 150},
                            {header: 'Titel' , dataIndex: 'title',  dataType  : 'string', width : 200}
                        ],
                        buttons : buttons,
                        perpage : 50,
                        page    : 1,
                        serverSort : false,
                        showHeader : true,
                        sortHeader : true,
                        alternaterows : true,
                        resizeColumns : true,
                        multipleSelection : false,
                        selectable        : true,

                        width  : $('site_search_tbl').offsetWidth - 25,
                        height : $('site_search_tbl').offsetHeight
                    });

                    //grid events
                    _pcsg.SiteSearch.List.addEvent('click', function(data)
                    {
                        var Grid   = data.target;
                        var _btns  = data.target.options.buttons;
                        var _site  = Grid.getDataByRow(data.row);
                        var submit = _pcsg.SiteSearch.Win.getAttribute('onsubmit');

                        if (typeof _Project !== 'undefined' &&
                             _Project.getAttribute('lang') == _site.lang &&
                             _Project.getAttribute('name') == _site.project &&
                              !submit &&
                             _site.id)
                        {
                            _btns.copy.setEnable();
                            _btns.linked.setEnable();
                        } else
                        {
                            _btns.copy.setDisable();
                            _btns.linked.setDisable();
                        }

                        if (_site.id && !submit)
                        {
                            _btns.goto.setEnable();
                        } else
                        {
                            _btns.goto.setDisable();
                        }

                        if (_site.id && submit)
                        {
                            _btns.pasteSite.setEnable();
                        } else
                        {
                            _btns.pasteSite.setDisable();
                        }
                    });

                    _pcsg.SiteSearch.List.addEvent('dblclick', function(data)
                    {
                        _pcsg.SiteSearch.pasteSite( data );
                    });

                    _pcsg.SiteSearch.showProjectLang( $('search_window_project') );

                    $('search_window_searchstring').focus();

                    Win.loaderStop();
                }, {
                    Win : Win
                });
            }
        });

        _pcsg.SiteSearch.Win.create();
    },

    search : function(searchstring, params, Func)
    {
        _pcsg.SiteSearch.Win.loaderStart();

        _Ajax.asyncPost('ajax_site_search_window', function(result, Ajax)
        {
            if (typeof Ajax.getAttribute('Func') == 'function') {
                Ajax.getAttribute('Func')( result );
            }

            _pcsg.SiteSearch.Win.loaderStop();
        }, {
            search  : searchstring,
            params  : JSON.encode( params ),
            Func    : Func,
            project : params.project
        });
    },

    copy : function(Btn)
    {
        var Grid  = _pcsg.SiteSearch.List;
        var row   = Grid.getSelectedIndices();
        var data  = Grid.getDataByRow(row);

        if (!data.id) {
            return;
        }

        _Project.Copy(
            new _ptools.SitemapItem({
                value : data.id,
                text  :data.name
            })
        );

        if (_pcsg.SiteSearch.Win)
        {
            _pcsg.SiteSearch.Win.close();
            _pcsg.SiteSearch.Win = null;
        }
    },

    linked : function(Btn)
    {
        var Grid  = _pcsg.SiteSearch.List;
        var row   = Grid.getSelectedIndices();
        var data  = Grid.getDataByRow(row);

        if (!data.id) {
            return;
        }

        _Project.Copy(
            new _ptools.SitemapItem({
                value : data.id,
                text  : data.name
            })
        );

        _Project.Linked();

        if (_pcsg.SiteSearch.Win)
        {
            _pcsg.SiteSearch.Win.close();
            _pcsg.SiteSearch.Win = null;
        }
    },

    showProjectLang : function(elm)
    {
        if (!$('search_window_'+ elm.value +'_langs'))
        {
            $('search_window_lang').innerHTML = '';
            return;
        }

        var langs = $('search_window_'+ elm.value +'_langs').innerHTML.toString().split(',');
        var str   = '';
        var oOption = document.createElement('option');

        $('search_window_lang').innerHTML = '';

        for (var i = 0, len = langs.length; i < len; i++)
        {
            if (langs[i].length == 2)
            {
                var o = oOption.cloneNode(true);

                o.value     = langs[i];
                o.innerHTML = langs[i];

                $('search_window_lang').appendChild( o );

                if (_pcsg.Project.getLang() == langs[i]){
                    $('search_window_lang').value = langs[i];
                }
            }
        }
    },

    gotoSite : function(Btn)
    {
        var Grid  = _pcsg.SiteSearch.List;
        var row   = Grid.getSelectedIndices();
        var data  = Grid.getDataByRow(row);

        if (!data.id) {
            return;
        }

        if (_pcsg.SiteSearch.Win)
        {
            _pcsg.SiteSearch.Win.close();
            _pcsg.SiteSearch.Win = null;
        }

        _pcsg.goToPage({
            project : data.project,
            lang    : data.lang,
            id      : data.id
        });
    },

    pasteSite : function()
    {
        var submit = _pcsg.SiteSearch.Win.getAttribute('onsubmit');

        if (!submit)
        {
            _pcsg.SiteSearch.Win.close();
            _pcsg.SiteSearch.Win = null;
            return false;
        }

        var Grid  = _pcsg.SiteSearch.List;
        var row   = Grid.getSelectedIndices();
        var data  = Grid.getDataByRow(row);

        if (!data.id) {
            return;
        }

        var r = {
            lang    : data.lang,
            project : data.project,
            id      : data.id,
            url     : data.url
        };

        _pcsg.SiteSearch.Win.close();
        _pcsg.SiteSearch.Win = null;

        return submit(r);
    }
};
