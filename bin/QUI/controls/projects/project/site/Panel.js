
/**
 * Displays a Site in a Panel
 *
 * @module controls/projects/project/site/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/desktop/Panel
 * @require Projects
 * @require Ajax
 * @require classes/projects/project/Site
 * @require qui/controls/buttons/Button
 * @require qui/utils/Form
 * @require utils/Controls
 * @require utils/Panels
 * @require utils/Site
 * @require Locale
 * @require css!controls/projects/project/site/Panel.css
 */

define('controls/projects/project/site/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'Projects',
    'Ajax',
    'qui/controls/buttons/Button',
    'qui/utils/Form',
    'qui/utils/Elements',
    'utils/Controls',
    'utils/Panels',
    'utils/Site',
    'Locale',

    'css!controls/projects/project/site/Panel.css'

], function()
{
    "use strict";

    var QUI          = arguments[ 0 ],
        QUIPanel     = arguments[ 1 ],
        Projects     = arguments[ 2 ],
        Ajax         = arguments[ 3 ],
        QUIButton    = arguments[ 4 ],
        QUIFormUtils = arguments[ 5 ],
        QUIElmUtils  = arguments[ 6 ],
        ControlUtils = arguments[ 7 ],
        PanelUtils   = arguments[ 8 ],
        SiteUtils    = arguments[ 9 ],
        Locale       = arguments[ 10 ];

    var lg = 'quiqqer/system';

    /**
     * An SitePanel, opens the Site in an Apppanel
     *
     * @class controls/projects/project/site/Panel
     *
     * @param {Object} Site - classes/projects/Site
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
            '$onInject',
            '$onCategoryEnter',
            '$onCategoryLeave',
            '$onEditorLoad',
            '$onEditorDestroy',
            '$onPanelButtonClick',
            '$onLogin',

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
            this.$Site            = null;
            this.$CategoryControl = null;

            if ( typeOf( Site ) === 'classes/projects/project/Site' )
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

            } else
            {
                // serialize data
                if ( typeof Site.attributes !== 'undefined' &&
                     typeof Site.project !== 'undefined' &&
                     typeof Site.lang !== 'undefined' &&
                     typeof Site.id !== 'undefined' )
                {
                    this.unserialize( Site );
                }
            }

            this.parent( options );

            this.addEvents({
                onCreate  : this.$onCreate,
                onResize  : this.$onResize,
                onDestroy : this.$onDestroy,
                onInject  : this.$onInject
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
                project    : Project.getName(),
                type       : this.getType()
            };
        },

        /**
         * import the saved data form the workspace
         *
         * @method controls/projects/project/site/Panel#unserialize
         * @param {Object} data
         * @return {Object} this (controls/projects/project/site/Panel)
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
         * @return {Object} classes/projects/Site
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
            this.refresh();


            if ( this.getActiveCategory() )
            {
                this.$onCategoryEnter( this.getActiveCategory() );

            } else
            {
                this.getCategoryBar().firstChild().click();
            }
        },

        /**
         * Refresh the site panel
         */
        refresh : function()
        {
            var title, description;

            var Site    = this.getSite(),
                Project = Site.getProject();

            title = Site.getAttribute( 'title') +' ('+ Site.getId() +')';

            description = Site.getAttribute( 'name' ) +' - '+
                          Site.getId() +' - ' +
                          Project.getName();

            if ( Site.getId() != 1 ) {
                description = description +' - '+ Site.getUrl();
            }

            this.setAttributes({
                title       : title,
                description : description,
                icon        : URL_BIN_DIR +'16x16/flags/'+ Project.getLang() +'.png'
            });

            this.parent();
        },

        /**
         * Create the panel design
         *
         * @method controls/projects/project/site/Panel#$onCreate
         */
        $onCreate : function()
        {
            this.Loader.show();

            window.addEvent( 'login', this.$onLogin );


            // permissions
            new QUIButton({
                image  : 'icon-gears',
                alt    : Locale.get( lg, 'projects.project.site.panel.btn.permissions' ),
                title  : Locale.get( lg, 'projects.project.site.panel.btn.permissions' ),
                styles : {
                    'border-left-width' : 1,
                    'float' : 'right'
                },
                events : {
                    onClick : this.openPermissions
                }
            }).inject( this.getHeader() );

            new QUIButton({
                image  : 'icon-picture',
                alt    : Locale.get( lg, 'projects.project.site.panel.btn.media' ),
                title  : Locale.get( lg, 'projects.project.site.panel.btn.media' ),
                styles : {
                    'border-left-width' : 1,
                    'float' : 'right'
                },
                events : {
                    onClick : this.openMedia
                }
            }).inject( this.getHeader() );

            new QUIButton({
                image  : 'icon-sort',
                alt    : Locale.get( lg, 'projects.project.site.panel.btn.sort' ),
                title  : Locale.get( lg, 'projects.project.site.panel.btn.sort' ),
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
                'ajax_site_buttons_get',
                'ajax_site_isLockedFromOther',
                'ajax_site_lock'
            ], function(categories, buttons, isLocked)
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
                    });

                    for ( ev in events  )
                    {
                        if ( !events.hasOwnProperty( ev ) ) {
                            continue;
                        }

                        try
                        {
                            eval( 'fn = '+ events[ ev ] );

                            Category.addEvent( ev, fn );

                        } catch ( e ) {}
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

                if ( isLocked ) {
                    self.setLocked();
                }

            }, {
                project : Project.encode(),
                id      : Site.getId()
            });
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            console.log( '$onInject' );
            console.log( this.getSite().getWorkingStorage() );

            if ( !this.getSite().hasWorkingStorage() )
            {
                this.getSite().load();
                return;
            }

            var self = this;

            this.Loader.show();

            require(['qui/controls/windows/Confirm'], function(QUIConfirm)
            {
                new QUIConfirm({
                    title   : 'Wiederherstellung',
                    content : 'Es wurde nicht gespeicherte Daten der Seite #'+ self.getSite().getId() +' gefunden. ' +
                              'Sollen die Daten wieder hergestellt werden?',
                    maxWidth  : 500,
                    maxHeight : 200,
                    events :
                    {
                        onSubmit : function()
                        {
                            self.getSite().restoreWorkingStorage();
                            self.load();
                        },

                        onCancel : function()
                        {
                            self.getSite().clearWorkingStorage();
                            self.getSite().load(function() {
                                self.load();
                            });
                        }
                    }
                }).open();
            });
        },

        /**
         * event : on destroy
         */
        $onDestroy : function()
        {
            var Site    = this.getSite(),
                Project = Site.getProject();

            Site.removeEvent( 'onLoad', this.load );
            Site.removeEvent( 'onActivate', this.$onSiteActivate );
            Site.removeEvent( 'onDeactivate', this.$onSiteDeactivate );
            Site.removeEvent( 'onSave', this.$onSiteSave );
            Site.removeEvent( 'onDelete', this.$onSiteDelete );

            Site.clearWorkingStorage();

            window.removeEvent( 'login', this.$onLogin );

            Ajax.get(['ajax_site_unlock'], false, {
                project : Project.encode(),
                id      : Site.getId()
            });
        },

        /**
         * event: panel resize
         *
         * @method controls/projects/project/site/Panel#$onResize
         */
        $onResize : function()
        {
            if ( this.$CategoryControl )
            {
                if ( "resize" in this.$CategoryControl ) {
                    this.$CategoryControl.resize();
                }
            }
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
            var Site = this.getSite();

            this.createSheet({
                title : Locale.get( lg, 'projects.project.site.panel.sort.title', {
                    id    : Site.getId(),
                    title : Site.getAttribute('title'),
                    name  : Site.getAttribute('name')
                }),
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
            var self = this;

            this.$onCategoryLeave( this.getActiveCategory() );

            this.getSite().save(function() {
                self.load();
            });
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
                    title     : Locale.get( lg, 'projects.project.site.panel.window.delete.title' ),
                    titleicon : 'icon-trash',
                    text : Locale.get( lg, 'projects.project.site.panel.window.delete.text', {
                        id    : Site.getId(),
                        url   : Site.getAttribute( 'name' ) +'.html',
                        name  : Site.getAttribute( 'name' ),
                        title : Site.getAttribute( 'title' )
                    }),
                    information : Locale.get( lg, 'projects.project.site.panel.window.delete.information', {
                        id    : Site.getId(),
                        url   : Site.getAttribute( 'name' ) +'.html',
                        name  : Site.getAttribute( 'name' ),
                        title : Site.getAttribute( 'title' )
                    }),
                    height : 200,
                    events : {
                        onSubmit : function() {
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
         * @param {String} [value] - [optional, if no newname was passed,
         *         a window would be open]
         */
        createNewChild : function(value)
        {
            SiteUtils.openCreateChild( this.getSite(), value );
        },

        /**
         * Enter the Tab / Category
         * Load the tab content and set the site attributes
         * or exec the plugin event
         *
         * @method controls/projects/project/site/Panel#$tabEnter
         * @fires onSiteTabLoad
         *
         * @param {Object} Category - qui/controls/toolbar/Button
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

                QUI.parse( Category );
                return;
            }

            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            Ajax.get([
                'ajax_site_categories_template',
                'ajax_site_lock'
            ], function(result)
            {
                var Body = self.getContent();

                if ( !result )
                {
                    Body.set( 'html', '' );
                    self.$categoryOnLoad( Category );

                    return;
                }

                var Form;

                Body.set( 'html', '<form class="qui-site-data">'+ result +'</form>' );

                Form = Body.getElement( 'form' );
                Form.addEvent('submit', function(event) {
                    event.stop();
                });

                // set to the media inputs the right project
                Body.getElements( '.media-image' ).each(function(Elm) {
                    Elm.set( 'data-project', Project.getName() );
                });

                // minimize setting tables
                if ( Category.getAttribute( 'name' ) == 'settings' )
                {
                    Body.getElements( '.data-table:not(.site-data)' )
                        .addClass( 'data-table-closed' );
                }

                // set data
                QUIFormUtils.setDataToForm(
                    self.getSite().getAttributes(),
                    Form
                );

                // information tab
                if ( Category.getAttribute( 'name' ) === 'information' )
                {
                    self.$bindNameInputUrlFilter();


                    // site linking
                    var i, len, Row, LastCell;

                    var LinkinLangTable = Body.getElement( '.site-langs'),
                        Locked          = Body.getElement( '[data-locked]' );

                    if ( LinkinLangTable )
                    {
                        var rowList = LinkinLangTable.getElements( 'tbody tr' );

                        new QUIButton({
                            text   : Locale.get( lg, 'projects.project.site.panel.linked.btn.add' ),
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
                                alt    : Locale.get( lg, 'open.site' ),
                                title  : Locale.get( lg, 'open.site' ),
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
                                alt    : Locale.get( lg, 'projects.project.site.panel.linked.btn.delete' ),
                                title  : Locale.get( lg, 'projects.project.site.panel.linked.btn.delete' ),
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


                    // locked
                    if ( Locked && USER.isSU )
                    {
                        new QUIButton({
                            text   : 'Trotzdem freischalten',
                            styles : {
                                clear : 'both',
                                display : 'block',
                                'float' : 'none',
                                margin : '10px auto',
                                width : 200
                            },
                            events :
                            {
                                onClick : function() {
                                    self.unlockSite();
                                }
                            }
                        }).inject( Locked );
                    }
                }

                ControlUtils.parse(Form, function()
                {
                    QUI.parse(Form, function()
                    {
                        // set the project to the controls
                        var i, len, Control;
                        var quiids = Form.getElements( '[data-quiid]' );

                        for ( i = 0, len = quiids.length; i < len; i++ )
                        {
                            Control = QUI.Controls.getById( quiids[ i ].get('data-quiid') );

                            if ( !Control ) {
                                continue;
                            }

                            if ( typeOf( Control.setProject ) == 'function' ) {
                                Control.setProject( Project );
                            }
                        }

                    });
                });


                self.$categoryOnLoad( Category );

            }, {
                id      : Site.getId(),
                project : Project.encode(),
                tab     : Category.getAttribute( 'name' )
            });
        },

        /**
         * Load the category
         *
         * @method controls/projects/project/site/Panel#$categoryOnLoad
         * @param {Object} Category - qui/controls/buttons/Button
         */
        $categoryOnLoad : function(Category)
        {
            var self = this,

                onloadRequire = Category.getAttribute( 'onload_require' ),
                onload        = Category.getAttribute( 'onload' );

            if ( onloadRequire )
            {
                require([ onloadRequire ], function(Plugin)
                {
                    if ( onload )
                    {
                        eval( onload +'( Category, self );' );
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
                        self.$CategoryControl = new Plugin({
                            Site : self.getSite()
                        });

                        if ( QUI.Controls.isControl( self.$CategoryControl ) )
                        {
                            self.$CategoryControl.inject( self.getContent() );
                            self.$CategoryControl.setParent( self );

                            self.Loader.hide();
                        }
                    }
                });

                return;
            }

            if ( onload )
            {
                eval( onload +'( Category, self );' );
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
         * @param {Object} Category - qui/controls/buttons/Button
         * @param {Function} [callback] - (optional) callback function
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

                if ( this.$CategoryControl )
                {
                    this.$CategoryControl.destroy();
                    this.$CategoryControl = null;
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
            var FormData = QUIFormUtils.getFormData( Form );

            for ( var key in FormData )
            {
                if ( key === '' ) {
                    continue;
                }

                if ( FormData.hasOwnProperty( key ) ) {
                    Site.setAttribute( key, FormData[ key ] );
                }
            }


            var self            = this,
                onunloadRequire = Category.getAttribute( 'onunload_require' ),
                onunload        = Category.getAttribute( 'onunload' );

            if ( onunloadRequire )
            {
                require([ onunloadRequire ], function()
                {
                    eval( onunload +'( Category, self );' );

                    if ( typeof callback !== 'undefined' ) {
                        callback();
                    }
                });
            }

            if ( this.$CategoryControl )
            {
                this.$CategoryControl.destroy();
                this.$CategoryControl = null;
            }
        },

        /**
         * Execute the panel onclick from PHP
         *
         * @method controls/projects/project/site/Panel#$onPanelButtonClick
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        $onPanelButtonClick : function(Btn)
        {
            var Panel = this; // maybe in eval

            eval( Btn.getAttribute( '_onclick' ) +'();' );
        },

        /**
         *
         */
        $bindNameInputUrlFilter : function()
        {
            var Site = this.getSite(),
                Body = this.getContent();

            var NameInput  = Body.getElements( 'input[name="site-name"]' ),
                UrlDisplay = Body.getElements( '.site-url-display' ),
                siteUrl    = Site.getUrl();

            if ( Site.getId() != 1 ) {
                UrlDisplay.set( 'html', Site.getUrl() );
            }

            // filter
            var sitePath   = siteUrl.replace(/\\/g, '/').replace(/\/[^\/]*\/?$/, '') +'/',
                notAllowed = Object.keys( SiteUtils.notAllowedUrlSigns() ).join('|'),
                reg        = new RegExp( '['+ notAllowed +']', "g" );

            var lastPos = null;

            NameInput.set({
                value  : Site.getAttribute( 'name' ),
                events :
                {
                    keydown : function(event) {
                        lastPos = QUIElmUtils.getCursorPosition( event.target );
                    },

                    keyup : function()
                    {
                        var old = this.value;

                        this.value = this.value.replace( reg, '' );
                        this.value = this.value.replace( / /g, QUIQQER.Rewrite.URL_SPACE_CHARACTER );

                        if ( old != this.value ) {
                            QUIElmUtils.setCursorPosition( this, lastPos );
                        }

                        if ( Site.getId() != 1 ) {
                            UrlDisplay.set('html', sitePath + this.value + '.html');
                        }
                    },

                    blur : function() {
                        this.fireEvent( 'keyup' );
                    },

                    focus : function() {
                        this.fireEvent( 'keyup' );
                    }
                }
            });
        },

        /**
         * Disable the buttons, if the site is locked
         */
        setLocked : function()
        {
            var buttons = this.getButtons();

            for ( var i = 0, len = buttons.length; i < len; i++ )
            {
                if ( "disable" in buttons[ i ] ) {
                    buttons[ i ].disable();
                }
            }
        },

        /**
         * Enable the buttons, if the site is unlocked
         */
        setUnlocked : function()
        {
            var buttons = this.getButtons();

            for ( var i = 0, len = buttons.length; i < len; i++ )
            {
                if ( "enable" in buttons[ i ] ) {
                    buttons[ i ].enable();
                }
            }
        },

        /**
         * unlock the site, only if the user has the permission
         */
        unlockSite : function()
        {
            var self = this,
                Site = this.getSite();

            this.Loader.show();

            Site.unlock(function() {
                self.$destroyRefresh();
            });
        },

        /**
         * destroy and refresh the panel
         *
         * @private
         */
        $destroyRefresh : function()
        {
            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            require(['utils/Panels'], function(Utils)
            {
                self.destroy();

                Utils.openSitePanel(
                    Project.getName(),
                    Project.getLang(),
                    Site.getId()
                );
            });
        },

        /**
         * event : on login
         */
        $onLogin : function()
        {
            var Active = this.getActiveCategory(),
                Task   = this.getAttribute( 'Task' );

            // if task exists, check if task is active
            if ( Task &&
                 typeOf( Task ) === 'qui/controls/taskbar/Task' &&
                 Task.isActive() === false
            ) {
                return;
            }

            // no active category? something is wrong, so reload the panel
            if ( !Active )
            {
                this.$destroyRefresh();
                return;
            }

            this.$onCategoryEnter( this.getActiveCategory() );
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
                            Editor.addCSS( result[ i ] );
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
         */
        $onEditorLoad : function()
        {
            this.Loader.hide();
        },

        /**
         * event: on editor load
         * if the editor would be destroyed
         *
         * @method controls/projects/project/site/Panel#$onEditorDestroy
         */
        $onEditorDestroy : function()
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
                                    project : Project.encode(),
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
         * @param {String} id - Site-ID of the language link
         */
        removeLanguagLink : function(lang, id)
        {
            var self = this;

            require(['qui/controls/windows/Confirm'], function(QUIConfirm)
            {
                var Site    = self.getSite(),
                    Project = Site.getProject();

                new QUIConfirm({
                    title  : Locale.get( lg, 'projects.project.site.panel.linked.window.delete.title' ),
                    icon   : 'icon-remove',
                    text   : Locale.get( lg, 'projects.project.site.panel.linked.window.delete.text' ),
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
                                project : Project.encode(),
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

                var attributes = Site.getAttributes();

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
                    if ( attributes.hasOwnProperty( key ) )
                    {
                        new Element('input', {
                            type  : 'hidden',
                            value : attributes[ key ],
                            name  : 'siteData['+ key +']'
                        }).inject( Form );
                    }
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
