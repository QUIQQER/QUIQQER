
if (typeof _pcsg === 'undefined') {
    var _pcsg = {};
}

function site_delete()
{
    alert('@depricated site_delete()');
    var del = _Site.del();
}

/**
 * Einstellungen
 */

_pcsg.settings = {};
_pcsg.settings.global = function(_button)
{
    _pcsg.settings.global.editVhostList = function(row, data)
    {
        var List = _pcsg.settings.global.vhostList;

        if (typeof List === 'undefined') {
            return;
        }

        data.edit = {
            image   : URL_BIN_DIR +'16x16/edit.png',
            title   : 'Eintrag editieren',
            alt     : 'Eintrag editieren',
            onclick : function(_me)
            {
                var List = _pcsg.settings.global.vhostList;
                var Data = _me.getAttribute('data');

                _pcsg.settings.global.activeRow = Data.row;

                _pcsg.settings.global.vhostWindow(
                    Data.host,
                    Data.project,
                    Data.lang,
                    Data.template,
                    Data.error
                );
            }
        };

        data.del = {
            image   : URL_BIN_DIR +'16x16/cancel.png',
            title   : 'Eintrag löschen',
            alt     : 'Eintrag löschen',
            onclick : function(_me)
            {
                var List = _pcsg.settings.global.vhostList;
                var Data = _me.getAttribute('data');

                new _ptools.Confirm({
                    title       : 'Virtuellen Host löschen?',
                    textIcon    : URL_BIN_DIR +'32x32/cancel.png',
                    text        : 'Möchten Sie den virtuellen Host <u>'+ Data.host +'</u> wirklich löschen?',
                    information : 'Das Löschen des virtuellen Hosts wird erst nach dem Speichern übernommen',
                    width       : 400,
                    height      : 160,
                    row         : Data.row,
                    onsubmit    : function(Win)
                    {
                        List.deleteRow(
                            Win.getAttribute('row')
                        );
                    }
                }).create();
            }
        };

        List.setDataByRow(row, data);
    };

    _pcsg.settings.global.addToVhostList = function(host, project, lang, template, error)
    {
        var List = _pcsg.settings.global.vhostList;

        if (typeof List == 'undefined') {
            return;
        }

        var data = List.getData();

        data.push({
            host     : host,
            project  : project,
            lang     : lang,
            template : template,
            error    : error,
            edit     : {
                image   : URL_BIN_DIR +'16x16/edit.png',
                title   : 'Eintrag editieren',
                alt     : 'Eintrag editieren',
                onclick : function(_me)
                {
                    var List = _pcsg.settings.global.vhostList;
                    var Data = _me.getAttribute('data');

                    _pcsg.settings.global.activeRow = Data.row;

                    _pcsg.settings.global.vhostWindow(
                        Data.host,
                        Data.project,
                        Data.lang,
                        Data.template,
                        Data.error
                    );
                }
            },
            del      : {
                image   : URL_BIN_DIR +'16x16/cancel.png',
                title   : 'Eintrag löschen',
                alt     : 'Eintrag löschen',
                onclick : function(_me)
                {
                    var List = _pcsg.settings.global.vhostList;
                    var Data = _me.getAttribute('data');

                    new _ptools.Confirm({
                        title       : 'Virtuellen Host löschen?',
                        textIcon    : URL_BIN_DIR +'32x32/cancel.png',
                        text        : 'Möchten Sie den virtuellen Host <u>'+ Data.host +'</u> wirklich löschen?',
                        information : 'Das Löschen des virtuellen Hosts wird erst nach dem Speichern übernommen',
                        width       : 400,
                        height      : 160,
                        row         : Data.row,
                        onsubmit    : function(Win)
                        {
                            List.deleteRow(
                                Win.getAttribute('row')
                            );
                        }
                    }).create();
                }
            }
        });

        List.onLoadData({
            data : data
        });

        List.loadData();
    };

    _pcsg.settings.global.editMovedList = function(row, data)
    {
        var List = _pcsg.settings.global.movedList;

        if (typeof List == 'undefined') {
            return;
        }

        data.edit = {
            image   : URL_BIN_DIR +'16x16/edit.png',
            title   : 'Eintrag editieren',
            alt     : 'Eintrag editieren',
            onclick : function(_me)
            {
                var List = _pcsg.settings.global.vhostList;
                var Data = _me.getAttribute('data');

                _pcsg.settings.global.activeRow = Data.row;

                _pcsg.settings.global.movedWindow(
                    Data.request,
                    Data.url
                );
            }
        };

        data.del = {
            title   : 'Eintrag löschen',
            alt     : 'Eintrag löschen',
            image   : URL_BIN_DIR +'16x16/cancel.png',
            onclick : function(_me)
            {
                var List = _pcsg.settings.global.movedList;
                var Data = _me.getAttribute('data');

                new _ptools.Confirm({
                    title       : 'Weiterleitung löschen?',
                    textIcon    : URL_BIN_DIR +'32x32/cancel.png',
                    text        : 'Möchten Sie die Weiterleitung <u>'+ Data.request +'</u> wirklich löschen?',
                    information : 'Das Löschen der Weiterleitung wird erst nach dem Speichern übernommen',
                    width       : 400,
                    height      : 160,
                    row         : Data.row,
                    onsubmit    : function(Win)
                    {
                        List.deleteRow(
                            Win.getAttribute('row')
                        );
                    }
                }).create();
            }
        };

        List.setDataByRow(row, data);
    };

    _pcsg.settings.global.addToMovedtList = function(request, url)
    {
        var List = _pcsg.settings.global.movedList;

        if (typeof List == 'undefined') {
            return;
        }

        var data = List.getData();

        data.push({
            request : request,
            url     : url,
            edit    : {
                image   : URL_BIN_DIR +'16x16/edit.png',
                title   : 'Eintrag editieren',
                alt     : 'Eintrag editieren',
                onclick : function(_me)
                {
                    var List = _pcsg.settings.global.vhostList;
                    var Data = _me.getAttribute('data');

                    _pcsg.settings.global.activeRow = Data.row;

                    _pcsg.settings.global.movedWindow(
                        Data.request,
                        Data.url
                    );
                }
            },
            del     : {
                title   : 'Eintrag löschen',
                alt     : 'Eintrag löschen',
                image   : URL_BIN_DIR +'16x16/cancel.png',
                onclick : function(_me)
                {
                    var List = _pcsg.settings.global.movedList;
                    var Data = _me.getAttribute('data');

                    new _ptools.Confirm({
                        title       : 'Weiterleitung löschen?',
                        textIcon    : URL_BIN_DIR +'32x32/cancel.png',
                        text        : 'Möchten Sie die Weiterleitung <u>'+ Data.request +'</u> wirklich löschen?',
                        information : 'Das Löschen der Weiterleitung wird erst nach dem Speichern übernommen',
                        width       : 400,
                        height      : 160,
                        row         : Data.row,
                        onsubmit    : function(Win)
                        {
                            List.deleteRow(
                                Win.getAttribute('row')
                            );
                        }
                    }).create();
                }
            }
        });

        List.onLoadData({
            data : data
        });

        List.loadData();
    };

    _pcsg.settings.global.vhostWindow = function(host, project, lang, template, error)
    {
        var edit  = false;
        error = error || '';

        if (typeof host     != 'undefined' &&
            typeof project  != 'undefined' &&
            typeof lang     != 'undefined' &&
            typeof template != 'undefined')
        {
            edit = true;
        } else
        {
            host     = '';
            project  = '';
            lang     = '';
            template = '';
        }

        var html = '<form name="createvhost">' +
            '<table style="width: 300px">' +
                '<tr>' +
                    '<td>Host</td>' +
                    '<td><input type="text" name="host" class="w100" style="width: 200px;" value="'+ host +'" /></td>' +
                '</tr>' +
                '<tr>' +
                    '<td>Template</td>' +
                    '<td><input type="text" name="template" class="w100" style="width: 200px;" value="'+ template +'" /></td>' +
                '</tr>' +

                '<tr>' +
                    '<td colspan="2" style="padding-top: 30px;">' +
                        'Projektzuweisung' +
                        '<div id="vhostProject" style="float: left; margin-right: 5px; height: 50px;"></div>' +
                        '<div style="float: left; margin: 5px; width: 240px;">' +
                            '<span style="font-weight: normal; float: left; line-height: 18px; width: 55px">Project</span>' +
                            '<input type="text" name="project" class="w100" style="float: left; width: 170px" value="'+ project +'" />' +
                        '</div>' +
                        '<div style="float: left; margin: 5px; width: 240px;">' +
                            '<span style="font-weight: normal; float: left; line-height: 18px; width: 55px">Sprache</span>' +
                            '<input type="text" name="lang" class="w100" style="width: 40px; float: left;" value="'+ lang +'" />' +
                        '</div>' +

                    '</td>' +
                    '<td></td>' +
                '</tr>' +

                '<tr>' +
                    '<td colspan="2" style="padding-top: 20px;">' +
                        'Fehlerseite' +
                        '<div id="vhostErrorPage" style="float: left; margin: 5px"></div>' +
                        '<div id="vhostErrorPageEntry" style="font-weight: normal; float: left; margin: 5px; line-height: 27px;">'+ error +'</div>' +
                    '</td>' +
                '</tr>' +
            '</table>' +
        '</form>';

        var _win = new _ptools.Confirm({
            name     : 'vhosts',
            title    : 'Virtueller Host',
            edit     : edit,
            onsubmit : function(Win)
            {
                var form = document.forms.createvhost;
                var elms = form.elements;

                if (elms.host.value === '' ||
                    elms.project.value === '' ||
                    elms.lang.value === '' ||
                    elms.template.value === '')
                {
                    return false;
                }

                if (Win.getAttribute('edit'))
                {
                    _pcsg.settings.global.editVhostList(_pcsg.settings.global.activeRow, {
                        host     : elms.host.value,
                        project  : elms.project.value,
                        lang     : elms.lang.value,
                        template : elms.template.value,
                        error    : $('vhostErrorPageEntry').innerHTML
                    });

                    return;
                }

                _pcsg.settings.global.addToVhostList(
                    elms.host.value,
                    elms.project.value,
                    elms.lang.value,
                    elms.template.value,
                    $('vhostErrorPageEntry').innerHTML
                );
            },
            width       : 450,
            height      : 350,
            image       : URL_BIN_DIR +'16x16/vhosts.png',
            textIcon    : URL_BIN_DIR +'48x48/vhosts.png',
            text        : html
        });
        _win.create();

        $('vhostProject').appendChild(
            _pcsg.Controls.SitemapButton.get({
                showprojects : true,
                onsubmit     : function(params)
                {
                    var form = document.forms.createvhost;
                    var elms = form.elements;

                    elms.project.value = params.project;
                    elms.lang.value    = params.lang;
                }
            }).create()
        );

        $('vhostErrorPage').appendChild(
            _pcsg.Controls.SitemapButton.get({
                showprojects : true,
                onsubmit     : function(params)
                {
                    $('vhostErrorPageEntry').innerHTML = params.project +','+ params.lang +','+ params.id;
                }
            }).create()
        );
    };


    _pcsg.settings.global.movedWindow = function(requesthost, url)
    {
        var edit = false;

        if (typeof requesthost != 'undefined' &&
            typeof url != 'undefined')
        {
            edit = true;
        }

        requesthost = requesthost || '';
        url         = url || '';

        var html = '<form name="create301">'+
             '<table style="width: 280px">'+
                 '<tr>' +
                    '<td>Request Adresse</td>'+
                    '<td><input type="text" name="request" class="w100" value="'+ requesthost +'" /></td>'+
                '</tr>'+
                '<tr>'+
                    '<td>URL</td>'+
                    '<td><input type="text" name="url" class="w100" value="'+ url +'" /></td>'+
                '</tr>';

                if (edit === false)
                {
                    html = html +'<tr>'+
                        '<td colspan="2" style="font-weight: normal; padding-top: 10px">'+
                            '<input type="checkbox" id="www_host" style="float: left; margin-right: 5px;" />'+
                            'Request Adresse mit und ohne www anlegen'+
                        '</td>'+
                    '</tr>';
                }

            html = html + '</table></form>';

        new _ptools.Confirm({
            name     : 'create301',
            title    : 'Weiterleitung einrichten',
            edit     : edit,
            onsubmit : function(Win)
            {
                var edit = Win.getAttribute('edit');
                var form = document.forms.create301;
                var elms = form.elements;

                if (elms.request.value === '' ||
                    elms.url.value === '')
                {
                    return false;
                }

                var request = elms.request.value;
                var url     = elms.url.value;

                if (!url.match('http://')) {
                    url = 'http://'+ url;
                }

                if (edit)
                {
                    _pcsg.settings.global.editMovedList(_pcsg.settings.global.activeRow, {
                        request : request,
                        url     : url
                    });

                    return;
                }

                if ($('www_host').checked)
                {
                    request = request.replace('www.', '');

                    _pcsg.settings.global.addToMovedtList(
                        'www.'+ request,
                        url
                    );
                }

                _pcsg.settings.global.addToMovedtList(
                    request,
                    url
                );
            },
            width       : 450,
            height      : 200,
            image       : URL_BIN_DIR +'16x16/moved.png',
            textIcon    : URL_BIN_DIR +'48x48/moved.png',
            text        : html,
            information : 'Fügen Sie eine neue Weiterleitung für Ihre Auftritte hinzu'
        }).create();
    };


    this.config = _Ajax.syncPost('ajax_config_global');

    var _Win = new _ptools.Setting({
        name     : '_Win',
        image    : URL_BIN_DIR +'16x16/kdf.png',
        title    : 'Globale Einstellungen',
        onsubmit : function(_win)
        {
            if (_win._active.getAttribute('onunload')) {
                _win._active.getAttribute('onunload')();
            }

            _win.loaderStart();

            var _return = _Ajax.syncPost('ajax_config_global_save', {
                params: JSON.encode( _pcsg.settings.config )
            });

            _win.loaderStop();

            if (_return)
            {
                new _ptools.Info({
                    text : 'Konfiguration wurde erfolgreich gespeichert'
                }).create();
            }

            return _return;
        },
        width  : 750,
        height : 420
    });

    var _Global = new _ptools.Button({
        name   : '_Globals',
        onload : function (_me, _win)
        {
            _win.loaderStart();

            var html = _Ajax.syncPost('ajax_get_tpl', {
                tpl: 'setting_globals'
            });
            _win.setBody(html);

            var form = document.forms.global_settings;
            var elms = form.elements;

            elms.standard.value = _pcsg.settings.config.standard;

            // Fehlerseite
            if (typeof _pcsg.settings.config.vhosts.error_site != 'undefined')
            {
                var _404 = _pcsg.settings.config.vhosts.error_site;
                var v    = _404.project +','+ _404.lang +','+ _404.id;

                $('global_settings_error_site').innerHTML= v;
            }

            $('global_settings_error_site_button').appendChild(
                _pcsg.Controls.SitemapButton.get({
                    onsubmit : function(params)
                    {
                        var form = document.forms.global_settings;
                        var elms = form.elements;

                        var v = params.project +','+ params.lang +','+ params.id;
                        $('global_settings_error_site').innerHTML= v;

                        if (typeof _pcsg.settings.config.vhosts == 'undefined') {
                            _pcsg.settings.config.vhosts = {};
                        }

                        var _404 = {
                            project : params.project,
                            lang    : params.lang,
                            id      : params.id
                        };

                         _pcsg.settings.config.vhosts.error_site = _404;
                    },
                    showprojects : true
                }).create()
            );

            // VHosts
            $('vhosts').innerHTML = '';

            var _vhosts = _pcsg.settings.config.vhosts;

            _pcsg.settings.global.vhostList = new omniGrid('vhosts', {
                columnModel: [
                    {header: '',             dataIndex: 'del',       dataType : 'button', width : 30},
                    {header: '',             dataIndex: 'edit',      dataType : 'button', width : 30},
                    {header: 'Host',         dataIndex: 'host',      dataType : 'string', width : 120},
                    {header: 'Projekt',     dataIndex: 'project',   dataType : 'string', width : 120},
                    {header: 'Sprache',     dataIndex: 'lang',      dataType : 'string', width : 40},
                    {header: 'Template',    dataIndex: 'template',  dataType : 'string', width : 100},
                    {header: 'Fehlerseite', dataIndex: 'error',     dataType : 'string', width : 100}
                ],
                buttons : [{
                    text    : '<img src="'+ URL_BIN_DIR +'16x16/vhosts.png" style="float: left; margin-right: 10px;" /> Virtuellen Host hinzufügen',
                     onclick : function() {
                        _pcsg.settings.global.vhostWindow();
                    }
                }],
                pagination : false,
                //perpage    : perPage,
                //page       : 1,
                serverSort : false,
                showHeader : true,
                sortHeader : true,

                alternaterows : true,
                resizeColumns : true,

                multipleSelection : false,

                width  : 430,
                height : 160
            });

            var List     = _pcsg.settings.global.vhostList;
            var ListData = [];

            if (typeof _vhosts.length == 'undefined')
            {
                for (var _h in _vhosts)
                {
                    if (_h == 'error_site') {
                        continue;
                    }

                    if (_h == 'moved') {
                        continue;
                    }

                    _pcsg.settings.global.addToVhostList(
                        _h,
                        _vhosts[_h].project,
                        _vhosts[_h].lang,
                        _vhosts[_h].template,
                        _vhosts[_h].error
                    );
                }
            }
            // [end] vhost

            // 301
            $('moved').innerHTML = '';


            _pcsg.settings.global.movedList = new omniGrid('moved', {
                columnModel: [
                    {header: '',         dataIndex: 'del',     dataType : 'button', width : 30},
                    {header: '',         dataIndex: 'edit',    dataType : 'button', width : 30},
                    {header: 'Request', dataIndex: 'request', dataType : 'string', width : 180},
                    {header: 'URL',     dataIndex: 'url',     dataType : 'string', width : 180}
                ],
                buttons : [{
                    text    : '<img src="'+ URL_BIN_DIR +'16x16/moved.png" style="float: left; margin-right: 10px;" /> Weiterleitung hinzufügen',
                     onclick : function()
                     {
                         _pcsg.settings.global.movedWindow();
                    }
                }],
                pagination : false,
                //perpage    : perPage,
                //page       : 1,
                serverSort : false,
                showHeader : true,
                sortHeader : true,

                alternaterows : true,
                resizeColumns : true,

                multipleSelection : false,

                width  : 430,
                height : 200
            });

            var _moved = _vhosts.moved;

            if (_moved)
            {
                for (var _m in _moved)
                {
                    _pcsg.settings.global.addToMovedtList(
                        _m,
                        _moved[_m]
                    );
                }
            }

            _win.loaderStop();
        },
        onunload : function (_me, _win)
        {
            var _vhosts = {};

            var List      = _pcsg.settings.global.vhostList;
            var MovedList = _pcsg.settings.global.movedList;

            var form = document.forms.global_settings;
            var elms = form.elements;

            _pcsg.settings.config.standard = elms.standard.value;

            var _tmp = _pcsg.settings.config.vhosts;

            if (_tmp.error_site) {
                _vhosts.error_site = _tmp.error_site;
            }

            if (List)
            {
                var Data = List.getData();

                for (var i = 0, len = Data.length; i < len; i++)
                {
                    var Entry = Data[i];

                    _vhosts[ Entry.host ] = {
                        project  : Entry.project,
                        lang     : Entry.lang,
                        template : Entry.template,
                        error    : Entry.error
                    };
                }
            }

            if (MovedList)
            {
                var Data   = MovedList.getData();
                var _moved = {};

                for (var i = 0, len = Data.length; i < len; i++) {
                    _moved[ Data[i].request ] = Data[i].url;
                }

                _vhosts.moved = _moved;
            }

            _pcsg.settings.config.vhosts = _vhosts;
        },
        title : 'Allgemein',
        text  : 'Allgemein',
        image : URL_BIN_DIR +'22x22/configure.png',
        body  : '&nbsp;'
    });
    _Win.appendChild( _Global );

    var _Auth = new _ptools.Button({
        name   : '_Auth',
        onload : function (_me, _win)
        {
            _win.loaderStart();

            var html = _Ajax.syncPost('ajax_get_tpl', {
                tpl : 'setting_auth'
            });

            _win.setBody(html);

            if (!_pcsg.settings.config.auth) {
                _pcsg.settings.config.auth = {};
            }

            if (!_pcsg.settings.config.session) {
                _pcsg.settings.config.session = {};
            }

            var auth    = _pcsg.settings.config.auth;
            var session = _pcsg.settings.config.session;
            var oForm   = document.forms.global_auth;
            var oTypes  = oForm.elements.auth_type;

            switch (auth.type)
            {
                case 'AD':
                    oTypes.value = 'AD';
                break;

                default:
                    oTypes.value = '';
                break;
            }

            _pcsg.settings.authtypes();

            // Rest laden
            if (auth.type == 'AD')
            {
                if (auth.server) {
                    oForm.elements.server.value = auth.server;
                }

                if (auth.base_dn) {
                    oForm.elements.base_dn.value = auth.base_dn;
                }

                if (auth.domain) {
                    oForm.elements.domain.value = auth.domain;
                }
            }

            // Sitzung
            oForm.elements.max_life_time.value = session.max_life_time;

            if (_pcsg.settings.config.globals.emaillogin) {
                oForm.elements.emaillogin.checked = true;
            }


            _win.loaderStop();
        },
        onunload : function (_me, _win)
        {
            var auth    = _pcsg.settings.config.auth;
            var session = _pcsg.settings.config.session;

            var oForm  = document.forms.global_auth;
            var oTypes = oForm.elements.auth_type;

            switch (oTypes.value)
            {
                case 'AD':
                    auth.type    = 'AD';
                    auth.server  = oForm.elements.server.value;
                    auth.base_dn = oForm.elements.base_dn.value;
                    auth.domain  = oForm.elements.domain.value;
                break;

                default:
                    auth.type = 'standard';
                break;
            }

            // Sitzung
            session.max_life_time = oForm.elements.max_life_time.value;

            _pcsg.settings.config.session = session;
            _pcsg.settings.config.auth    = auth;


            if (!_pcsg.settings.config.globals) {
                _pcsg.settings.config.globals = {};
            }

            _pcsg.settings.config.globals.emaillogin = oForm.elements.emaillogin.checked ? true : false;
        },
        title : 'Authentifizierung',
        text  : 'Authentifizierung',
        image : URL_BIN_DIR +'22x22/auth.png',
        body  : '&nbsp;'
    });
    _Win.appendChild( _Auth );

    var _DB = new _ptools.Button({
        name   : '_DB',
        onload : function (_me, _win)
        {
            _win.loaderStart();

            var html = _Ajax.syncPost('ajax_get_tpl', {
                tpl : 'setting_db'
            });
            _win.setBody(html);

            var form = document.forms.global_settings;
            var elms = form.elements;
            var db   = _pcsg.settings.config.db;

            elms.host.value = db.host;
            elms.db.value   = db.database;
            elms.user.value = db.user;

            _win.loaderStop();
        },
        onunload : function (_me, _win)
        {
            var form = document.forms.global_settings;
            var elms = form.elements;
            var db   = _pcsg.settings.config.db;

            db.host     = elms.host.value;
            db.database = elms.db.value;
            db.user     = elms.user.value;
            db.password = elms.pw.value;

            _pcsg.settings.config.db = db;
        },
        title : 'Datenbank',
        text  : 'Datenbank',
        image : URL_BIN_DIR +'22x22/database.png',
        body  : '&nbsp;'
    });
    _Win.appendChild( _DB );

    var _Mail = new _ptools.Button({
        name   : '_Mail',
        onload : function (_me, _win)
        {
            _win.loaderStart();

            var html = _Ajax.syncPost('ajax_get_tpl', {
                tpl : 'setting_mail'
            });
            _win.setBody(html);

            var form = document.forms['global_settings'];
            var elms = form.elements;
            var mail = _pcsg.settings.config.mail;

            elms['mailfrom'].value      = mail.MAILFrom;
            elms['mailfromtext'].value  = mail.MAILFromText;
            elms['mailfromreply'].value = mail.MAILReplyTo;
            elms['adminmail'].value     = mail.admin_mail;
            elms['bccToAdmin'].checked  = mail.bccToAdmin;
            elms['smtpserver'].value    = mail.SMTPServer;
            elms['smtpuser'].value      = mail.SMTPUser;

            _win.loaderStop();
        },
        onunload : function (_me, _win)
        {
            var form = document.forms['global_settings'];
            var elms = form.elements;
            var mail = _pcsg.settings.config.mail;

            mail.MAILFrom     = elms['mailfrom'].value;
            mail.MAILFromText = elms['mailfromtext'].value;
            mail.MAILReplyTo  = elms['mailfromreply'].value;
            mail.admin_mail   = elms['adminmail'].value;
            mail.bccToAdmin   = elms['bccToAdmin'].checked;
            mail.SMTPServer   = elms['smtpserver'].value;
            mail.SMTPUser     = elms['smtpuser'].value;
            mail.SMTPPass     = elms['smtppass'].value;

            _pcsg.settings.config.mail = mail;
        },
        title : 'E-Mail',
        text  : 'E-Mail',
        image : URL_BIN_DIR +'22x22/mail.png',
        body  : '&nbsp;'
    });
    _Win.appendChild( _Mail );

    var _Mail = new _ptools.Button({
        name   : '_Mail',
        onload : function (_me, _win)
        {
            _win.loaderStart();

            var html = _Ajax.syncPost('ajax_get_tpl', {
                tpl : 'setting_error'
            });
            _win.setBody( html );

            var form  = document.forms['global_settings'];
            var elms  = form.elements;
            var error = _pcsg.settings.config.error;

            elms['mail'].value   = error.mail;
            elms['send'].checked = parseInt(error.send);

            elms['mysql_ajax_errors_frontend'].checked = parseInt(error.mysql_ajax_errors_frontend);
            elms['mysql_ajax_errors_backend'].checked  = parseInt(error.mysql_ajax_errors_backend);

            _win.loaderStop();
        },
        onunload : function (_me, _win)
        {
            var form  = document.forms['global_settings'];
            var elms  = form.elements;
            var error = _pcsg.settings.config.error;

            error.mail = elms['mail'].value;
            error.send = elms['send'].checked == true ? 1 : 0;

            error.mysql_ajax_errors_frontend = elms['mysql_ajax_errors_frontend'].checked == true ? 1 : 0;
            error.mysql_ajax_errors_backend  = elms['mysql_ajax_errors_backend'].checked == true ? 1 : 0;

            _pcsg.settings.config.error = error;
        },
        title : 'Fehlerbehandlung',
        text  : 'Fehlerbehandlung',
        image : URL_BIN_DIR +'22x22/error.png',
        body  : '&nbsp;'
    });
    _Win.appendChild( _Mail );

    var _Smarty = new _ptools.Button({
        name   : '_Smarty',
        onload : function (_me, _win)
        {
            _win.loaderStart();

            var html = _Ajax.syncPost('ajax_get_tpl', {
                tpl : 'setting_smarty'
            });
            _win.setBody(html);

            var form   = document.forms['global_settings'];
            var elms   = form.elements;
            var smarty = _pcsg.settings.config.smarty;

            if (typeof smarty != 'undefined' &&
                typeof smarty.compile_check != 'undefined')
            {
                elms['compile_check'].checked = parseInt(smarty.compile_check);
            }

            _win.loaderStop();
        },
        onunload : function (_me, _win)
        {
            var form = document.forms['global_settings'];
            var elms = form.elements;

            if (typeof _pcsg.settings.config.smarty == 'undefined') {
                _pcsg.settings.config.smarty = {};
            }

            var smarty = _pcsg.settings.config.smarty;

            smarty.compile_check = elms['compile_check'].checked == true ? 1 : 0;

            _pcsg.settings.config.smarty = smarty;
        },
        title : 'Smarty',
        image : URL_BIN_DIR +'images/smarty_icon.gif',
        body  : '&nbsp;'
    });
    _Win.appendChild( _Smarty );

    _Win.appendChild(
        new _ptools.Button({
            name   : '_System',
            onload : function (Btn, Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_get_tpl', function(result, Ajax)
                {
                    Ajax.getAttribute('Win').setBody( result );

                    var form = document.forms['global_settings'];
                    var elms = form.elements;

                    var globals = _pcsg.settings.config.globals;

                    elms['host'].value       = globals.host;
                    elms['httpshost'].value  = globals.httpshost;
                    elms['admingroup'].value = globals.root;

                    elms['debugmode'].checked       = parseInt(globals.debug_mode);
                    elms['emailprotection'].checked = parseInt(globals.mailprotection);
                    elms['cache'].checked           = parseInt(globals.cache);
                    elms['memory_limit'].value      = globals.memory_limit ? globals.memory_limit : '';

                    Ajax.getAttribute('Win').loaderStop();
                }, {
                    tpl : 'setting_system',
                    Win : Win
                });
            },
            onunload : function (_me, _win)
            {
                var globals = _pcsg.settings.config.globals;
                var form    = document.forms['global_settings'];
                var elms    = form.elements;

                globals.host      = elms['host'].value;
                globals.httpshost = elms['httpshost'].value;
                globals.root      = elms['admingroup'].value;

                globals.debug_mode     = elms['debugmode'].checked == true ? 1 : 0;
                globals.mailprotection = elms['emailprotection'].checked == true ? 1 : 0;
                globals.cache          = elms['cache'].checked == true ? 1 : 0;
                globals.memory_limit   = elms['memory_limit'].value;

                _pcsg.settings.config.globals = globals;
            },
            title : 'System',
            text  : 'System',
            image : URL_BIN_DIR +'22x22/system.png',
            body  : '&nbsp;'
        })
    );


    _Win.create();

    this._Win = _Win;
};

