/**
 * A QUIQQER project
 *
 *
 * @events onSiteStatusEditBegin
 * @events onSiteStatusEditEnd
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires
 *
 * @module classes/Project
 * @package com.pcsg.qui.js.classes
 * @namespace QUI.classes
 */

define('classes/Project', [

    'classes/DOM',
    'classes/project/Site',
    'classes/project/Media',
    'classes/project/Trash'

], function(DOM, Site)
{
    /**
     * A project
     *
     * @class QUI.classes.Project
     *
     * @param {Object} options
     */
    QUI.classes.Project = new Class({

        Implements: [DOM],

        options : {
            name : '',
            lang : 'de'
        },

        $ids   : {},
        $Media : null,
        $Trash : null,

        initialize : function(options)
        {
            this.init( options );
        },

        /**
         * Get a site from the project
         *
         * @method QUI.classes.Project#get
         *
         * @param {Integer} id - ID of the site
         * @return {QUI.classes.project.Site}
         */
        get : function(id)
        {
            if ( typeof this.$ids[ id ] === 'undefined' )
            {
                this.$ids[id] = new QUI.classes.project.Site( this, id );

                this.$ids[id].addEvent( 'onDelete', function(Site)
                {
                    this.deleteChild( Site.getId() );
                }.bind( this ) );
            }

            return this.$ids[ id ];
        },

        /**
         * Delete the child entry
         *
         * @method QUI.classes.Project#deleteChild
         *
         * @param {Integer} id - ID of the site
         * @return {this}
         */
        deleteChild : function(id)
        {
            if ( this.$ids[ id ] ) {
                delete this.$ids[ id ];
            }

            return this;
        },

        /**
         * Return the Media Object for the Project
         *
         * @method QUI.classes.Project#getMedia
         *
         * @return {QUI.classes.project.Media}
         */
        getMedia : function()
        {
            if ( !this.$Media ) {
                this.$Media = new QUI.classes.project.Media( this );
            }

            return this.$Media;
        },

        /**
         * Return the Trash Object for the Project
         *
         * @method QUI.classes.Project#getTrash
         *
         * @return {QUI.classes.project.Trash}
         */
        getTrash : function()
        {
            if ( !this.$Trash ) {
                this.$Trash = new QUI.classes.project.Trash( this );
            }

            return this.$Trash;
        },

        /**
         * Return the Project name
         *
         * @method QUI.classes.Project#getName
         *
         * @return {String}
         */
        getName : function()
        {
            if ( this.getAttribute( 'project' ) ) {
                return this.getAttribute( 'project' );
            }

            return this.getAttribute( 'name' );
        },

        /**
         * Return the Project lang
         *
         * @method QUI.classes.Project#getName
         *
         * @return {String}
         */
        getLang : function()
        {
            return this.getAttribute('lang');
        }
    });

    return QUI.classes.Project;
});

