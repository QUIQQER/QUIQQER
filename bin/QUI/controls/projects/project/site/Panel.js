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
 */

define('controls/projects/project/site/Panel', [

    'qui/controls/desktop/Panel',
    'Projects',
    'Ajax',
    'classes/projects/project/Site',
    'qui/controls/buttons/Button',
    'qui/utils/Form',
    'utils/Controls',

    'css!controls/projects/project/site/Panel.css'

], function(QUIPanel, Projects, Ajax, Site, QUIButton, QUIFormUtils, ControlUtils)
{
    "use strict";

    /**
     * An SitePanel, opens the Site in an Apppanel
     *
     * @class controls/projects/project/site/Panel
     *
     * @param {classes/projects/Site} Site
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/site/Panel',

        Binds : [
            'load',
            'createNewChild',
            'openPermissions',
            'openMedia',

            '$onCreate',
            '$onResize',
            '$onCategoryEnter',
            '$onCategoryLeave',
            '$onEditorLoad',
            '$onEditorDestroy',
            '$onPanelButtonClick',

            '$onSiteActivate',
            '$onSiteDeactivate',
            '$onSiteSave',
            '$onSiteDelete'
        ],

        options : {
            id        : 'projects-site-panel',
            container : false
        },

        initialize : function(Site, options)
        {
            var Project = Site.getProject(),

                id = 'panel-'+
                     Project.getName() +'-'+
                     Project.getLang() +'-'+
                     Site.getId();

            // default id
            this.setAttribute( 'id', id );
            this.setAttribute( 'name', id );

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
         * @method controls/projects/project/site/Panel#serialize
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
         * @method controls/projects/project/site/Panel#unserialize
         * @param {Object} data
         * @return {this}
         */
        unserialize : function(data)
        {
            this.setAttributes( data.attributes );

            var Project = Projects.get(
                data.project,
                data.lang
            );

            this.$Site = Project.get( data.id );

            return this;
        },

        /**
         * Return the Site object from the panel
         *
         * @method controls/projects/project/site/Panel#getSite
         * @return {classes/projects/Site}
         */
        getSite : function()
        {
            return this.$Site;
        },

        /**
         * Load the site attributes to the panel
         *
         * @method controls/projects/project/site/Panel#load
         */
        load : function()
        {
            var title   = '',
                Site    = this.getSite(),
                Project = Site.getProject();

            title = title + Project.getName();
            title = title + ' - '+ Site.getAttribute( 'name' ) +' ('+ Site.getId() +')';

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
         * @method controls/projects/project/site/Panel#$onCreate
         */
        $onCreate : function()
        {
            this.Loader.show();

            // permissions
            var PermissionButton = new QUIButton({
                image  : 'icon-gears',
                alt    : 'Seiten Zugriffsrechte einstellen',
                title  : 'Seiten Zugriffsrechte einstellen',
                styles : {
                    'float' : 'right'
                },
                events : {
                    onClick : this.openPermissions
                }
            }).inject(
                this.getHeader()
            );

            var MediaButton = new QUIButton({
                image  : 'icon-picture',
                alt    : 'Media',
                title  : 'Media',
                styles : {
                    'float' : 'right'
                },
                events : {
                    onClick : this.openMedia
                }
            }).inject(
                this.getHeader()
            );

            var Site    = this.getSite(),
                Project = Site.getProject();

            Ajax.get([
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

                    Category = new QUIButton( category );

                    Category.addEvents({
                        onActive : Panel.$onCategoryEnter
                        //onNormal : Panel.$onCategoryLeave -> trigger in onActive
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

                Site.addEvents({
                    onLoad       : Panel.load,
                    onActivate   : Panel.$onSiteActivate,
                    onDeactivate : Panel.$onSiteDeactivate,
                    onSave       : Panel.$onSiteSave,
                    onDelete     : Panel.$onSiteDelete
                });

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
         * @method controls/projects/project/site/Panel#$onResize
         */
        $onResize : function()
        {

        },

        /**
         * Opens the site permissions
         *
         * @method controls/projects/project/site/Panel#openPermissions
         */
        openPermissions : function()
        {
            var Parent = this.getParent(),
                Site   = this.getSite();

            require([ 'controls/permissions/Panel' ], function(PermPanel)
            {
                Parent.appendChild(
                    new PermPanel( null, Site )
                );
            });
        },

        /**
         * Opens the site media
         *
         * @method controls/projects/project/site/Panel#openMedia
         */
        openMedia : function()
        {
            var Parent  = this.getParent(),
                Site    = this.getSite(),
                Project = Site.getProject(),
                Media   = Project.getMedia();

            require([ 'controls/projects/project/media/Panel' ], function(Panel) {
                Parent.appendChild( new Panel( Media ) );
            });
        },

        /**
         * saves site attributes
         *
         * @method controls/projects/project/site/Panel#openPermissions
         */
        save : function()
        {
            this.$onCategoryLeave( this.getActiveCategory() );
            this.getSite().save();
        },

        /**
         * opens the delet dialog
         */
        del : function()
        {
            var Site = this.getSite();

            require(['qui/controls/windows/Confirm'], function(Confirm)
            {
                new Confirm({
                    title       : 'Seite #'+ Site.getId() +' löschen',
                    titleicon   : 'icon-trash',
                    text        : 'Möchten Sie die Seite #'+ Site.getId() +' '+ Site.getAttribute( 'name' ) +'.html wirklich löschen?',
                    information :
                        'Die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden.' +
                        'Auch alle Unterseiten und Verknüpfungen werden in den Papierkorb gelegt.',
                    height : 200,
                    events :
                    {
                        onSubmit : function(Win) {
                            Site.del();
                        }
                    }
                }).open();
            });

        },

        /**
         * Create a child site
         *
         * @method controls/projects/project/site/Panel#createChild
         *
         * @param {String} newname - [optional, if no newname was passed,
         *         a window would be open]
         */
        createNewChild : function()
        {
            var self = this,
                Site = self.getSite()

            require(['qui/controls/windows/Prompt'], function(Prompt)
            {
                new Prompt({
                    title       : 'Wie soll die neue Seite heißen?',
                    text        : 'Bitte geben Sie ein Namen für die neue Seite an',
                    texticon    : 'icon-file',
                    information : 'Sie legen eine neue Seite unter '+ Site.getAttribute('name') +'.html an.',
                    events      :
                    {
                        onSubmit : function(result, Win) {
                            Site.createChild( result );
                        }
                    }
                }).open();
            });
        },

        /**
         * Enter the Tab / Category
         * Load the tab content and set the site attributes
         * or exec the plugin event
         *
         * @method controls/projects/project/site/Panel#$tabEnter
         * @fires onSiteTabLoad
         *
         * @param {qui/controls/toolbar/Button} Category
         */
        $onCategoryEnter : function(Category)
        {
            this.Loader.show();

            if ( this.getActiveCategory() ) {
                this.$onCategoryLeave( this.getActiveCategory() );
            }


            if ( Category.getAttribute( 'name' ) == 'content' )
            {
                this.loadEditor(
                    this.getSite().getAttribute( 'content' )
                );

                return;
            }

            if ( !Category.getAttribute( 'template' ) )
            {
                this.getContent().set( 'html', '' );
                this.$categoryOnLoad( Category );

                return;
            }

            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            Ajax.get('ajax_site_categories_template', function(result, Request)
            {
                var Body = self.getContent();

                if ( !result )
                {
                    Body.set( 'html', '' );
                    self.$categoryOnLoad( Category );

                    return;
                }

                var Form;

                Body.set( 'html', '<form>'+ result +'</form>' );

                Form = Body.getElement( 'form' );
                Form.addEvent('submit', function(event) {
                    event.stop();
                });

                QUIFormUtils.setDataToForm(
                    self.getSite().getAttributes(),
                    Form
                );

                // information tab
                if ( Category.getAttribute( 'tab' ) === 'information' )
                {
                    var Input = Body.getElements( 'input[name="site-name"]' );

                    //Input.focusToBegin();
                    Input.set( 'value', Site.getAttribute( 'name' ) );
                }

                ControlUtils.parse( Form );

                self.$categoryOnLoad( Category );

            }, {
                id      : Site.getId(),
                project : Project.getName(),
                lang    : Project.getLang(),
                tab     : Category.getAttribute( 'name' )
            });
        },

        /**
         * Load the category
         *
         * @method controls/projects/project/site/Panel#$categoryOnLoad
         * @param {qui/controls/buttons/Button} Category
         */
        $categoryOnLoad : function(Category)
        {
            var self = this;

            if ( Category.getAttribute( 'onload_require' ) )
            {
                require([
                    Category.getAttribute( 'onload_require' )
                ], function(Plugin)
                {
                    eval( Category.getAttribute( 'onload' ) +'( Category, self );' );
                });

                return;
            }

            this.Loader.hide();
        },

        /**
         * The site tab leave event
         *
         * @method controls/projects/project/site/Panel#$tabLeave
         * @fires onSiteTabUnLoad
         *
         * @param {qui/controls/buttons/Button} Category
         */
        $onCategoryLeave : function(Category)
        {
            this.Loader.show();

            var Site  = this.getSite(),
                Body  = this.getBody();

            if ( Category.getAttribute( 'name' ) === 'content' )
            {
                Site.setAttribute(
                    'content',
                    this.getAttribute( 'Editor' ).getContent()
                );

                this.Loader.hide();
                return;
            }

            if ( !Body.getElement( 'form' ) ) {
                return;
            }

            var Form     = Body.getElement( 'form' ),
                elements = Form.elements;

            // information tab
            if ( Category.getAttribute( 'name' ) === 'information' )
            {
                Site.setAttribute( 'name', elements['site-name'].value );
                Site.setAttribute( 'title', elements.title.value );
                Site.setAttribute( 'short', elements.short.value );
                Site.setAttribute( 'nav_hide', elements.nav_hide.checked );
                Site.setAttribute( 'type', elements.type.value );

                return;
            }

            var self = this;

            if ( Category.getAttribute( 'onunload_require' ) )
            {
                require([
                    Category.getAttribute( 'onunload_require' )
                ], function(Plugin)
                {
                    eval( Category.getAttribute( 'onunload' ) +'( Category, self );' );
                });

                return;
            }
        },

        /**
         * Execute the panel onclick from PHP
         *
         * @method controls/projects/project/site/Panel#$onPanelButtonClick
         * @param {qui/controls/buttons/Button} Btn
         */
        $onPanelButtonClick : function(Btn)
        {
            var Panel = this;

            eval( Btn.getAttribute( '_onclick' ) +'();' );
        },

        /**
         * Site event methods
         */

        $onSiteSave : function()
        {
            this.Loader.hide();
        },

        /**
         * event : on {classes/projects/Site} activation
         */
        $onSiteActivate : function()
        {
            var Status = this.getButtons( 'status' );

            if ( !Status ) {
                return;
            }

            Status.setAttributes({
                'textimage' : Status.getAttribute( 'dimage' ),
                'text'      : Status.getAttribute( 'dtext' ),
                '_onclick'  : 'Panel.getSite().deactivate'
            });
        },

        /**
         * event : on {classes/projects/Site} deactivation
         */
        $onSiteDeactivate : function()
        {
            var Status = this.getButtons( 'status' );

            if ( !Status ) {
                return;
            }

            Status.setAttributes({
                'textimage' : Status.getAttribute( 'aimage' ),
                'text'      : Status.getAttribute( 'atext' ),
                '_onclick'  : 'Panel.getSite().activate'
            });
        },

        /**
         * event : on {classes/projects/Site} delete
         */
        $onSiteDelete : function()
        {
            this.destroy();
        },

        /**
         * Editor (WYSIWYG) Methods
         */

        /**
         * Load the WYSIWYG Editor in the panel
         *
         * @method controls/projects/project/site/Panel#loadEditor
         * @param {String} content - content of the editor
         */
        loadEditor : function(content)
        {
            var self = this,
                Body = this.getBody();

            Body.set( 'html', '' );

            require(['Editors'], function(Editors)
            {
                Editors.getEditor(null, function(Editor)
                {
                    var Site    = self.getSite(),
                        Project = Site.getProject();

                    self.setAttribute( 'Editor', Editor );

                    // draw the editor
                    Editor.setAttribute( 'Panel', self );
                    Editor.setAttribute( 'name', Site.getId() );
                    Editor.addEvent( 'onDestroy', self.$onEditorDestroy );

                    // set the site content
                    if ( typeof content === 'undefined' || !content ) {
                        content = '';
                    }

                    Editor.inject( Body );
                    Editor.setContent( content );
                    Editor.addEvent( 'onLoaded', self.$onEditorLoad )
                });
            });
        },

        /**
         * event: on editor load
         * if the editor is finished
         *
         * @method controls/projects/project/site/Panel#$onEditorLoad
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
         * @method controls/projects/project/site/Panel#$onEditorDestroy
         * @param Editor
         */
        $onEditorDestroy : function(Editor)
        {
            this.setAttribute( 'Editor', false );
        }
    });
});