_pcsg.settings.maintenance =
{
    open : function()
    {
        new _ptools.SubmitWindow({
            name   : 'maintenance',
            image  : URL_BIN_DIR +'16x16/configure.png',
            title  : 'Wartungsarbeiten',
            height : 150,
            width  : 380,
            onopen : function(Win)
            {
                Win.loaderStart();

                Win.setBody(
                    '<div style="margin: 20px;">' +
                        '<input type="checkbox" id="maintenance" style="margin-right: 10px; float: left;" />' +
                        '<span style="line-height: 14px">Wartungsarbeiten aktivieren</span>' +
                    '</div>'
                );

                _Ajax.asyncPost('ajax_get_maintenance_status', function(result, Ajax)
                {
                    if (result) {
                        $('maintenance').checked = true;
                    }

                    Ajax.getAttribute('Win').loaderStop();
                }, {
                    Win : Win
                });

            },
            onsubmit : function(Win)
            {
                _Ajax.asyncPost('ajax_set_maintenance_status', function(result, Ajax)
                {
                    Ajax.getAttribute('Win').close();
                }, {
                    status : $('maintenance').checked ? 1 : 0,
                    Win    : Win
                });

                return false;
            }
        }).create();
    }
};


_pcsg.settings.authtypes = function()
{
    var oForm  = document.forms['global_auth'];
    var oTypes = oForm.elements['auth_type'];

    switch (oTypes.value)
    {
        default:
            $('auth_settings').style.display    = 'none';
            $('auth_settings_params').innerHTML = '';
            $('auth_explain').innerHTML         = 'Die Authentifizierung von Benutzern erfolgt &uuml;ber das P.MS selbst.';
        break;

        case 'AD': // Active Directory
            $('auth_settings').style.display = '';
            $('auth_explain').innerHTML      = 'Die Authentifizierung von Benutzern erfolgt &uuml;ber einen externen Active Directory Server.';

            $('auth_settings_params').innerHTML = '' +
                '<table class="setting" style="width: 420px; margin: 0 auto;">' +
                '<tr>' +
                    '<td style="width: 100px">DC</td>' +
                    '<td>' +
                        '<input type="text" name="server" class="w100" style="width: 100%" />' +
                        '<span class="description" style="clear: both; float: left;">Beispiel: 192.168.1.1</span>' +
                    '<td>' +
                '</tr>' +
                '<tr>' +
                    '<td>baseDN</td>' +
                    '<td>' +
                        '<input type="text" name="base_dn" style="width: 100%" />' +
                        '<span class="description" style="clear: both; float: left;">Beispiel: DC=SBS2003,DC=local</span>' +
                    '<td>' +
                '</tr>' +
                '<tr>' +
                    '<td>Domain</td>' +
                    '<td>' +
                        '<input type="text" name="domain" style="width: 100%" />' +
                        '<span class="description" style="clear: both; float: left;">Beispiel: SBS2003.local</span>' +
                    '<td>' +
                '</tr>' +
                '</table>';

        break;
    }
};