/*
_pcsg.classes.Project = function(name, lang)
{
    var t    = this;
    var type = 'PROJECT';

    var attributes = [];

    var FirstChild = null,
        oMedia     = null,
        oToolbar   = null,
        oTabbar    = null;

    t.__construct = function(name, lang)
    {
        t.setAttribute('name', name);
        t.setAttribute('lang', lang);
    };

    t.getMedia = function()
    {
        if (oMedia == null) {
            oMedia = new Media( this );
        };

        return oMedia;
    };

    t.getAttribute = function(att)
    {
        if (attributes[att]) {
            return attributes[att];
        };

        return false;
    };

    t.getConfig = function()
    {
        return t.getAttribute('config');
    };

    t.setAttribute = function(key, value)
    {
        attributes[key] = value;
    };


    t.unload = function()
    {

    };

    t.load = function()
    {
        var result = _Ajax.syncPost('ajax_project_getproject', {
            name : t._name,
            lang : t._lang
        });

        for (k in result) {
            t.setAttribute(k, result[k]);
        };
    };

    t.firstChild = function(async)
    {
        if (FirstChild == null) {
            FirstChild = t.getSite( 1 );
        };

        return FirstChild;
    };


    t.async =
    {
        firstChild : function(async)
        {
            if (typeof async != 'function') {
                alert('Konnte Asyncronen request nicht ausführen. Project.async.firstChild : function(async); param async fehlt');
            };

            _pcsg.Ajax.asyncPost('ajax_site_getsite', function(result, Ajax)
            {
                Ajax.getAttribute('async')(result, Ajax);
            }, {
                id        : 1,
                lang    : t.getAttribute('lang'),
                project : t.getAttribute('name'),
                async   : async
            });
        },

        getSite : function(async, id)
        {
            if (typeof async != 'function') {
                alert('Konnte Asyncronen request nicht ausführen. Project.async.getSite : function(asyncm id); param async fehlt');
            };

            _pcsg.Ajax.asyncPost('ajax_site_getsite', function(result, Ajax)
            {
                Ajax.getAttribute('async')(result, Ajax);
            }, {
                id        : id,
                lang    : t.getAttribute('lang'),
                project : t.getAttribute('name'),
                async   : async
            });
        },

        load : function(async)
        {
            if (typeof async != 'function') {
                alert('Konnte Asyncronen request nicht ausführen. Project.async.load : function(async); param async fehlt');
            };

            _pcsg.Ajax.syncPost('ajax_project_getproject', function(result, Ajax)
            {
                var Project = Ajax.getAttribute('Project');

                for (k in result) {
                    Project.setAttribute(k, result[k]);
                };

                Ajax.getAttribute('async')(result, Ajax);
            }, {
                lang    : t.getAttribute('lang'),
                name    : t.getAttribute('name'),
                async   : async,
                Project : t
            });
        }
    };

    t.openSite = function(Itm)
    {
        if (oTabbar == null)
        {
            oTabbar = new _ptools.Toolbar({
                name  : '_Tabbar',
                width : $('main-panel-tabbar').offsetWidth-50 +'px'
            });

            $('main-panel-tabbar').appendChild(
                oTabbar.create()
            );
        };

        if (oToolbar == null)
        {
            oToolbar = new _ptools.Toolbar({
                name  : '_Toolbar',
                slide : false
            });

            $('main-panel-toolbar').appendChild(
                oToolbar.create()
            );
        };

        oTabbar.clear();
        oToolbar.clear();

        var Site = t.getSite(
            Itm.getAttribute('value')
        );

        _Site = Site;

        //_Site.ajax();
        //_Site.onload();

        Site.loadTabs(function(result, Ajax)
        {
            var Site    = Ajax.getAttribute('Site');
            var Project = Site.getProject();

            Project.setSiteTabs(result, Site);

            Site.loadButtons(function(result, Ajax)
            {
                var Site    = Ajax.getAttribute('Site');
                var Project = Site.getProject();

                Project.setSiteButtons( result );

                _pcsg.Controls.Loader.stop();
            });
        });
    };

    t.getSite = function(id)
    {
        return new _pcsg.classes.Site(this, id);
    };

    t.setSiteTabs = function(tabs, Site)
    {
        oTabbar.clear();

        for (var i = 0, len = tabs.length; i < len; i++)
        {
            tabs[i]['Site'] = Site;

            oTabbar.appendChild(
                new _ptools.ToolbarTab(
                    tabs[i]
                )
            );
        };

        oTabbar.firstChild().onclick();
    };

    t.setSiteButtons = function(buttons, Site)
    {
        oToolbar.clear();

        for (var i = 0, len = buttons.length; i < len; i++)
        {
            buttons[i]['Site'] = Site;

            if (buttons[i]['name'] == '_sep')
            {
                oToolbar.appendChild(
                    new _ptools.ButtonSeperator(
                        buttons[i]
                    )
                );

                continue;
            };

            buttons[i]['onmouseover'] = function(Btn)
            {
                if (!Btn.getAttribute('help')) {
                    return;
                };

                _pcsg.Helper.showText( Btn.getAttribute('help') );
            };

            buttons[i]['onmouseout'] = function(Btn)
            {
                if (!Btn.getAttribute('help')) {
                    return;
                };

                _pcsg.Helper.clearText();
            };

            oToolbar.appendChild(
                new _ptools.Button(
                    buttons[i]
                )
            );
        };
    };


    t.__construct(name, lang);
};


/*
Project.prototype.typeWindow = function( func, sType )
{
    var t = this;
    _TypeWindow = new _ptools.Window({
        title  : 'Seitentyp &auml;ndern',
        name   : '_TypeWindow',
        body   : '',
        height : 450,
        width  : 300,
        image  : URL_BIN_DIR +'16x16/edit.png'
    });
    _TypeWindow.create();

    var types = _Ajax.syncPost('ajax_project_gettypes', {
        lang : t.getAttribute('lang'),
        name : t.getAttribute('name')
    });

    this._typemap = new _ptools.Sitemap({
        name: 'typemap'
    });

    var _ProjectTypes = new _ptools.SitemapItem({
        name : '_ProjectTypes',
        text : 'Seitentypen',
        icon : URL_BIN_DIR +'16x16/types.png'
    });
    this._typemap.appendChild( _ProjectTypes );
    _ProjectTypes.setDisabled();

    // Standardtyp
    var _st = new _ptools.SitemapItem({
        name : 'standard',
        text : 'standard',
        type : 'standard',
        icon : URL_BIN_DIR +'16x16/types.png'
    });
    _ProjectTypes.appendChild( _st );

    if (sType == null || sType == 'standard' || !sType) {
        _st.onclick();
    };

    for (type in types)
    {
        if (types[type] && types[type]['types'])
        {
            var tp = types[type];

            var _t = new _ptools.SitemapItem({
                name : tp['name'],
                text : tp['name'],
                type : type,
                icon : tp['icon_16x16'] ? URL_DIR + tp['icon_16x16'] : URL_BIN_DIR +'16x16/types.png'
            });

            _ProjectTypes.appendChild( _t );
            _t.setDisabled();

            for (i in tp['types'])
            {
                var tp2 = tp['types'][i];

                var _t2 = new _ptools.SitemapItem({
                    name : tp2['name'],
                    text : tp2['name'],
                    type : type +'/'+ i,
                    icon : tp2['icon_16x16'] ? URL_DIR + tp2['icon_16x16'] : URL_BIN_DIR +'16x16/types.png'
                });
                _t.appendChild( _t2 );

                if (sType == tp2[i]) {
                    _t.onclick();
                };
            };
        };
    };

    var oSitemap    = document.createElement('div');
    var oButtons    = oSitemap.cloneNode(true);
    var oButtonsSub = oSitemap.cloneNode(true);

    var style = oSitemap.style;
    style.height    = (_TypeWindow.oDivBody.offsetHeight-35) +'px';
    style.overflowX = 'auto';
    style.position  = 'relative';
    oSitemap.appendChild( this._typemap.create() );

    style = oButtons.style;
    style.height      = '30px';
    style.textAlign   = 'center';
    style.marginLeft  = 'auto';
    style.marginRight = 'auto';
    style.width       = '95%';

    oButtons.align           = 'center';
    oButtons.style.borderTop = '1px solid #7c7c7c';

    style = oButtonsSub.style;
    style.width       = '130px';
    style.marginLeft  = 'auto';
    style.marginRight = 'auto';
    style.marginTop   = '3px';


    var _ok = new _ptools.Button({
        name    : '_ok',
        onclick : func,
        text    : 'OK'
    });

    var _cancel = new _ptools.Button({
        name    : '_ok',
        onclick : '_TypeWindow.close',
        text    : 'Abbrechen'
    });

    oButtonsSub.appendChild( _ok.create() );
    oButtonsSub.appendChild( _cancel.create() );
    oButtons.appendChild(oButtonsSub);

    _TypeWindow.oDivBody.appendChild( oSitemap );
    _TypeWindow.oDivBody.appendChild( oButtons );

    _ProjectTypes.open();
    this._typemap.obj.style.paddingLeft = '10px';

    this._typemap.openAll();
};

Project.prototype.goToId = function( id )
{
    var parents = this.getParentIds( id );
    _fc         = _Sitemap.firstChild();

    _fc.close();
    _fc.open();

    if (parents && parents.length)
    {
        for (var i = 0, len = parents.length; i < len; i++)
        {
            _fc = _fc.getChildByValue( parents[i] );

            if (_fc) {
                _fc.open();
            };
        };
    };

    _c = _fc.getChildByValue(id);
    _c.onclick();
};


Project.prototype.getParentIds = function(id)
{
    var parents = _Ajax.syncPost('ajax_project_getParentIds', {
        id        : id,
        lang    : this.getAttribute('lang'),
        project : this.getAttribute('name')
    });

    return parents;
};

Project.prototype.getSheet = function( parentid, childid )
{
    var sheet = _Ajax.syncPost('ajax_site_getsheet', {
        project  : this.getAttribute('name'),
        lang     : this.getAttribute('lang'),
        parentid : parentid,
        id       : childid
    });

    return sheet;
};

Project.prototype.getChildrenFromId = function(id)
{
    var children = _Ajax.syncPost('ajax_site_getchildren', {
        id             : id,
        lang         : this.getAttribute('lang'),
        project_name : this.getAttribute('name')
    });

    return children;
};
*/
