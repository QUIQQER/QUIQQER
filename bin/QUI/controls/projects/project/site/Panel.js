/**
 * Displays a Site in a Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/controls/desktop/Panel
 * @requires Projects
 * @requires Ajax
 * @requires classes/projects/project/Site
 * @requires qui/controls/buttons/Button
 * @requires qui/utils/Form
 *
 * @module controls/projects/site/Panel
 */

define('controls/projects/project/site/Panel', [

    'qui/controls/desktop/Panel',
    'Projects',
    'Ajax',
    'classes/projects/project/Site',
    'qui/controls/buttons/Button',
    'qui/utils/Form',
    'utils/Controls',
    'utils/Panels',

    'css!controls/projects/project/site/Panel.css'

], function(QUIPanel, Projects, Ajax, Site, QUIButton, QUIFormUtils, ControlUtils, PanelUtils)
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
            'openSort',

            '$onCreate',
            '$onDestroy',
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
                onCreate  : this.$onCreate,
                onResize  : this.$onResize,
                onDestroy : this.$onDestroy
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
                this.$onCategoryEnter( this.getActiveCategory() );
                // .click();

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
                    'border-left-width' : 1,
                    'float' : 'right'
                },
                events : {
                    onClick : this.openPermissions
                }
            }).inject( this.getHeader() );

            var MediaButton = new QUIButton({
                image  : 'icon-picture',
                alt    : 'Media',
                title  : 'Media',
                styles : {
                    'border-left-width' : 1,
                    'float' : 'right'
                },
                events : {
                    onClick : this.openMedia
                }
            }).inject( this.getHeader() );

            var SortButton = new QUIButton({
                image  : 'icon-sort',
                alt    : 'Sortierung',
                title  : 'Sortierung',
                styles : {
                    'border-left-width' : 1,
                    'float' : 'right'
                },
                events : {
                    onClick : this.openSort
                }
            }).inject( this.getHeader() );


            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            Ajax.get([
                'ajax_site_categories_get',
                'ajax_site_buttons_get'
            ], function(categories, buttons, Request)
            {
                var i, ev, fn, len, events, category, Category;


                for ( i = 0, len = buttons.length; i < len; i++ )
                {
                    if ( buttons[ i ].onclick )
                    {
                        buttons[ i ]._onclick = buttons[ i ].onclick;
                        delete buttons[ i ].onclick;

                        buttons[ i ].events = {
                            onClick : self.$onPanelButtonClick
                        };
                    }

                    self.addButton( buttons[ i ] );
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
                        onActive : self.$onCategoryEnter
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

                    self.addCategory( Category );
                }

                Site.addEvents({
                    onLoad       : self.load,
                    onActivate   : self.$onSiteActivate,
                    onDeactivate : self.$onSiteDeactivate,
                    onSave       : self.$onSiteSave,
                    onDelete     : self.$onSiteDelete
                });

                Site.load();

            }, {
                project : Project.getName(),
                lang    : Project.getLang(),
                id      : Site.getId()
            });
        },

        /**
         * event : on destroy
         */
        $onDestroy : function()
        {
            var Site = this.getSite();

            Site.removeEvent( 'onLoad', this.load );
            Site.removeEvent( 'onActivate', this.$onSiteActivate );
            Site.removeEvent( 'onDeactivate', this.$onSiteDeactivate );
            Site.removeEvent( 'onSave', this.$onSiteSave );
            Site.removeEvent( 'onDelete', this.$onSiteDelete );
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
         * Opens the sort sheet
         *
         * @method controls/projects/project/site/Panel#openSort
         */
        openSort : function()
        {
            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            this.createSheet({
                title : 'Sortierung - '+ this.getAttribute( 'title' ),
                events :
                {
                    onOpen : function(Sheet)
                    {
                        require([
                            'controls/projects/project/site/SiteChildrenSort'
                        ], function(SiteSort) {
                            new SiteSort( Site ).inject( Sheet.getContent() );
                        });
                    }
                }
            }).show();
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
                Site = self.getSite();

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

            if ( Category.getAttribute( 'type' ) == 'wysiwyg' )
            {
                this.loadEditor(
                    this.getSite().getAttribute(
                        Category.getAttribute( 'name' )
                    )
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

                // set to the media inputs the right project
                Body.getElements( '.media-image' ).each(function(Elm) {
                    Elm.set( 'data-project', Project.getName() );
                });

                // set data
                QUIFormUtils.setDataToForm(
                    self.getSite().getAttributes(),
                    Form
                );

                // information tab
                if ( Category.getAttribute( 'name' ) === 'information' )
                {
                    Body.getElements( 'input[name="site-name"]' )
                        .set('value', Site.getAttribute( 'name' ) );


                    // site linking
                    var i, len, Row, LastCell;

                    var LinkinLangTable = Body.getElement( '.site-langs' );

                    if ( LinkinLangTable )
                    {
                        var rowList = LinkinLangTable.getElements( 'tbody tr' );

                        new QUIButton({
                            text : 'Sprach Verknüpfung hinzufügen',
                            styles : {
                                float : 'right'
                            },
                            events :
                            {
                                onClick : function() {
                                    self.addLanguagLink();
                                }
                            }
                        }).inject( LinkinLangTable.getElement( 'th' ) );


                        for ( i = 0, len = rowList.length; i < len; i++ )
                        {
                            Row = rowList[ i ];

                            if ( !Row.get( 'data-id' ).toInt() ) {
                                continue;
                            }

                            LastCell = rowList[ i ].getLast();


                            new QUIButton({
                                icon   : 'icon-file-alt',
                                alt    : 'Seite öffnen',
                                title  : 'Seite öffnen',
                                lang   : Row.get( 'data-lang' ),
                                siteId : Row.get( 'data-id' ),
                                styles : {
                                    'float' : 'right'
                                },
                                events :
                                {
                                    onClick : function(Btn)
                                    {
                                        PanelUtils.openSitePanel(
                                            Project.getName(),
                                            Btn.getAttribute( 'lang' ),
                                            Btn.getAttribute( 'siteId' )
                                        );
                                    }
                                }
                            }).inject( LastCell );

                            new QUIButton({
                                icon   : 'icon-remove',
                                alt    : 'Verknüpfung löschen',
                                title  : 'Verknüpfung löschen',
                                lang   : Row.get( 'data-lang' ),
                                siteId : Row.get( 'data-id' ),
                                styles : {
                                    'float' : 'right'
                                },
                                events :
                                {
                                    onClick : function(Btn)
                                    {
                                        self.removeLanguagLink(
                                            Btn.getAttribute( 'lang' ),
                                            Btn.getAttribute( 'siteId' )
                                        );
                                    }
                                }
                            }).inject( LastCell );
                        }
                    }
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
                    if ( Category.getAttribute( 'onload' ) )
                    {
                        eval( Category.getAttribute( 'onload' ) +'( Category, self );' );
                        return;
                    }

                    var type = typeOf( Plugin );

                    if ( type === 'function' )
                    {
                        type( Category, self );
                        return;
                    }

                    if ( type === 'class' )
                    {
                        var Obj = new Plugin({
                            Site : self.getSite()
                        });

                        if ( QUI.Controls.isControl( Obj ) )
                        {
                            Obj.inject( self.getContent() );
                            Obj.setParent( self );

                            self.Loader.hide();

                            return;
                        }
                    }
                });

                return;
            }

            if ( Category.getAttribute( 'onload' ) )
            {
                eval( Category.getAttribute( 'onload' ) +'( Category, self );' );
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
         * @param {Function} callback - [optional] callback function
         */
        $onCategoryLeave : function(Category, callback)
        {
            this.Loader.show();

            var Site  = this.getSite(),
                Body  = this.getBody();

            // main content
            if ( Category.getAttribute( 'name' ) === 'content' )
            {
                Site.setAttribute(
                    'content',
                    this.getAttribute( 'Editor' ).getContent()
                );

                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                this.Loader.hide();
                return;
            }

            // wysiwyg type
            if ( Category.getAttribute( 'type' ) == 'wysiwyg' )
            {
                Site.setAttribute(
                    Category.getAttribute( 'name' ),
                    this.getAttribute( 'Editor' ).getContent()
                );

                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                this.Loader.hide();
                return;
            }

            // form unload
            if ( !Body.getElement( 'form' ) )
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

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

                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                return;
            }

            // unload params
            for ( var i = 0, len = elements.length; i < len; i++ )
            {
                if ( elements[ i ].name )
                {
                    Site.setAttribute(
                        elements[ i ].name,
                        elements[ i ].value
                    );
                }
            }

            var self = this;

            if ( Category.getAttribute( 'onunload_require' ) )
            {
                require([
                    Category.getAttribute( 'onunload_require' )
                ], function(Plugin)
                {
                    eval( Category.getAttribute( 'onunload' ) +'( Category, self );' );

                    if ( typeof callback !== 'undefined' ) {
                        callback();
                    }
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

        /**
         * event on site save
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

                    // load css files
                    Ajax.get('ajax_editor_get_projectFiles', function(result)
                    {
                        Editor.setAttribute( 'bodyId', result.bodyId );
                        Editor.setAttribute( 'bodyClass', result.bodyClass );

                        for ( var i = 0, len = result.cssFiles.length; i < len; i++) {
                            Editor.addCSS( result[ i ] )
                        }

                        Editor.addEvent( 'onLoaded', self.$onEditorLoad );

                    }, {
                        project : Project.getName()
                    });

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
        },

        /**
         * Opens a project popup, so, an user can set a languag link
         */
        addLanguagLink : function()
        {
            var self = this;

            require(['controls/projects/Popup'], function(ProjectPopup)
            {
                var Site    = self.getSite(),
                    Project = Site.getProject();

                Project.getConfig(function(config)
                {
                    var langs = config.langs,
                        lang  = Project.getLang();

                    langs = langs.replace( lang, '' )
                                 .replace( ',,', '' )
                                 .replace( /^,|,$/g, '' );


                    new ProjectPopup({
                        project : Project.getName(),
                        langs   : langs.split(','),
                        events  :
                        {
                            onSubmit : function(Popup, result)
                            {
                                Popup.Loader.show();

                                Ajax.post('ajax_site_language_add', function()
                                {
                                    Popup.close();

                                    self.load();
                                }, {
                                    project : Project.getName(),
                                    lang    : Project.getLang(),
                                    id      : Site.getId(),
                                    linkedParams : JSON.encode({
                                        lang : result.lang,
                                        id   : result.ids[ 0 ]
                                    })
                                });
                            }
                        }
                    }).open();
                });
            });
        },

        /**
         * Open the remove languag link popup
         *
         * @param {String} lang - lang of the language link
         * @param {String} id - Site id of the language link
         */
        removeLanguagLink : function(lang, id)
        {
            var self = this;

            require(['qui/controls/windows/Confirm'], function(QUIConfirm)
            {
                var Site    = self.getSite(),
                    Project = Site.getProject();

                new QUIConfirm({
                    title  : 'Sprach-Verknüpfung wirklich löschen?',
                    icon   : 'icon-remove',
                    text   : 'Möchten Sie die Sprach-Verknüpfung wirklich löschen?',
                    events :
                    {
                        onSubmit : function(Confirm)
                        {
                            Confirm.Loader.show();

                            Ajax.post('ajax_site_language_remove', function()
                            {
                                Confirm.close();

                                self.load();
                            }, {
                                project : Project.getName(),
                                lang    : Project.getLang(),
                                id      : Site.getId(),
                                linkedParams : JSON.encode({
                                    lang : lang,
                                    id   : id
                                })
                            });
                        }
                    }
                }).open();
            });
        },

        /**
         * Open the preview window
         */
        openPreview : function()
        {
            var self = this;

            this.Loader.show();

            this.$onCategoryLeave(this.getActiveCategory(), function()
            {
                var Site    = self.getSite(),
                    Project = Site.getProject();

                var Form = new Element('form', {
                    method : 'POST',
                    action : URL_SYS_DIR +'bin/preview.php',
                    target : '_blank'
                });

                var attributes = Site.getAttributes(),
                    project = Project.getName(),
                    lang    = Project.getLang(),
                    id      = Site.getId();


                new Element('input', {
                    type  : 'hidden',
                    value : Project.getName(),
                    name  : 'project'
                }).inject( Form );

                new Element('input', {
                    type  : 'hidden',
                    value :  Project.getLang(),
                    name  : 'lang'
                }).inject( Form );

                new Element('input', {
                    type  : 'hidden',
                    value : Site.getId(),
                    name  : 'id'
                }).inject( Form );


                for ( var key in attributes )
                {
                    new Element('input', {
                        type  : 'hidden',
                        value : attributes[ key ],
                        name  : 'siteData['+ key +']'
                    }).inject( Form );
                }

                Form.inject( document.body );
                Form.submit();

                self.Loader.hide();

                (function() {
                    Form.destroy();
                }).delay( 1000 );
            });
        }
    });
});