_pcsg.settings.project = function(_button)
{
    _pcsg.settings._project = _button.getAttribute('text');

    this.config = _Ajax.syncPost('ajax_config_projects', {
        project : _pcsg.settings._project
    });

    var _Win = new _ptools.Setting({
        name     : '_Win',
        image    : URL_BIN_DIR +'16x16/kdf.png',
        title    : _button.getAttribute('text') +' Einstellungen',
        onsubmit : function(_win)
        {
            if (_win._active.getAttribute('onunload')) {
                _win._active.getAttribute('onunload')();
            }

            _win.loaderStart();

            var _return = _Ajax.syncPost('ajax_config_projects_save', {
                project : _win.getAttribute('value'),
                params  : JSON.encode(_pcsg.settings.config)
            });

            _win.loaderStop();

            return _return;
        },
        value : _button.getAttribute('text')
    });

    _Win.appendChild(
        new _ptools.Button({
            name   : '_Settings',
            onload : function (Btn, Win)
            {
                Win.loaderStart();

                var html = _Ajax.syncPost('ajax_get_tpl', {
                    tpl : 'settings_project'
                });

                Win.setBody(html);

                var form   = document.forms['settings_project'];
                var elms   = form.elements;
                var config = _pcsg.settings.config;

                elms['default_lang'].value = config.default_lang;
                elms['langs'].value        = config.langs;
                elms['admin_mail'].value   = config.admin_mail;

                for (var i = 0, len = config.templates.length; i < len; i++)
                {
                    var opt = document.createElement("OPTION");
                    opt.text  = config.templates[i];
                    opt.value = config.templates[i];

                    elms['template'].options.add(opt);

                    if( config.templates[i] == config.template){
                        elms['template'].selectedIndex = i+1;
                    }
                }

                if (config.sheets) {
                    elms['sheets'].value = config.sheets;
                }

                if (config.sitemapDisplayTyp) {
                    elms['sitemapDisplayTyp'].value = config.sitemapDisplayTyp;
                }

                if (config.archive) {
                    elms['archive'].value = config.archive;
                }

                $('settings_project_default_lang').innerHTML = '<img src="'+ URL_BIN_DIR +'16x16/flags/'+ config.default_lang +'.png" />';

                var _langs = config.langs.split(',');
                var _limgs = '';

                for (var i = 0, len = _langs.length; i < len; i++) {
                    _limgs = _limgs + ' <img src="'+ URL_BIN_DIR +'16x16/flags/'+ _langs[i] +'.png" />';
                }

                $('settings_project_langs').innerHTML = _limgs;

                Win.loaderStop();
            },
            onunload : function (Btn, Win)
            {
                var form   = document.forms['settings_project'];
                var elms   = form.elements;
                var config = _pcsg.settings.config;

                config.default_lang      = elms['default_lang'].value;
                config.langs             = elms['langs'].value;
                config.admin_mail        = elms['admin_mail'].value;
                config.template          = elms['template'].value;
                config.sheets            = elms['sheets'].value;
                config.sitemapDisplayTyp = elms['sitemapDisplayTyp'].value;

                if (elms['archive'].value) {
                    config.archive = elms['archive'].value;
                }

                _pcsg.settings.config = config;
            },
            title : 'Einstellungen',
            text  : 'Einstellungen',
            image : URL_BIN_DIR +'22x22/configure.png',
            body  : '&nbsp;'
        })
    );


    _Win.appendChild(
        new _ptools.Button({
            name   : 'MetaSettings',
            title  : 'Meta Angaben',
            text   : 'Meta Angaben',
            image  : URL_BIN_DIR +'22x22/meta.png',
            body   : '&nbsp;',
            onload : function (Btn, Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_get_tpl', function(result, Ajax)
                {
                    var Win = Ajax.getAttribute('Win');

                    Win.setBody( result );

                    var form   = document.forms['settings_project'];
                    var elms   = form.elements;
                    var config = _pcsg.settings.config;

                    elms['author'].value      = config.author    ? config.author    : '';
                    elms['publisher'].value   = config.publisher ? config.publisher : '';
                    elms['copyright'].value   = config.copyright ? config.copyright : '';
                    elms['robots'].value      = config.robots    ? config.robots    : '';
                    elms['keywords'].value    = config.keywords  ? config.keywords  : '';
                    elms['description'].value = config.description ? config.description : '';

                    Win.loaderStop();
                }, {
                    tpl : 'settings_project_meta',
                    Win : Win
                });
            },
            onunload : function (Btn, Win)
            {
                var form   = document.forms['settings_project'];
                var elms   = form.elements;
                var config = _pcsg.settings.config;

                config.author      = elms['author'].value;
                config.publisher   = elms['publisher'].value;
                config.copyright   = elms['copyright'].value;
                config.robots      = elms['robots'].value;
                config.keywords    = elms['keywords'].value;
                config.description = elms['description'].value;

                _pcsg.settings.config = config;
            }
        })
    );


    _Win.appendChild(
        new _ptools.Button({
            name   : '_Rights',
            onload : function (_me, _win)
            {
                if (!_pcsg.settings.config.rights) {
                    _pcsg.settings.config.rights = '';
                }

                _win.loaderStart();

                _Ajax.asyncPost('ajax_get_tpl', function(result)
                {
                    var _win = _ptools._Windows['_Win'];

                    _win.setBody( result );

                    var _Btn_Right = _pcsg.Controls.GroupButton.getObj({
                        onsubmit : function(_me)
                        {
                            var _Me   = _me[0];
                            var _Win  = _ptools._Windows['_Win'];
                            var _Grid = _Win.getAttribute('_Grid', _Grid);

                            var _del = new _ptools.Button({
                                name    : '_delete',
                                onclick : function(_me) {
                                    _me.getAttribute('Grid').removeChild( _me.getAttribute('Item') );
                                },
                                image : URL_BIN_DIR +'16x16/cancel.png',
                                Grid  : _Grid
                            });

                            var _Item = new _ptools.GridItem({
                                values : [_Me.getAttribute('text'), _del],
                                name   : _Me.getAttribute('value')
                            });

                            _del.setAttribute('Item', _Item);
                            _Grid.addItem( _Item );
                        },
                        onclick : function(_me) {
                            //_base.rights.active = _me;
                        }
                    });

                    _Btn_Right.setAttribute('name', 'projectright');
                    _Btn_Right.setAttribute('value', 'projectright');

                    $('btn_right').appendChild( _Btn_Right.create() );

                    var _Grid = new _ptools.Grid({
                        name   : '_grid',
                        cols   : ['group','delete'],
                        titles : [
                            ['Gruppen',''],
                            [300,30]
                        ]
                    });
                    _win.setAttribute('_Grid', _Grid);
                    $('right_table').appendChild( _Grid.create() );

                    var rights = _pcsg.settings.config.rights;
                    rights     = rights.split(',');

                    for (var i = 0, len = rights.length; i < len; i++)
                    {
                        if (!rights[i] || rights[i] == '') {
                            continue;
                        }

                        var _del = new _ptools.Button({
                            name    : '_delete',
                            onclick : function(_me) {
                                _me.getAttribute('Grid').removeChild( _me.getAttribute('Item') );
                            },
                            image   : URL_BIN_DIR +'16x16/cancel.png',
                            Grid    : _Grid
                        });

                        var _group = _Ajax.syncPost('ajax_rights_group_get_group',{
                            id : rights[i]
                        });

                        var _Item = new _ptools.GridItem({
                            values : [_group.name, _del],
                            name   : rights[i]
                        });

                        _del.setAttribute('Item', _Item);
                        _Grid.addItem(_Item);
                    }

                    _win.loaderStop();
                }, {
                    tpl : 'settings_project_rights'
                });
            },
            onunload : function (_me, _win)
            {
                var _Win  = _ptools._Windows['_Win'];
                var _Grid = _Win.getAttribute('_Grid', _Grid);

                var Children = _Grid.getChildren();
                var str      = '';

                for (var i = 0, len = Children.length; i < len; i++) {
                    str = str + Children[i].settings.name + ',';
                }

                _pcsg.settings.config.rights = str;
            },
            title : 'Rechte',
            text  : 'Rechte',
            image : URL_BIN_DIR +'22x22/group.png',
            body  : '&nbsp;'
        })
    );


    _Win.appendChild(
        new _ptools.Button({
            name   : '_Backup',
            onload : function (_me, _win)
            {
                _win.loaderStart();

                var html = _Ajax.syncPost('ajax_get_tpl', {
                    tpl : 'settings_project_backup'
                });
                _win.setBody(html);

                var _CreateButton = new _ptools.Button({
                    name    : '_CreateButton',
                    text    : 'Backup erstellen',
                    onclick : function(_me)
                    {
                        var _createBackup = new _ptools.SubmitWindow({
                            name     : '_createBackup',
                            onsubmit : function(_me)
                            {
                                var elms = document.forms['backupproject'].elements;

                                _Ajax.asyncPost('ajax_project_createbackup', function() {}, {
                                    name      : _me.getAttribute('project'),
                                    config    : elms['config'].checked ? 1 : 0,
                                    project   : elms['project'].checked ? 1 : 0,
                                    media     : elms['media'].checked ? 1 : 0,
                                    templates : elms['templates'].checked ? 1 : 0
                                });
                            },
                            project : _me.getAttribute('project'),
                            title   : 'Backup erstellen von '+ _me.getAttribute('project'),
                            width   : 400,
                            height  : 250,
                            image   : URL_BIN_DIR +'16x16/backup.png'
                        });

                        _createBackup.create();

                        var html = '<div style="margin: 10px">' +
                            '<p>Was m&ouml;chten Sie sichern?</p>' +
                            '<div style="margin-top: 10px"><img src="'+ URL_BIN_DIR +'48x48/backup.png" style="float: left; margin: 15px 30px" />' +
                            '<div style="margin: 10px; float: left;"><form name="backupproject">' +

                            '<p style="margin: 5px 0"><input type="checkbox" name="config" checked="checked" /> Konfiguration</p>' +
                            '<p style="margin: 5px 0"><input type="checkbox" name="project" checked="checked" /> Projekt</p>' +
                            '<p style="margin: 5px 0"><input type="checkbox" name="media" checked="checked" /> Media-Center</p>' +
                            '<p style="margin: 5px 0"><input type="checkbox" name="templates" checked="checked" /> Templates</p>' +

                            '</form></div></div>' +
                            '</div>';

                        _createBackup.setBody( html );

                    },
                    project : _me.getAttribute('project')
                });

                $('backupbuttons').appendChild(
                    _CreateButton.create()
                );

                $('backups').innerHTML = '<img src="'+ URL_BIN_DIR +'images/loader.gif" /> Loading';

                // Backups auflisten
                _Ajax.asyncPost('ajax_project_getbackups', function(files)
                {
                    var _BackupGrid = new _ptools.Grid({
                        cols   : ['date', 'download', 'delete'],
                        titles : [['Datum'],[320,25,25]]
                    });

                    if (files &&
                        (typeof files.length == 'undefined' || typeof files.length != 'undefined' && files.length > 0))
                    {
                        for (i in files)
                        {
                            var _archive = files[i];
                            var _v       = [];

                            if (_archive['running'] == 1)
                            {
                                _v = [
                                    _archive['date'],
                                    new _ptools.Image({
                                        name  : _archive['date'],
                                        image : URL_BIN_DIR +'images/loader.gif'
                                    })
                                ];
                            } else
                            {
                                _v = [
                                    _archive['date'] +' ('+ _archive['size'] +')',
                                    new _ptools.Button({
                                        name    : 'download',
                                         image   : URL_BIN_DIR +'16x16/down.png',
                                         title   : "Backup '"+ _archive['date'] +"' runterladen",
                                         alt     : "Backup vom "+ _archive['date'] +" runterladen",
                                         onclick : function(_me) {

                                             if (!$('iframe_download'))
                                             {
                                                 var oIFrame = document.createElement('iframe');
                                                 oIFrame.id  = 'iframe_download';

                                                 var style = oIFrame.style;
                                                 style.position = 'absolute';
                                                 style.width    = '1px';
                                                 style.height   = '1px';
                                                 style.top      = '-1000px';
                                                 style.left     = '-1000px';

                                                 document.body.appendChild(oIFrame);
                                             }

                                             var p = _pcsg.settings._project;
                                             $('iframe_download').src = 'bin/backupdownload.php?project='+ p +'&file='+ _me.getAttribute('parent').getAttribute('name');
                                         }
                                    }),
                                    new _ptools.Button({
                                         name    : 'delete',
                                         image   : URL_BIN_DIR +'16x16/trashcan_empty.png',
                                         title   : "Backup '"+ _archive['date'] +"' löschen",
                                         alt     : "Backup vom "+ _archive['date'] +"  löschen",
                                         onclick : function(_me)
                                         {
                                             var p = _pcsg.settings._project;

                                             _Ajax.syncPost('ajax_project_deletebackup', {
                                                 project : p,
                                                 archive : _me.getAttribute('parent').getAttribute('name')
                                             });
                                         }
                                    })
                                ];
                            }

                            var _Itm = new _ptools.GridItem({
                                values : _v,
                                name   : _archive['folder']
                            });

                            _BackupGrid.addItem( _Itm );
                        }

                    } else
                    {
                        var _Itm = new _ptools.GridItem({
                            values : [
                                 'Kein Backup vorhanden'
                            ],
                            name : 'noentry'
                        });
                        _BackupGrid.addItem( _Itm );
                    }

                    $('backups').innerHTML = '';
                    $('backups').appendChild( _BackupGrid.create() );

                }, {
                    name : _me.getAttribute('project')
                });

                _win.loaderStop();
            },
            onunload : function (_me, _win) {

            },
            title   : 'Backup',
            text    : 'Backup',
            image   : URL_BIN_DIR +'22x22/backup.png',
            body    : '&nbsp;',
            project : _button.getAttribute('text')
        })
    );


    _Win.appendChild(
        new _ptools.Button({
            name   : 'Watermark',
            onload : function(Btn, Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_get_tpl', function(result, Ajax)
                {
                    Ajax.getAttribute('Win').setBody( result );

                    // Einstellungen laden
                    var needle;
                    var form   = document.forms['settings_project'];
                    var elms   = form.elements;
                    var config = _pcsg.settings.config;


                    $('watermark-media').appendChild(
                        _pcsg.Controls.MediaButton.get(function(result)
                        {
                            form.elements['watermark_image'].value = result.url;
                        })
                    );

                    var needles = [
                        'watermark_image', 'watermark_position',
                        'watermark_percent'
                    ];

                    for (var i = 0, len = needles.length; i < len; i++)
                    {
                        needle = needles[i];

                        if (config[needle])
                        {
                            elms[needle].value = config[needle];
                            continue;
                        }

                        elms[needle].value = '';
                    }

                    Ajax.getAttribute('Win').loaderStop();
                }, {
                    tpl : 'settings_project_watermark',
                    Win : Win
                });
            },
            onunload : function(Btn, Win)
            {
                var form   = document.forms['settings_project'];
                var elms   = form.elements;
                var config = _pcsg.settings.config;

                config.watermark_image    = elms['watermark_image'].value;
                config.watermark_position = elms['watermark_position'].value;

                config.watermark_percent  = elms['watermark_percent'].value;

                _pcsg.settings.config = config;
            },
            title   : 'Wasserzeichen',
            text    : 'Wasserzeichen',
            image   : URL_BIN_DIR +'22x22/watermark.png',
            body    : '&nbsp;',
            project : _button.getAttribute('text')
        })
    );

    _Win.create();

    this._Win = _Win;
};


