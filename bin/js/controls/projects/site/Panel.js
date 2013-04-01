/**
 * Displays a Site in a Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires lib/Sites
 * @requires classes/projects/Site
 *
 * @module controls/projects/site/Panel
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.projects.site
 */

define('controls/projects/site/Panel', [

    'controls/desktop/Panel',
    'Projects',
    'classes/projects/Site'

], function(QUI_Panel, QUI_Sites, QUI_Site)
{
    "use strict";

    QUI.namespace( 'controls.projects.site' );

    /**
     * An SitePanel, opens the Site in an Apppanel
     *
     * @class QUI.controls.projects.site.Panel
     *
     * @param {QUI.classes.projects.Site} Site
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.site.Panel = new Class({

        Extends : QUI_Panel,
        Type    : 'QUI.controls.projects.site.Panel',

        Binds : [
            'load',
            'createNewChild',
            'openPermissions',

            '$onCreate',
            '$onResize',
            '$onCategoryEnter',
            '$onCategoryLeave',
            '$onEditorLoad',
            '$onEditorDestroy',
            '$onPanelButtonClick'
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

            this.$Site = Site;

            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });
        },

        /**
         * Save the site panel to the workspace
         *
         * @method QUI.controls.projects.site.Panel#serialize
         * @return {Object} data
         */
        serialize : function()
        {
            var Site    = this.getSite(),
                Project = Site.getProject();

            return {
                attributes : this.getAttributes(),
                id         : this.getSite().getId(),
                lang       : Project.getLang(),
                project    : Project.getName()
            };
        },

        /**
         * import the saved data form the workspace
         *
         * @method QUI.controls.projects.site.Panel#unserialize
         * @param {Object} data
         * @return {this}
         */
        unserialize : function(data)
        {
            this.setAttributes( data.attributes );

            var Project = QUI.Projects.get(
                data.project,
                data.lang
            );

            this.$Site = Project.get( data.id );

            return this;
        },

        /**
         * Return the Site object from the panel
         *
         * @method QUI.controls.projects.site.Panel#getSite
         * @return {QUI.classes.projects.Site}
         */
        getSite : function()
        {
            return this.$Site;
        },

        /**
         * Load the site attributes to the panel
         *
         * @method QUI.controls.projects.site.Panel#load
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
         * @method QUI.controls.projects.site.Panel#$onCreate
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
                var i, ev, fn, len, events, category, Category,
                    Panel = Request.getAttribute( 'Control' );


                for ( i = 0, len = buttons.length; i < len; i++ )
                {
                    if ( buttons[ i ].onclick )
                    {
                        buttons[ i ]._onclick = buttons[ i ].onclick;
                        delete buttons[ i ].onclick;

                        buttons[ i ].events = {
                            onClick : Panel.$onPanelButtonClick
                        };
                    }

                    Panel.addButton( buttons[ i ] );
                }


                for ( i = 0, len = categories.length; i < len; i++ )
                {
                    events   = {};
                    category = categories[ i ];

                    if ( typeOf( category.events ) === 'object' )
                    {
                        events = category.events;
                        delete category.events;
                    }

                    Category = new QUI.controls.buttons.Button( category );

                    Category.addEvents({
                        onActive : Panel.$onCategoryEnter,
                        onNormal : Panel.$onCategoryLeave
                    });

                    for ( ev in events  )
                    {
                        try
                        {
                            eval( 'fn = '+ events[ ev ] );

                            Category.addEvent( ev, fn );

                        } catch ( e )
                        {
                            continue;
                        }
                    }

                    Panel.addCategory( Category );
                }

                Site.addEvent( 'onLoad', Panel.load );

                if ( Site.getAttribute( 'name' ) )
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
         * event: panel resize
         *
         * @method QUI.controls.projects.site.Panel#$onResize
         */
        $onResize : function()
        {

        },

        /**
         * Save the Site params to the Site
         *
         * @method QUI.controls.projects.site.Panel#save
         */
        save : function()
        {


            console.info( 'save' );
            console.log( this );
        },

        /**
         * Create a child site
         *
         * @method QUI.controls.projects.site.Panel#createChild
         *
         * @param {String} newname - [optional, if no newname was passed,
         *         a window would be open]
         */
        createNewChild : function()
        {
            var Panel = this;

            QUI.Windows.create('prompt', {
                title       : 'Wie soll die neue Seite heißen?',
                text        : 'Bitte geben Sie ein Namen für die neue Seite an',
                texticon    : URL_BIN_DIR +'48x48/filenew.png',
                information : 'Sie legen eine neue Seite unter '+ this.getAttribute('name') +'.html an.',
                events      :
                {
                    onSubmit : function(result, Win) {
                        Panel.getSite().createChild( result );
                    }
                }
            });



            return;

            QUI.lib.Sites.createChild(
                function(result, Request)
                {
                    // open the site in the sitemap
                    var i, len, Panel, items;

                    var Site   = Request.getAttribute( 'Site' ),
                        id     = Site.getId(),
                        panels = QUI.lib.Sites.getProjectPanels( Site ),

                        func_close = function(Item) {
                            Item.close();
                        };

                    for ( i = 0, len = panels.length; i < len; i++ )
                    {
                        Panel = panels[ i ];

                        // if site is inb the map, it must be refreshed
                        items = Panel.getSitemapItemsById( id );

                        if ( items.length ) {
                            items.each( func_close );
                        }

                        panels[i].openSite( result.id );
                    }
                },
                this.ajaxParams(),
                {
                    name  : newname,
                    title : newname
                }
            );
        },

        /**
         * Enter the Tab / Category
         * Load the tab content and set the site attributes
         * or exec the plugin event
         *
         * @method QUI.controls.projects.site.Panel#$tabEnter
         * @fires onSiteTabLoad
         *
         * @param {QUI.controls.toolbar.Button} Button
         */
        $onCategoryEnter : function(Button)
        {
            this.Loader.show();

            if ( !Button.getAttribute( 'template' ) ) {
                return;
            }


            if ( Button.getAttribute( 'name' ) == 'content' )
            {
                this.loadEditor(
                    this.getSite().getAttribute( 'content' )
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

                QUI.Utils.setDataToForm(
                    Panel.getSite().getAttributes(),
                    Form
                );

                // information tab
                if ( Request.getAttribute('tab') === 'information' )
                {
                    var Input = Body.getElements( 'input[name="site-name"]' );

                    Input.focusToBegin();
                    Input.value = Site.getAttribute( 'name' );
                }

                QUI.controls.Utils.parse( Form );

                Panel.$categoryOnLoad( Category );

            }, {
                id      : Site.getId(),
                project : Project.getName(),
                lang    : Project.getLang(),
                tab     : Button.getAttribute( 'name' ),

                Category : Button,
                Panel    : this
            });
        },

        /**
         * Load the category
         *
         * @method QUI.controls.projects.site.Panel#$categoryOnLoad
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
         * @method QUI.controls.projects.site.Panel#$tabLeave
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
                var FormElm = Body.getElement( 'form' );

                Site.setAttribute( 'name', FormElm.elements['site-name'].value );
                Site.setAttribute( 'title', FormElm.elements.title.value );
                Site.setAttribute( 'short', FormElm.elements.short.value );
                Site.setAttribute( 'nav_hide', FormElm.elements.nav_hide.checked );
                Site.setAttribute( 'type', FormElm.elements.type.value );

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
         * Exceute the panel onclick from PHP
         *
         * @method QUI.controls.projects.site.Panel#$onPanelButtonClick
         * @param {QUI.controls.buttons.Button} Btn
         */
        $onPanelButtonClick : function(Btn)
        {
            var Panel = this;

            eval( Btn.getAttribute( '_onclick' ) +'();' );
        },

        /**
         * Opens the permissions for the site
         *
         * @method QUI.controls.projects.site.Panel#openPermissions
         */
        openPermissions : function()
        {
            var Body  = this.getBody(),
                Panel = this;

            Body.set( 'html', '' );

            QUI.Ajax.get('ajax_site_permissions_tpl', function(result, Request)
            {
                Body.set( 'html', result );

                QUI.controls.Utils.parse( Body );

                Panel.Loader.hide();
            });

        },

        /**
         * Load the WYSIWYG Editor in the panel
         *
         * @method QUI.controls.projects.site.Panel#loadEditor
         * @param {String} content - content of the editor
         */
        loadEditor : function(content)
        {
            var Body = this.getBody(),

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
         * @method QUI.controls.projects.site.Panel#$onEditorLoad
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
         * @method QUI.controls.projects.site.Panel#$onEditorDestroy
         * @param Editor
         */
        $onEditorDestroy : function(Editor)
        {
            this.setAttribute( 'Editor', false );
        }
    });

    return QUI.controls.projects.site.Panel;
});