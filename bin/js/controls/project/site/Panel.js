/**
 * Displays a Site in a Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires lib/Sites
 * @requires classes/project/Site
 *
 * @module controls/project/site/Panel
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.project.site
 */

define('controls/project/site/Panel', [

    'controls/desktop/Panel',
    'lib/Sites',
    'classes/project/Site'

], function(QUI_Panel, QUI_Sites, QUI_Site)
{
    QUI.namespace('controls.project.site');

    /**
     * An SitePanel, opens the Site in an Apppanel
     *
     * @class QUI.controls.project.site.Panel
     *
     * @param {QUI.classes.project.Site} Site
     * @param {Object} options
     */
    QUI.controls.project.site.Panel = new Class({

        Implements : [ QUI_Panel ],
        Type       : 'QUI.controls.project.site.Panel',

        Binds : [
            'load',
            '$onCreate',
            '$onCategoryEnter',
            '$onCategoryLeave',
            '$onEditorLoad',
            '$onEditorDestroy'
        ],

        options : {
            id        : 'projects-site-panel',
            container : false
        },

        initialize : function(Site, options)
        {
            // default id
            this.setAttribute( 'id', 'projects-site-'+ Site.getId() +'-panel' );
            this.setAttribute( 'name', 'projects-site-'+ Site.getId() +'-panel' );

            this.init( options );

            this.$Site = Site;

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * Return the Site object from the panel
         *
         * @return {QUI.classes.project.Site}
         */
        getSite : function()
        {
            return this.$Site;
        },

        /**
         * Load the site attributes to the panel
         */
        load : function()
        {
            var title   = '',
                Site    = this.getSite(),
                Project = Site.getProject();

            title = title + Project.getName();
            title = title +' - '+ Site.getAttribute( 'name' ) +' ('+ Site.getId() +')';

            this.setAttributes({
                title : title,
                icon  : URL_BIN_DIR +'16x16/flags/'+ Project.getLang() +'.png'
            });

            this.refresh();


            if ( this.getActiveCategory() )
            {
                this.getActiveCategory().click();
            } else
            {
                this.getCategoryBar().firstChild().click();
            }
        },

        /**
         * Create the panel design
         *
         * @method QUI.controls.project.site.Panel#create
         */
        $onCreate : function()
        {
            this.Loader.show();

            var Site    = this.getSite(),
                Project = Site.getProject();

            QUI.Ajax.get([
                'ajax_site_categories_get',
                'ajax_site_buttons_get'
            ], function(categories, buttons, Request)
            {
                var i, len;

                var Panel = Request.getAttribute( 'Control' ),
                    Site  = Panel.getSite();

                for ( i = 0, len = buttons.length; i < len; i++ ) {
                    Panel.addButton( buttons[ i ] );
                }

                for ( i = 0, len = categories.length; i < len; i++ )
                {
                    categories[ i ].events = {
                        onActive : Panel.$onCategoryEnter,
                        onNormal : Panel.$onCategoryLeave
                    };

                    Panel.addCategory( categories[ i ] );
                }

                Site.addEvent( 'onRefresh', Panel.load );

                if ( Site.getAttribute('name') )
                {
                    Panel.load();
                } else
                {
                    Site.load();
                }

            }, {
                project : Project.getName(),
                lang    : Project.getLang(),
                id      : Site.getId(),
                Control : this
            });
        },



        /**
         * Load the Site and the Tabs to the Panel
         *
         * @method QUI.controls.project.site.Panel#load
         */
        /*
        load : function()
        {
            this.$Panel.Loader.show();

            var Panel   = this.$Panel,
                Tabs    = Panel.Tabs,
                Project = this.$Site.getProject(),
                Site    = this.$Site;

            Tabs.clear();

            Panel.setAttribute( 'Project', Project );
            Panel.setAttribute( 'Site', Site );
            Panel.setAttribute( 'Control', this );

            // Site Events
            Site.addEvent('onStatusEditBegin', function(Site)
            {
                Panel.Loader.show();

                var Status = Panel.Buttons.getElement('status');
                Status.setAttribute('textimage',  URL_BIN_DIR +'images/loader.gif');
            });

            Site.addEvent('onStatusEditEnd', function(Site)
            {
                // Aktive / Deaktiv Button
                var Status = Panel.Buttons.getElement('status');

                if ( Site.getAttribute('active') )
                {
                    Status.setAttribute( 'textimage', Status.getAttribute('dimage') );
                    Status.setAttribute( 'text', Status.getAttribute('dtext') );
                    Status.setAttribute( 'onclick', Status.getAttribute('donclick') );
                } else
                {
                    Status.setAttribute( 'textimage', Status.getAttribute('aimage') );
                    Status.setAttribute( 'text', Status.getAttribute('atext') );
                    Status.setAttribute( 'onclick', Status.getAttribute('aonclick') );
                }

                // adapt all projects panels
                var i, len, c, clen, items;

                var panels = QUI.lib.Sites.getProjectPanels( Site ),
                    id     = Site.getId();

                for ( i = 0, len = panels.length; i < len; i++ )
                {
                    items = panels[i].getSitemapItemsById( id );

                    for ( c = 0, clen = items.length; c < clen; c++ )
                    {
                        if ( Site.getAttribute('active') )
                        {
                            items[c].activate();
                        } else
                        {
                            items[c].deactivate();
                        }

                        // @todo check what config is set in the project for the sitemap
                        // @todo set sitetype for the map item
                        // @todo set nav hide for the map item
                        items[c].setAttribute( 'text', Site.getAttribute('title') );
                    }
                }

                Panel.Loader.hide();
            });

            // Site data
            Site.load(function(Site, Ajax)
            {
                var title = '',
                    Panel = Ajax.getAttribute('Panel');

                title = title + Ajax.getAttribute('project');
                title = title +' - '+ Site.getAttribute('name') +' ('+ Site.getId() +')';

                Panel.setOptions({
                    title : title,
                    icon  : URL_BIN_DIR +'16x16/flags/'+ Ajax.getAttribute('lang') +'.png'
                });

                Panel.refresh();

                // tabs bekommen
                QUI.Ajax.get('ajax_site_gettabs', function(result, Ajax)
                {
                    var i, len, func_on_enter, func_on_leave;

                    var Panel = Ajax.getAttribute('Panel'),
                        Tabs  = Panel.Tabs;

                    Tabs.clear();

                    // events
                    func_on_enter = function(Tab)
                    {
                        Tab.getAttribute('Panel')
                           .getAttribute('Control')
                           .$tabEnter( Tab );
                    };

                    func_on_leave = function(Tab)
                    {
                        Tab.getAttribute('Panel')
                           .getAttribute('Control')
                           .$tabLeave( Tab );
                    };

                    // create
                    for (i = 0, len = result.length; i < len; i++)
                    {
                        Tabs.appendChild(
                            new QUI.controls.toolbar.Tab(

                                QUI.lib.Utils.combine(result[i], {
                                    Panel  : Panel,
                                    Site   : Panel.getAttribute('Site'),
                                    events :
                                    {
                                        onEnter : func_on_enter,
                                        onLeave : func_on_leave
                                    }
                                })

                            )
                        );
                    }

                    Tabs.firstChild().click();

                    // Buttons laden
                    QUI.Ajax.get('ajax_site_getbuttons', function(result, Ajax)
                    {
                        var i, len;
                        var Panel = Ajax.getAttribute('Panel');

                        Panel.clearButtons();

                        for (i = 0, len = result.length; i < len; i++) {
                            Panel.addButton( result[i] );
                        }

                        Panel.Loader.hide();

                    }, {
                        Panel   : Panel,
                        project : Ajax.getAttribute('project'),
                        lang    : Ajax.getAttribute('lang'),
                        id      : Ajax.getAttribute('id')
                    });
                }, {
                    Panel   : Panel,
                    project : Ajax.getAttribute('project'),
                    lang    : Ajax.getAttribute('lang'),
                    id      : Ajax.getAttribute('id')
                });

            }, {
                Panel   : Panel,
                project : Project.getAttribute('name'),
                lang    : Project.getAttribute('lang'),
                id      : Site.getId()
            });
        },

        unload : function()
        {
            var Panel  = this.$Panel,
                Tabs   = Panel.Tabs,
                Active = Tabs.getActive();

            this.$tabLeave( Active );
        },
        */
        /**
         * Enter the Tab / Category
         * Load the tab content and set the site attributes
         * or exec the plugin event
         *
         * @method QUI.controls.project.site.Panel#$tabEnter
         * @fires onSiteTabLoad
         *
         * @param {QUI.controls.toolbar.Button} Button
         */
        $onCategoryEnter : function(Button)
        {
            this.Loader.show();

            if ( Button.getAttribute('name') == 'content' )
            {
                this.loadEditor(
                    this.getSite().getAttribute('content')
                );

                return;
            }

            var Site    = this.getSite(),
                Project = Site.getProject();

            QUI.Ajax.get('ajax_site_categories_template', function(result, Request)
            {
                var Panel    = Request.getAttribute( 'Panel' ),
                    Category = Request.getAttribute( 'Category' ),
                    Body     = Panel.getBody();


                if ( !result )
                {
                    Body.set( 'html', '' );
                    Panel.$categoryOnLoad( Category );

                    return;
                }

                var Form;

                Body.set( 'html', '<form>'+ result +'</form>' );

                Form = Body.getElement( 'form' );
                Form.addEvent('submit', function(event) {
                    event.stop();
                });

                QUI.lib.Utils.setDataToForm(
                    Panel.getSite().getAttributes(),
                    Form
                );

                // information tab
                if ( Request.getAttribute('tab') === 'information' )
                {
                    Body.getElement( 'input[name="site-name"]' ).focusToBegin();

                    // set params
                    Form.elements['site-name'].value  = Site.getAttribute( 'name' );
                }

                QUI.controls.Utils.parse( Form );

                Panel.$categoryOnLoad( Category );

            }, {
                id      : Site.getId(),
                project : Project.getName(),
                lang    : Project.getLang(),

                tab     : Button.getAttribute('name'),

                Category : Button,
                Panel    : this
            });



            return;

            var Panel   = Tab.getAttribute('Panel'),
                Site    = Panel.getAttribute('Site'),
                Project = Panel.getAttribute('Project');

            Panel.Loader.show();

            // if editor exist, destroy it, so we get no problems
            if (Panel.getAttribute('Editor')) {
                Panel.getAttribute('Editor').destroy();
            }

            // loading editor if content tab
            if (Tab.getAttribute('name') == 'content')
            {
                var Body      = Panel.getBody(),
                    Container = new Element('textarea#editor'+ Panel.getId(), {
                        name    :' editor'+ Panel.getId(),
                        styles  : {
                            width  : Body.getSize().x - 40,
                            height : Body.getSize().y - 40
                        }
                    });

                Body.set('html', '');
                Container.inject( Body );

                new Element('div', {
                    styles : {
                        margin : 10
                    }
                }).wraps( Container );


                QUI.lib.Editor.getEditor('package/ckeditor3', function(Editor)
                {
                    var Site    = this.getAttribute('Site'),
                        Project = this.getAttribute('Project');

                    this.setAttribute('Editor', Editor);

                    // draw the editor
                    Editor.setAttribute( 'Panel', this );
                    Editor.setAttribute( 'name', Site.getId() );
                    Editor.addEvent('onDestroy', function(Editor)
                    {
                        this.setAttribute( 'Editor', false );
                    }.bind( this ));

                    // set the site content
                    if (Site.getAttribute('content') === '' ||
                        Site.getAttribute('content') === false)
                    {
                        Editor.setContent('');
                    } else
                    {
                        Editor.setContent( Site.getAttribute('content') );
                    }

                    Editor.addEvent('onLoaded', function(Editor, Instance)
                    {
                        if (this.getAttribute('Panel')) {
                            Panel.Loader.hide();
                        }
                    });

                    Editor.draw( Body );
                }.bind( Panel ));

                return;
            }

            // andere Tabs laden
            QUI.Ajax.get('ajax_site_tab_template', function(result, Ajax)
            {
                var Panel = Ajax.getAttribute('Panel'),
                    Tab   = Ajax.getAttribute('Tab'),
                    Body  = Panel.getBody();

                if ( !result.tpl )
                {
                    Body.set('html', '');
                    Panel.Loader.hide();
                    return;
                }

                Body.set('html', '<form>'+ result.tpl +'</form>');

                var FormElm = Body.getElement('form');

                FormElm.set({
                    events :
                    {
                        submit : function(event) {
                            event.stop();
                        }
                    },

                    styles : {
                        margin: 20
                    }
                });

                QUI.lib.Utils.setDataToForm(
                    Site.getAttributes(),
                    FormElm
                );

                // informations tab
                if (Tab.getAttribute('name') === 'information')
                {
                    Body.getElement('input[name="site-name"]').focusToBegin();

                    // set params
                    FormElm.elements['site-name'].value  = Site.getAttribute('name');

                    QUI.lib.Controls.parse( Body.getElement('form') );

                    Panel.Loader.hide();
                    return;
                }

                QUI.lib.Controls.parse( Body.getElement('form') );

                // onload vom Plugin
                if (result.plugin)
                {
                    QUI.lib.Plugins.get(result.plugin, function(Plgn)
                    {
                        Plgn.fireEvent('siteTabLoad', [this]);
                    }.bind( Tab ));
                }

                Panel.Loader.hide();

            }, {
                Tab     : Tab,
                Panel   : Panel,

                id      : Site.getId(),
                project : Project.getAttribute('name'),
                lang    : Project.getAttribute('lang'),
                tab     : Tab.getAttribute('name')
            });
        },

        /**
         * Load the category
         *
         * @param {QUI.controls.buttons.Button} Category
         */
        $categoryOnLoad : function(Category)
        {
            if ( Category.getAttribute( 'onload_require' ) )
            {
                require([
                    Category.getAttribute( 'onload_require' )
                ], function(Plugin)
                {
                    eval( Category.getAttribute( 'onload' ) +'( Category, this );' );
                }.bind( this ));

                return;
            }

            this.Loader.hide();
        },

        /**
         * The site tab leave event
         *
         * @method QUI.controls.project.site.Panel#$tabLeave
         * @fires onSiteTabUnLoad
         *
         * @param {QUI.controls.toolbar.Tab} Tab
         */
        $onCategoryLeave : function(Tab)
        {


            if ( Tab.getAttribute('name') === 'content' )
            {

            }


            return;


            var Panel = Tab.getAttribute('Panel'),
                Site  = Panel.getAttribute('Site'),
                Body  = Panel.getBody();

            Panel.Loader.show();

            // content unload editor
            if (Tab.getAttribute('name') === 'content')
            {
                Site.setAttribute(
                    'content',
                    Panel.getAttribute('Editor').getContent()
                );

                return;
            }

            // information tab
            if (Tab.getAttribute('name') === 'information')
            {
                var FormElm = Body.getElement('form');

                Site.setAttribute('name', FormElm.elements['site-name'].value);
                Site.setAttribute('title', FormElm.elements.title.value);
                Site.setAttribute('short', FormElm.elements.short.value);
                Site.setAttribute('nav_hide', FormElm.elements.nav_hide.checked);
                Site.setAttribute('type', FormElm.elements.type.value);

                return;
            }

            QUI.lib.Plugins.get(Tab.getAttribute('plugin'), function(Plgn)
            {
                if (Plgn) {
                    Plgn.fireEvent('siteTabUnload', [this]);
                }
            }.bind( Tab ));
        },

        /**
         * Load the WYSIWYG Editor in the panel
         *
         * @param {String} content - content of the editor
         */
        loadEditor : function(content)
        {
            var Body      = this.getBody(),

                Container = new Element('textarea#editor'+ this.getId(), {
                    name    :' editor'+ this.getId(),
                    styles  : {
                        width  : Body.getSize().x - 60,
                        height : Body.getSize().y - 40
                    }
                });

            Body.set( 'html', '' );
            Container.inject( Body );

            QUI.Editors.getEditor(null, function(Editor)
            {
                var Site    = this.getSite(),
                    Project = Site.getProject();

                this.setAttribute( 'Editor', Editor );

                // draw the editor
                Editor.setAttribute( 'Panel', this );
                Editor.setAttribute( 'name', Site.getId() );
                Editor.addEvent( 'onDestroy', this.$onEditorDestroy );

                // set the site content
                if ( typeof content === 'undefined' || !content ) {
                    content = '';
                }

                Editor.setContent( content );
                Editor.addEvent( 'onLoaded', this.$onEditorLoad );
                Editor.draw( Body );

            }.bind( this ));
        },

        /**
         * event: on editor load
         * if the editor is finished
         *
         * @param Editor
         * @param Instance
         */
        $onEditorLoad : function(Editor, Instance)
        {
            this.Loader.hide();
        },

        /**
         * event: on editor load
         * if the editor would be destroyed
         *
         * @param Editor
         */
        $onEditorDestroy : function(Editor)
        {
            this.setAttribute( 'Editor', false );
        }
    });

    return QUI.controls.project.site.Panel;
});