var _menu_hilfe={};

_menu_hilfe.about = function()
{
    var Win = new _ptools.Window({
        title  : 'Über',
        name   : '_AboutWindow',
        body   : '<div align="center" style="padding-top: 10px"><p>P.MS</p><p>Version '+ _VERSION_ +'</p><p><br />Copyright PCSG</p><p>Author Henning Leutz &amp; Moritz Scholz</p></div>',
        height : 170,
        width  : 380,
        image  : URL_BIN_DIR +'16x16/kdf.png'
    }).create();

    return false;
};

_menu_hilfe.support = function()
{
    this._SupportWindow = new _ptools.Window({
        title  : 'Support Anfrage',
        name   : '_SupportWindow',
        height : 450,
        width  : 550,
        image  : URL_BIN_DIR+'16x16/support_mail.png'
    });

    this._SupportWindow.create();
    this._SupportWindow.setBody(
        _Ajax.sync('ajax_get_tpl', {
            tpl : 'support_mail'
        })
    );

    var f = document.forms['pcsg_support'];

    if (f)
    {
        f.elements['url'].value     = document.location;
        f.elements['browser'].value = navigator.userAgent;

        var _send = new _ptools.Button({
            name    : '_send',
            text    : 'senden',
            onclick : '_menu_hilfe.support_send'
        });
        $('pcsg_support_send').appendChild( _send.create() );
    }
};

_menu_hilfe.support_send = function()
{
    var f = document.forms['pcsg_support'];

    if (f)
    {
        var msg = _Ajax.sync('ajax_send_support_mail', {
            title   : f.elements['title'].value,
            text    : f.elements['text'].value,
            browser : f.elements['browser'].value,
            url     : f.elements['url'].value,
            mail    : f.elements['mail'].value
        });

        if (msg == 'true')
        {
            new _ptools.Info({
                text : 'Mail wurde erfolgreich an PCSG versendet. Vielen Dank'
            }).create();

            this._SupportWindow.close();

        } else if (msg == 'false')
        {
            new _ptools.Info({
                text : 'Fehler beim versenden '+ msg
            }).create();
        } else
        {
            new _ptools.Info({
                text : 'Fehler beim versenden '+ msg
            }).create();
        }
    }
};

_menu_hilfe.system_update = function()
{
    this._UpdateWindow = new _ptools.Window({
        title  : 'System Updates',
        name   : '_UpdateWindow',
        height : 450,
        width  : 550,
        image  : URL_BIN_DIR+'16x16/system_update.png'
    });

    this._UpdateWindow.create();
    this._UpdateWindow.setBody( _Ajax.sync('ajax_get_tpl', {tpl: 'system_update'}) );
};

_menu_hilfe.tools = {};

// Help.Tools.Firefox.UserJs
_menu_hilfe.tools.FirefoxUserJs =
{
    open : function()
    {
        new _ptools.Window({
            title  : 'Firefox User.js Generator',
            name   : 'FirefoxUserJs',
            height : 520,
            width  : 550,
            image  : URL_BIN_DIR +'16x16/browser/firefox.png',
            onopen : function(Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_get_tpl', function(result, Ajax)
                {
                    Ajax.getAttribute('Win').setBody( result );

                    var f = document.forms['pcsg_FirefoxUserJs'];

                    if (!f)
                    {
                        Win.loaderStop();
                        return;
                    }


                    f.elements['url'].value     = document.location;
                    f.elements['browser'].value = navigator.userAgent;

                    var _send = new _ptools.Button({
                        name    : '_sendButton',
                        text    : 'User.js erstellen',
                        width   : 150,
                        onclick : function() {
                            document.forms['pcsg_FirefoxUserJs'].submit();
                        }
                    });

                    $('pcsgFFSendButton').appendChild( _send.create() );

                    Win.loaderStop();
                }, {
                    tpl : 'tools_firefox_user_js',
                    Win : Win
                });
            }
        }).create();
    }
};


/**
 * Extras
 */
_pcsg.extras={};
_pcsg.extras.phpinfo = function( _button )
{
    this._win =  new _ptools.Window({
        name    : '_win',
        title   : 'PHP Info',
        height  : 400,
        width   : 650,
        image   : URL_BIN_DIR+'16x16/php.png',
        onclose : '_pcsg.extras._win = null;'
    });

    this._win.create();
    this._win.loaderStart();

    //this.setBody();
    _Ajax.asyncPost('ajax_phpinfo', function(html)
    {
        var style= '<style type="text/css">'+
            '.phpinfo pre {margin: 0px; font-family: monospace;}'+
            '.phpinfo a:link {color: #000099; text-decoration: none; background-color: #ffffff;}'+
            '.phpinfo a:hover {text-decoration: underline;}'+
            '.phpinfo table {border-collapse: collapse; width:600px}'+
            '.phpinfo .center {text-align: center;}'+
            '.phpinfo .center table { margin-left: auto; margin-right: auto; text-align: left;}'+
            '.phpinfo .center th { text-align: center !important; }'+
            '.phpinfo td, .phpinfo th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}'+
            '.phpinfo h1 {font-size: 150%;}'+
            '.phpinfo h2 {font-size: 125%;}'+
            '.phpinfo .p {text-align: left;}'+
            '.phpinfo .e {background-color: #ccccff; font-weight: bold; color: #000000;}'+
            '.phpinfo .h {background-color: #9999cc; font-weight: bold; color: #000000;}'+
            '.phpinfo .v {background-color: #cccccc; color: #000000;}'+
            '.phpinfo .vr {background-color: #cccccc; text-align: right; color: #000000;}'+
            '.phpinfo img {float: right; border: 0px;}'+
            '.phpinfo hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}'+
        '</style>';

        _pcsg.extras._win.setBody(style + '<div class="phpinfo" style="width: 600px; margin-left: auto; margin-right:auto; margin-top: 20px">'+html+'</div>');
        _pcsg.extras._win.loaderStop();
    });
};
/*
_pcsg.extras.systemcheck = function()
{
    this._win = new _ptools.Window({
        title  : 'System Pr&uuml;fung',
        name   : '_SystemCheckWindow',
        height : 450,
        width  : 650
    });

    this._win.create();
    this._win.loaderStart();

    _Ajax.asyncPost('ajax_system_check', function(html) {
        _pcsg.extras._win.setBody(html);
        _pcsg.extras._win.loaderStop();
    });
};
*/

_pcsg.extras.project =
{
    create : function()
    {
        new _ptools.SubmitWindow({
            title    : 'Projekt erstellen',
            name     : '_CreateProject',
            height   : 240,
            width    : 550,
            onsubmit : function(Win)
            {
                var elms = document.forms['create_project'].elements;

                if (_ptools._System.empty( elms['lang'].value ) ||
                    _ptools._System.empty( elms['project'].value))
                {
                    _ptools._Helper.addError(
                        'Name oder Sprache des Projektes wurde nicht angegeben',
                        'Das Projekt konnte nicht angelegt werden. Bitte geben Sie einen Namen und eine Sprache an.'
                    );

                    _ptools._Helper.show();
                    return false;
                }

                Win.loaderStart();

                _Ajax.asyncPost('ajax_project_create', function(result, Ajax)
                {
                    Ajax.getAttribute('Win').close();

                    new _ptools.Info({
                        text : 'Das Projekt wurde erfolgreich angelegt'
                    }).create();

                }, {
                    newname  : elms['project'].value,
                    lang     : elms['lang'].value,
                    template : elms['template'].checked ? 1 : 0,
                    Win      : Win
                })

                return false;
            },
            onopen : function(Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_get_tpl', function(html, Ajax)
                {
                    Ajax.getAttribute('Win').setBody( html );
                    Ajax.getAttribute('Win').loaderStop();
                }, {
                    tpl : 'create_project',
                    Win : Win
                });
            }
        }).create();
    }
};

_pcsg.extras.plugins =
{
    _b_install  : null,
    _b_update   : null,
    _b_download : null,

    open : function()
    {
        this._win =  new _ptools.Window({
            name   : '_win',
            title  : 'Plugins',
            height : 400,
            width  : 690,
            image  : URL_BIN_DIR +'16x16/plugins.png'
        });

        this._win.create();
        this._win.loaderStart('verfügbare Plugins werden geladen....');

        _Ajax.asyncPost('ajax_plugins_getmanager', function(result) {
            _pcsg.extras.plugins.setBody(result);
        });
    },

    setBody : function(html)
    {
        var t = this;

        t._win.setBody( html );

        var oToolbar = $('settings_toolbar');
        var aSplit   = $('plg_active_plugins').innerHTML.split(',');

        var aPlugins = [];

        for (var i = 0, len = aSplit.length; i < len; i++) {
            aPlugins[ aSplit[i] ] = 1;
        }

        t._b_install = new _ptools.Button({
            text    : 'Installierte Plugins',
            width   : 150,
            image   : URL_BIN_DIR +'32x32/plugins.png',
            onclick : "_pcsg.extras.plugins.setInstallActive"
        });
        oToolbar.appendChild( this._b_install.create() );

        t._b_update = new _ptools.Button({
            text    : 'Updates',
            width   : 150,
            image   : URL_BIN_DIR +'32x32/update.png',
            onclick : "_pcsg.extras.plugins.setUpdateActive"
        });
        oToolbar.appendChild( this._b_update.create() );

        t._b_download = new _ptools.Button({
            text    : 'Neue Plugins',
            width   : 150,
            image   : URL_BIN_DIR +'32x32/down.png',
            onclick : "_pcsg.extras.plugins.setDownloadActive"
        });
        oToolbar.appendChild( this._b_download.create() );

        t._b_remove = new _ptools.Button({
            text    : 'Plugins löschen',
            width   : 150,
            image   : URL_BIN_DIR +'32x32/trashcan_full.png',
            onclick : "_pcsg.extras.plugins.setRemoveActive"
        });
        oToolbar.appendChild( this._b_remove.create() );

        var plugins = $('settings_ids');
        plugins     = plugins.innerHTML.split(',');

        for (var i = 0, len = plugins.length; i < len; i++)
        {
            var plg  = plugins[i];
            var oElm = document.getElementById( 'active_'+ plg );

            if (oElm)
            {
                var _setup = new _ptools.Button({
                    name    : plg +'_setup',
                    plugin  : plg,
                    image   : URL_BIN_DIR +'22x22/plugins.png',
                    title   : 'Setup durchführen',
                    onclick : '_pcsg.extras.plugins.setup'
                });
                oElm.appendChild( _setup.create() );

                var _update = new _ptools.Button({
                    name    : plg +'_update',
                    plugin  : plg,
                    image   : URL_BIN_DIR +'22x22/update.png',
                    title   : 'Update durchführen',
                    onclick : function(_me) {
                        _pcsg.extras.plugins.update(_me);
                    }
                });
                oElm.appendChild( _update.create() );
                //_update.setDisable();

                if (aPlugins[ plg ])
                {
                    var _button = new _ptools.Button({
                        name    : plg,
                        plugin  : plg,
                        image   : URL_BIN_DIR +'22x22/apply.png',
                        title   : 'Status: aktiv. (Per Klick Plugin deaktivieren)',
                        onclick : '_pcsg.extras.plugins.deactivate'
                    });
                    oElm.appendChild( _button.create() );
                } else
                {
                    var _button = new _ptools.Button({
                        name    : plg,
                        plugin  : plg,
                        image   : URL_BIN_DIR +'22x22/cancel.png',
                        title   : 'Status: inaktiv. (Per Klick Plugin aktivieren)',
                        onclick : '_pcsg.extras.plugins.activate'
                    });

                    oElm.appendChild( _button.create() );
                }
            }
        }

        var newPlugins = $('new_settings_ids');

        if (newPlugins)
        {
            newPlugins = newPlugins.innerHTML.split(',');

            for (var i = 0, len = newPlugins.length; i < len; i++)
            {
                var plg  = newPlugins[i];
                var oElm = document.getElementById( 'download_'+ plg );

                if (oElm)
                {
                    var _download = new _ptools.Button({
                        name    : plg +'_download',
                        plugin  : plg,
                        title   : 'Plugin herunterladen',
                        text    : 'Plugin herunterladen',
                        onclick : '_pcsg.extras.plugins.download'
                    });
                    oElm.appendChild( _download.create() );

                }
            }
        }

        t.setInstallActive();
        t._win.loaderStop();
    },

    setInstallActive : function()
    {
        $('settings_body_instaled').style.display = '';
        $('settings_body_download').style.display = 'none';
        $('settings_body_remove').style.display   = 'none';
        $('settings_body_update').style.display   = 'none';

        this._b_install.setActive();
        this._b_download.setNormal();
        this._b_remove.setNormal();
        this._b_update.setNormal();
    },

    setDownloadActive : function()
    {
        $('settings_body_instaled').style.display = 'none';
        $('settings_body_update').style.display   = 'none';
        $('settings_body_remove').style.display   = 'none';
        $('settings_body_download').style.display = '';

        this._b_install.setNormal();
        this._b_update.setNormal();
        this._b_remove.setNormal();
        this._b_download.setActive();
    },
    setUpdateActive : function()
    {
        $('settings_body_instaled').style.display = 'none';
        $('settings_body_download').style.display = 'none';
        $('settings_body_remove').style.display   = 'none';
        $('settings_body_update').style.display   = '';

        this._b_install.setNormal();
        this._b_download.setNormal();
        this._b_remove.setNormal();
        this._b_update.setActive();

        this._win.loaderStart('verfügbare Updates werden geladen....');

        _Ajax.asyncPost('ajax_plugins_get_update_manager', function(result)
        {
            if (!$('settings_body_update')) {
                return;
            }

            $('settings_body_update').innerHTML = result;

            var updatePlugins = $('update_settings_ids');

            if (updatePlugins)
            {
                updatePlugins = updatePlugins.innerHTML.split(',');

                for (var i = 0, len = updatePlugins.length; i < len; i++)
                {
                    var plg  = updatePlugins[i];
                    var oElm = document.getElementById( 'update_'+ plg );

                    if (oElm)
                    {

                        var _update = new _ptools.Button({
                        name      : plg +'_update',
                        plugin    : plg,
                        textimage : URL_BIN_DIR +'16x16/update.png',
                        title     : 'Update durchführen',
                        text      : 'Update durchführen',
                        onclick   : function(_me) {
                            _pcsg.extras.plugins.update(_me);
                        }
                        });
                        oElm.appendChild( _update.create() );
                    }
                }
            }

            _pcsg.extras.plugins._win.loaderStop();
        });

    },
    setRemoveActive : function()
    {
        $('settings_body_instaled').style.display = 'none';
        $('settings_body_download').style.display = 'none';
        $('settings_body_update').style.display   = 'none';
        $('settings_body_remove').style.display   = '';

        this._b_install.setNormal();
        this._b_download.setNormal();
        this._b_update.setNormal();
        this._b_remove.setActive();

        this._win.loaderStart('Plugins werden geladen...');

        _Ajax.asyncPost('ajax_plugins_get_remove_manager', function(result)
        {
            if (!$('settings_body_remove')) {
                return;
            }

            $('settings_body_remove').innerHTML = result;

            var removePlugins = $('remove_settings_ids');

            if (removePlugins)
            {
                removePlugins = removePlugins.innerHTML.split(',');

                for (var i = 0, len = removePlugins.length; i < len; i++)
                {
                    var plg  = removePlugins[i];
                    var oElm = document.getElementById( 'remove_'+ plg );

                    if (oElm)
                    {

                        var _remove = new _ptools.Button({
                        name      : plg +'_remove',
                        plugin    : plg,
                        textimage : URL_BIN_DIR +'16x16/trashcan_empty.png',
                        title     : 'Plugin löschen',
                        text      : 'Plugin löschen',
                        onclick   : function(_me) {
                            _pcsg.extras.plugins.remove(_me);
                        }
                        });
                        oElm.appendChild( _remove.create() );
                    }
                }
            }

            _pcsg.extras.plugins._win.loaderStop();
        });

    },

    openInfo : function(id, oDiv)
    {
        var oElm = document.getElementById('info_'+ id);

        if (!oElm || !oDiv) {
            return;
        }

        if (oElm.style.display == 'none')
        {
            oElm.style.display = '';
            oDiv.className     = 'plg_itm_closer';
            return;
        }

        oElm.style.display = 'none';
        oDiv.className     = 'plg_itm_opener';
    },

    activate : function(_Btn)
    {
        this._win.loaderStart();

        _Ajax.syncPost('ajax_plugins_activate', {
            plugin : _Btn.getAttribute('plugin')
        });

        _Ajax.asyncPost('ajax_plugins_getmanager', function(result) {
            _pcsg.extras.plugins.setBody(result);
        });
    },

    deactivate : function(_Btn)
    {
        this._win.loaderStart();

        _Ajax.syncPost('ajax_plugins_deactivate', {
            plugin : _Btn.getAttribute('plugin')
        });

        _Ajax.asyncPost('ajax_plugins_getmanager', function(result) {
            _pcsg.extras.plugins.setBody(result);
        });
    },

    update : function(_Btn)
    {
        this._win.loaderStart('Das Update wird Installiert...');

        try
        {
            _Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

            _Ajax.asyncPost('ajax_plugins_update', function(result)
            {
                new _ptools.Info({
                    name : 'update',
                    text : 'Update wurde erfolgreich durchgeführt'
                }).create();

                _Ajax.asyncPost('ajax_plugins_getmanager', function(result) {
                    _pcsg.extras.plugins.setBody( result );
                });

            }, {
                plugin : _Btn.getAttribute('plugin')
            });

        } catch(e)
        {
            new _ptools.Alert({
                text : 'Update wurde abgebrochen',
                information : e.getCode() +' : '+ e.getMessage()
            }).create();
        }
    },
    remove : function(_Btn)
    {

        _Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

        var plugin      = _Btn.getAttribute('plugin');
        var pluginName  = $('plugin_name_' + plugin).innerHTML;
        var removeForce = $('remove_force_' + plugin).checked;
        var text        = 'Sind Sie sicher das Sie das Plugin "'+ pluginName +'" löschen möchten?';
        var information = 'Es werden alle Dateien des Plugins "'+ pluginName +'" gelöscht. <br /><strong>Die Daten bleiben erhalten!</strong>';

        if (removeForce) {
            information = 'Es werden <strong>>alle</strong> Daten des Plugins "' + pluginName +
            '" gelöscht. <br /><strong>Der Vorgang kann nicht Rückgängig gemacht werden!</strong>';
        }

        var _win = new _ptools.Confirm({
            name     : '_removePlugin',
            title    : 'Plugin "'+ pluginName +'" löschen',
            plugin   : _Btn.getAttribute('plugin'),
            pname    : pluginName,
            force    : removeForce,
            onsubmit : function(_win)
            {
                var plugin   = _win.getAttribute('plugin')
                var force    = _win.getAttribute('force')
                var pName    = _win.getAttribute('pName')
                var settings = {plugin: plugin,force: force}

                _pcsg.extras.plugins._win.loaderStart('Das Plugin "'+ pName +'"wird gelöscht ...');

                try
                {
                    _Ajax.asyncPost('ajax_plugins_remove', function(result)
                    {
                        new _ptools.Info({
                            name : 'remove',
                            text : 'Das Plugin wurde erfolgreich gelöscht'
                        }).create();

                        _Ajax.asyncPost('ajax_plugins_getmanager', function(result) {
                            _pcsg.extras.plugins.setBody( result );
                        });

                    }, {
                        settings : JSON.encode( settings)
                    });

                } catch(e)
                {
                    new _ptools.Alert({
                        text : 'Löschvorgang wurde abgebrochen',
                        information : e.getCode() +' : '+ e.getMessage()
                    }).create();
                            _ptools.onError(
                        new _ptools.Exception(
                            'Das Plugin konnte nicht gelöscht werden!<br />'+ e.getCode() +' : '+ e.getMessage()
                        )
                    );
                }
            },
            oncancel : function(_win) {
                _Btn.setAttribute('image', '');
            },
            width       : 460,
            height      : 210,
            image       : URL_BIN_DIR +'16x16/plugins.png',
            textIcon    : URL_BIN_DIR +'48x48/trashcan_empty.png',
            text        : text,
            information : information
        });
        _win.create();

    },
    download : function(_Btn)
    {
        this._win.loaderStart('Plugin wird Heruntergeladen...');
        _Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

        _Ajax.asyncPost('ajax_plugins_install', function(result, Ajax)
        {
            _ptools._Helper.addNotice(
                  'Install',
                   result
            );

            _ptools._Helper.show();

            _Ajax.asyncPost('ajax_plugins_getmanager', function(result, Ajax) {
                _pcsg.extras.plugins.setBody( result );
            });

        }, {
            plugin : _Btn.getAttribute('plugin'),
            server : $(_Btn.getAttribute('plugin') + '_updateserver').innerHTML
        });
    },
    setup : function(Btn)
    {
        var image = Btn.getAttribute('image');

        Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

        _Ajax.asyncPost('ajax_plugins_setup', function(result, Ajax)
        {
            var Btn = Ajax.getAttribute('Btn');

            _ptools._Helper.addNotice(
                Btn.getAttribute('plugin') +' Setup',
                'Setup für das Plugin '+ Btn.getAttribute('plugin') +' wurde erfolgreich durchgeführt'
            ).show();

            Btn.setAttribute('image', Ajax.getAttribute('image'));

        }, {
            image  : image,
            Btn    : Btn,
            plugin : Btn.getAttribute('plugin')
        });
    }
};

_pcsg.extras.logs =
{
    active  : null,
    Win     : null,
    BtnDel  : null,
    BtnSend : null,

    open : function()
    {
        new _ptools.Window({
            title  : 'Logs',
            width  : 600,
            height : 400,
            image  : URL_BIN_DIR +'16x16/terminal.png',
            onopen : function(Win)
            {
                Win.loaderStart();

                Win.setBody(
                    '<div style="margin: 10px;">' +
                        '<div id="pcsg-plugins-log-list"></div>' +
                        '<pre id="pcsg-plugins-log-container"></pre>' +
                        '<div id="pcsg-plugins-log-btns"></div>' +
                    '</div>'
                );

                _pcsg.extras.logs.Win = Win;

                var style = $('pcsg-plugins-log-list').style;
                style.cssFloat   = 'left';
                style.styleFloat = 'left';
                style.width      = '170px';
                style.height     = '350px';
                style.overflow   = 'auto';
                style.border     = '1px solid #C6C3B9';
                style.backgroundColor = '#fff';

                style = $('pcsg-plugins-log-container').style;
                style.cssFloat   = 'left';
                style.styleFloat = 'left';
                style.marginLeft = '10px';
                style.width      = '390px';
                style.height     = '300px';
                style.overflow   = 'auto';
                style.border     = '1px solid #C6C3B9';
                style.backgroundColor = '#fff';

                style = $('pcsg-plugins-log-btns').style;
                style.margin     = '10px';
                style.cssFloat   = 'left';
                style.styleFloat = 'left';

                var BtnDel = new _ptools.Button({
                    title   : 'Log löschen',
                    alt     : 'Log löschen',
                    image   : URL_BIN_DIR +'22x22/trashcan_empty.png',
                    onclick : function(Btn)
                    {
                        if (!_pcsg.extras.logs.active) {
                            return;
                        }

                        new _ptools.Confirm({
                            title       : 'Log löschen?',
                            textIcon    : URL_BIN_DIR +'32x32/trashcan_empty.png',
                            image       : URL_BIN_DIR +'16x16/trashcan_empty.png',
                            text        : 'Möchten Sie die Log wirklich löschen?',
                            information : 'Die Error Log wird unwiderruflich gelöscht',
                            width       : 400,
                            height      : 150,
                            onsubmit    : function(Win)
                            {
                                var Btn = Win.getAttribute('Btn');

                                _pcsg.extras.logs.deleteLog(
                                    Btn.getAttribute('log')
                                );
                            },
                            Btn : Btn
                        }).create()
                    }
                });

                var BtnSend = new _ptools.Button({
                    alt     : 'Log senden',
                    title   : 'Log senden',
                    image   : URL_BIN_DIR +'22x22/mail.png',
                    onclick : function(Btn)
                    {
                        if (!_pcsg.extras.logs.active) {
                            return;
                        }

                        _pcsg.extras.logs.sendLog( Btn.getAttribute('log') );
                    }
                });

                $('pcsg-plugins-log-btns').appendChild( BtnDel.create() );
                $('pcsg-plugins-log-btns').appendChild( BtnSend.create() );

                _pcsg.extras.logs.BtnDel = BtnDel;
                _pcsg.extras.logs.BtnDel.setDisable();

                _pcsg.extras.logs.BtnSend = BtnSend;
                _pcsg.extras.logs.BtnSend.setDisable();

                _pcsg.extras.logs.loadList();
            }
        }).create();
    },

    loadList : function()
    {
        $('pcsg-plugins-log-list').innerHTML      = '';
        $('pcsg-plugins-log-container').innerHTML = '';

        _Ajax.asyncPost('ajax_plugins_logs_list', function(result, Ajax)
        {
            var Div;
            var oDiv = new Element('div');

            oDiv.style.cssFloat   = 'left';
            oDiv.style.styleFloat = 'left';
            oDiv.style.clear      = 'both';
            oDiv.style.padding    = '2px 5px';
            oDiv.style.margin     = '2px';
            oDiv.style.cursor     = 'pointer';
            oDiv.style.border     = '1px solid #FFF';

            for (var i = 0, len = result.length; i < len; i++)
            {
                Div = oDiv.cloneNode(true);

                Div.innerHTML   = '<span style="margin: 2px;">'+ result[i] +'</span>';
                Div.onmouseover = function()
                {
                    this.style.backgroundColor = '#B6C5F2';
                    this.style.border = '1px solid #2D4488';
                };

                Div.onmouseout = function()
                {
                    if (_pcsg.extras.logs.active == this) {
                        return;
                    }

                    this.style.backgroundColor = '#FFF';
                    this.style.border = '1px solid #FFF';
                };

                Div.onclick = function()
                {
                    _pcsg.extras.logs.loadLog( this.getAttribute('log') );

                    if (_pcsg.extras.logs.active)
                    {
                        _pcsg.extras.logs.active.style.backgroundColor = '#FFF';
                        _pcsg.extras.logs.active.style.border = '1px solid #FFF';
                    }


                    _pcsg.extras.logs.active = this;

                    this.style.backgroundColor = '#B6C5F2';
                    this.style.border = '1px solid #2D4488';

                    _pcsg.extras.logs.BtnDel.setEnable();
                    _pcsg.extras.logs.BtnDel.setAttribute('log', this.getAttribute('log'));

                    _pcsg.extras.logs.BtnSend.setEnable();
                    _pcsg.extras.logs.BtnSend.setAttribute('log', this.getAttribute('log'));
                };

                Div.setAttribute('log', result[i]);

                $('pcsg-plugins-log-list').appendChild( Div );

                if (i == 0) {
                    Div.onclick();
                }
            }

            _pcsg.extras.logs.Win.loaderStop();
        });
    },

    deleteLog : function(log)
    {
        if (_pcsg.extras.logs.Win) {
            _pcsg.extras.logs.Win.loaderStart();
        }

        _Ajax.asyncPost('ajax_plugins_logs_delete', function(result, Ajax)
        {
            _pcsg.extras.logs.loadList();
        }, {
            log : log
        });
    },

    sendLog : function(log)
    {
        if (_pcsg.extras.logs.Win) {
            _pcsg.extras.logs.Win.loaderStart();
        }

        _Ajax.asyncPost('ajax_plugins_logs_send', function(result, Ajax)
        {
            _pcsg.extras.logs.Win.loaderStop();
        }, {
            log : log
        });
    },

    loadLog : function(log)
    {
        if (!$('pcsg-plugins-log-container')) {
            return;
        }

        if (_pcsg.extras.logs.Win) {
            _pcsg.extras.logs.Win.loaderStart();
        }

        _Ajax.asyncPost('ajax_plugins_logs_get', function(result, Ajax)
        {
            $('pcsg-plugins-log-container').innerHTML = result;

            if (_pcsg.extras.logs.Win) {
                _pcsg.extras.logs.Win.loaderStop();
            }

        }, {
            log : log
        });
    }
};

/**
 * Multilingual
 */
_pcsg.multilingual = {};
_pcsg.multilingual.open = function()
{
    // Wizard starten
    this._win = new _ptools.Window({
        name    : '_win',
        title   : 'Verkn&uuml;pfungen',
        height  : 300,
        width   : 450,
        image   : URL_BIN_DIR +'16x16/lang_link.png',
        onclose : '_pcsg.multilingual._win = null;'
    });

    this._win.create();
    this.setBody();
};

_pcsg.multilingual.lm = function()
{
    // Wizard starten
    this._win = new _ptools.Window({
        name   : '_win',
        title  : 'Verwaltung',
        height : 300,
        width  : 450,
        image  : URL_BIN_DIR +'16x16/lang_link.png'
    });

    this._win.create();
};

_pcsg.multilingual.setBody = function()
{
    if (!this._win) {
        return;
    }

    this._win.setBody(
        _Ajax.syncPost('ajax_multilingual_manager', {
            id              : _Site.getId(),
            lang          : _Project.getAttribute('lang'),
            project_name : _Project.getAttribute('name')
        })
    );

    // Buttons erstellen
    var langs = _Project.getAttribute('langs');

    for (var i = 0, len = langs.length; i < len; i++)
    {
        if ($('trash_'+langs[i]))
        {
            var _t = new _ptools.Button({
                name    : 'trash_'+ langs[i],
                image   : URL_BIN_DIR +'16x16/trashcan_empty.png',
                onclick : '_pcsg.multilingual.removelink',
                lang    : langs[i],
                title   : 'Verknüpfung "'+ langs[i] +'" löschen',
                alt     : 'Verknüpfung "'+ langs[i] +'" löschen'
            });

            $('trash_'+ langs[i]).appendChild( _t.create() );
        }
    }

    // Buttons Verknüpfung erstellen
    var oAdd = $('addLangButtons');
    langs    = oAdd.innerHTML;

    if (!langs.match(',')) {
        return;
    }

    oAdd.innerHTML = '';
    langs = langs.split(',');

    for (var i = 0, len = langs.length; i < len; i++)
    {
        if (langs[i])
        {
            var _b = new _ptools.Button({
                name    : langs[i],
                image   : URL_BIN_DIR +'16x16/flags/'+ langs[i] +'.png',
                onclick : '_pcsg.multilingual.sitemap',
                text    : langs[i],
                lang    : langs[i]
            });
            oAdd.appendChild( _b.create() );
        }
    }
};

_pcsg.multilingual.sitemap = function(Btn)
{
    this.lang = Btn.getAttribute('lang');

    var _Sitemap = new _pcsg.Sitemap({
        onsubmit : function(params) {
            _pcsg.multilingual.addlink( params );
        },
        project  : _Project.getAttribute('name'),
        lang     : this.lang
    });

    _Sitemap.open();
};

_pcsg.multilingual.addlink = function(params)
{
    _Site.addLanguageLink(this.lang, params.id);

    this.setBody();
};

_pcsg.multilingual.removelink = function(Btn)
{
    if (Btn.getAttribute('lang') == false) {
        return;
    }

    _Site.removeLanguageLink( Btn.getAttribute('lang') );
    this.setBody();
};

_pcsg.multilingual.createLinkInLang = function(lang)
{
    var Sitemap = new _pcsg.Sitemap({
        onsubmit : function(params)
        {
            _Ajax.asyncPost('ajax_multilingual_copy', function(result)
            {

            }, {
                project_name : _Project.getAttribute('name'),
                sitelang     : _Project.getAttribute('lang'),
                id           : _Site.getId(),
                parentid     : params.id,
                parentlang   : params.lang
            });
        },
        showprojects : false,
        project      : _Project.getAttribute('name'),
        lang         : lang,
        message      : 'Wählen Sie die Elternseite aus unter welcher die Kopie eingefügt werden soll'
    });

    Sitemap.open();
};

/**
 * Dienste Verwaltung
 */
_pcsg.crons =
{
    open : function()
    {
        this.Win = new _ptools.Window({
            name     : 'CronWindow',
            title    : 'Dienste Verwaltung',
            height   : 350,
            width    : 550,
            image    : URL_BIN_DIR +'16x16/tasks.png',
            onopen : function(Win)
            {
                Win.setBody(
                    '<div id="cronbody">'+
                        '<div id="crontable"></div>'+
                        '<div id="addcron"></div>'+
                    '</div>'
                );

                _pcsg.crons.List = new omniGrid('crontable', {
                    columnModel: [
                        {header : '',       dataIndex : 'del',       dataType : 'button', width : 40},
                        {header : '',       dataIndex : 'start',     dataType : 'button', width : 40},
                        {header : 'Id',     dataIndex : 'id',        dataType : 'string', width : 30},
                        {header : 'Plugin', dataIndex : 'plugin',    dataType : 'string', width : 130},
                        {header : 'Cron',   dataIndex : 'cronname',  dataType : 'string', width : 130},
                        {header : 'Min',    dataIndex : 'min',       dataType : 'string', width : 40},
                        {header : 'Std',    dataIndex : 'hour',      dataType : 'string', width : 40},
                        {header : 'Tag',    dataIndex : 'day',       dataType : 'string', width : 40},
                        {header : 'Mon',    dataIndex : 'month',     dataType : 'string', width : 40},
                        {header : 'Parameter', dataIndex : 'params', dataType : 'string', width : 200}
                    ],
                    pagination : false,
                    //perpage    : perPage,
                    //page       : 1,
                    serverSort : false,
                    showHeader : true,
                    sortHeader : true,

                    alternaterows : true,
                    resizeColumns : true,

                    multipleSelection : false,

                    width  : 530,
                    height : 200
                });
            }
        });

        this.Win.create();
        this.refresh();
    },

    refresh : function()
    {
        _pcsg.crons.Win.loaderStart();

        _Ajax.asyncPost('ajax_cron_list', function(result, Ajax)
        {
            var data = [];
            var res  = '';

            for (var i = 0, len = result.length; i < len; i++)
            {
                res = result[i];

                data.push({
                    id       : res.id,
                    min      : res.min,
                    hour     : res.hour,
                    day      : res.day,
                    month    : res.month,
                    plugin   : res.plugin,
                    cronname : res.cronname,
                    params   : res.params,
                    del : {
                        image   : URL_BIN_DIR +'16x16/cancel.png',
                        cid     : res.id,
                        title   : 'Dienste löschen',
                        onclick : function(Btn)
                        {
                            new _ptools.Confirm({
                                name        : 'deletecron',
                                title       : 'Dienst löschen?',
                                onsubmit    : function(Win)
                                {
                                    _Ajax.asyncPost('ajax_cron_delete', function(result, Ajax)
                                    {
                                        _pcsg.crons.refresh();
                                    }, {
                                        Confirm : Win,
                                        cid     : Win.getAttribute('cid')
                                    });
                                },
                                width       : 350,
                                height      : 150,
                                image       : URL_BIN_DIR +'16x16/trashcan_full.png',
                                textIcon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                                text        : 'Wollen Sie den Dienst wirklich löschen?',
                                information : 'Das Löschen des Dienstes kann nicht rückgängig gemacht werden.',
                                cid         : Btn.getAttribute('cid')
                            }).create();
                        }
                    },
                    start : {
                        image   : URL_BIN_DIR +'16x16/play.png',
                        cid     : res.id,
                        title   : 'Dienst ausführen',
                        onclick : function(Btn)
                        {
                            Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

                            _Ajax.asyncPost('ajax_cron_execute', function(result, Ajax)
                            {
                                Btn.setAttribute('image', URL_BIN_DIR +'16x16/play.png');
                            }, {
                                cid : Btn.getAttribute('cid'),
                                Btn : Btn
                            });
                        }
                    },
                    ondblclick : function(Row, data)
                    {
                        new _ptools.SubmitWindow({
                            name        : 'editcron',
                            title       : 'Dienst editieren',
                            params      : data,
                            onopen      : function(Win)
                            {
                                Win.setBody(
                                    '<div style="margin: 10px;">' +
                                        '<div id="add_params_cron_btn"></div>' +
                                        '<form id="add_params_cron" name="add_params_cron"></form>' +
                                    '</div>'
                                );

                                var params = Win.getAttribute('params');
                                    params = eval(params.params);

                                if (params.length)
                                {
                                    for (var i = 0, len = params.length; i < len; i++)
                                    {
                                        $('add_params_cron').appendChild(
                                            new _pcsg.crons.paramsElm({
                                                name  : params[i]['name'],
                                                value : params[i]['value']
                                            }).create()
                                        );
                                    }
                                }

                                $('add_params_cron_btn').appendChild(
                                    new _ptools.Button({
                                        text    : 'Parameter hinzufügen',
                                        onclick : function()
                                        {
                                            $('add_params_cron').appendChild(
                                                new _pcsg.crons.paramsElm().create()
                                            );
                                        }
                                    }).create()
                                );
                            },
                            onsubmit    : function(Win)
                            {
                                if (!document.forms['add_params_cron']) {
                                    return;
                                }

                                var form   = document.forms['add_params_cron'];
                                var names  = form.elements['names'];
                                var values = form.elements['values'];
                                var params = [];

                                if (form.elements['names'] && !form.elements['names'].length)
                                {
                                    params.push({
                                        name  : names.value,
                                        value : values.value
                                    });
                                } else
                                {
                                    for (var i = 0, len = names.length; i < len; i++)
                                    {
                                        params.push({
                                            name  : names[i].value,
                                            value : values[i].value
                                        });
                                    }
                                }

                                _Ajax.asyncPost('ajax_cron_edit_params', function(result, Ajax)
                                {
                                    _pcsg.crons.refresh();
                                }, {
                                    cid    : Win.getAttribute('cid'),
                                    params : JSON.encode( params )
                                });
                            },
                            width       : 400,
                            height      : 250,
                            image       : URL_BIN_DIR +'16x16/edit.png',
                            cid         : data.id
                        }).create();
                    }
                });
            }

            _pcsg.crons.List.setData({
                data : data
            });

            _Ajax.asyncPost('ajax_cron_add_template', function(result, Ajax)
            {
                if (!$('addcron')) {
                    return;
                }

                $('addcron').innerHTML = result;

                $('btn_add_cron').appendChild(
                    new _ptools.Button({
                        image   : URL_BIN_DIR +'16x16/add.png',
                        title   : 'Cron hinzufügen',
                        alt     : 'Cron hinzufügen',
                        onclick : function(Btn)
                        {
                            _pcsg.crons.Win.loaderStart();

                            var form     = document.forms['cronsettings'];
                            var plgvalue = form.elements['plugin'].value.split(';');

                            var params = {
                                plugin   : plgvalue[0],
                                cronname : plgvalue[1],
                                min      : form.elements['min'].value,
                                hour     : form.elements['hour'].value,
                                day      : form.elements['day'].value,
                                month    : form.elements['month'].value
                            };

                            _Ajax.asyncPost('ajax_cron_add', function(result, Ajax)
                            {
                                _pcsg.crons.refresh();
                            }, {
                                params : JSON.encode(params)
                            });
                        }
                    }).create()
                );

                _pcsg.crons.Win.loaderStop();
            });
        });
    }
};

_pcsg.crons.paramsElm = function(settings)
{
    var settings = settings || {};

    var oName  = null;
    var oValue = null;
    var oElm   = null;
    var oDel   = null;

    this.create = function()
    {
        oElm   = document.createElement('div');
        oDel   = document.createElement('div');
        oName  = document.createElement('input');
        oValue = document.createElement('input');

        oElm.style.clear  = 'both';

        oName.style.cssFloat   = 'left';
        oName.style.styleFloat = 'left';
        oName.style.margin     = '2px';
        oName.type = 'text';
        oName.name = 'names';

        if (settings.name) {
            oName.value = settings.name;
        }

        oValue.style.cssFloat   = 'left';
        oValue.style.styleFloat = 'left';
        oValue.style.margin     = '2px';
        oValue.type = 'text';
        oValue.name = 'values';

        if (settings.value) {
            oValue.value = settings.value;
        }

        oDel.style.cssFloat   = 'left';
        oDel.style.styleFloat = 'left';
        oDel.style.margin     = '0 2px';

        oDel.appendChild(
            new _ptools.Button({
                image   : URL_BIN_DIR +'16x16/cancel.png',
                Param   : this,
                onclick : function(Btn)
                {
                    var Param = Btn.getAttribute('Param');
                    Param.remove();
                }
            }).create()
        );

        oElm.appendChild( oName );
        oElm.appendChild( oValue );
        oElm.appendChild( oDel );

        return oElm;
    }

    this.remove = function()
    {
        oElm.parentNode.removeChild( oElm );
    }
};

_pcsg.crons.robottxt =
{
    open : function()
    {
        new _ptools.SubmitWindow({
            name   : 'robot_txt',
            title  : 'robot.txt',
            height : 350,
            width  : 550,
            image  : URL_BIN_DIR +'16x16/robottxt.png',
            body   : '<div id="robottxtbody" style="margin: 10px;">' +
                        '<textarea id="robottxt" style="margin: 10px; width: 500px; height: 220px"></textarea>' +
                        '<div id="robotbtns" style="float: left; margin-left: 10px;"></div>'+
                    '</div>',
            onopen : function(Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_get_robot_txt', function(result, Ajax)
                {
                    if (!$('robottxt'))
                    {
                        Ajax.getAttribute('Win').loaderStop();
                        return;
                    }

                    $('robottxt').value = result;
                    $('robotbtns').appendChild(
                        new _ptools.Button({
                            name    : 'insert_standard_robot',
                            text    : 'Standard robots.txt einfügen',
                            onclick : function()
                            {
                                $('robottxt').value = '' +
                                    'User-Agent: *\n'+
                                    'Disallow: /ajax.php\n'+
                                    'Disallow: /update.php\n'+
                                    'Disallow: /cron.php\n'+
                                    'Disallow: /mail_protection.php\n'+
                                    'Disallow: /header.php\n'+
                                    'Disallow: /opt/\n'+
                                    'Disallow: /usr/\n'+
                                    'Disallow: /bin/';
                            }
                        }).create()
                    );

                    Ajax.getAttribute('Win').loaderStop();
                }, {
                    Win : Win
                });
            },
            onsubmit : function(Win)
            {
                Win.loaderStart();

                _Ajax.asyncPost('ajax_set_robot_txt', function(result, Ajax)
                {
                    Ajax.getAttribute('Win').close();
                }, {
                    Win  : Win,
                    text : $('robottxt').value
                });

                return false;
            }
        }).create();
    }
};

_pcsg.goToPage = function(MoveTo)
{
    _pcsg.Controls.Loader.start();

    _Ajax.asyncPost('ajax_site_get_parentids', function(result, Ajax)
    {
        var NewProject = new Project(
            Ajax.getAttribute('project'),
            Ajax.getAttribute('lang')
        );

        NewProject.setAttribute('GoToMoveTo', Ajax.getAttribute('MoveTo'));
        NewProject.setAttribute('GoToResult', result);

        NewProject.load(function(NewProject)
        {
            _pcsg.Controls.Loader.start();

            var MoveTo = NewProject.getAttribute('GoToMoveTo');
            var result = NewProject.getAttribute('GoToResult');
            // Seitenöffnen

            var Site = _Sitemap.firstChild();

            if (MoveTo.id == 1) {
                return Site.onclick();
            }

            for (var i = 1, len = result.length; i < len; i++)
            {
                _pcsg.Controls.Loader.start();

                var Child = Site.getChildByValue( result[i] );

                if (!Child)
                {
                    Site.search({id : result[i]});
                    Child = Site.getChildByValue( result[i] );

                    if (!Child)
                    {
                        _ptools.onError(
                            new _ptools.Exception(
                                'Seite wurde nicht gefunden',
                                404
                            )
                        );

                        _pcsg.Controls.Loader.start();
                        return
                    }
                }

                Child.open();
                Site = Child;
            }

            Child = Site.getChildByValue( MoveTo.id );

            if (Child) {
                return Child.onclick();
            }

            Site.search({id : MoveTo.id});
            Child = Site.getChildByValue( MoveTo.id );

            if (Child) {
                return Child.onclick();
            }
        });

        _pcsg.Controls.Loader.stop();
    }, {
        project : MoveTo.project,
        lang    : MoveTo.lang,
        id      : MoveTo.id,
        MoveTo  : MoveTo
    });
};

// CMS Globale Suche
_pcsg.search =
{
    open : function()
    {
        _pcsg.SiteSearch.open({
            onsubmit : function(result, WIn)
            {
                _pcsg.goToPage({
                    project : result.project,
                    lang    : result.lang,
                    id      : result.id
                });
            }
        });
    }
};
