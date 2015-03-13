
/**
 *
 * @module controls/projects/project/site/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Popup
 * @require controls/projects/TypeWindow
 * @require controls/projects/Popup
 * @require Projects
 * @require css!controls/projects/project/site/Select.css
 */

define('controls/projects/project/site/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Popup',
    'controls/projects/TypeWindow',
    'controls/projects/Popup',
    'Projects',

    'css!controls/projects/project/site/Select.css'

],function(QUI, QUIControl, QUIButton, QUIPopup, TypeWindow, ProjectWindow, Projects)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/site/Select',

        Binds : [
            'openSitemap',
            'openSiteTypes',
            '$onImport'
        ],

        options : {
            styles      : false,
            name        : '',
            value       : '',
            projectName : false,
            projectLang : false,
            placeholder : '',
            selectids   : true,
            selecttypes : true
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Input     = false;
            this.$Buttons   = false;
            this.$Container = false;
            this.$Project   = false;

            this.$ButtonTypes = false;
            this.$ButtonSite  = false;

            this.addEvents({
                onImport : this.$onImport
            });
        },

        /**
         * Return the domnode element
         *
         * @return {HTMLElement}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'control-site-select',
                html    : '<div class="control-site-select-container"></div>'+
                          '<div class="control-site-select-buttons"></div>'
            });

            if ( !this.$Input )
            {
                this.$Input = new Element('input', {
                    type : 'hidden'
                }).inject( this.$Elm );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.$Buttons   = this.$Elm.getElement( '.control-site-select-buttons' );
            this.$Container = this.$Elm.getElement( '.control-site-select-container' );

            this.$Container.set(
                'html',

                '<p class="control-site-select-container-placeholder">'+
                    this.getAttribute( 'placeholder' ) +
                '</p>'
            );


            if ( this.getAttribute( 'selecttypes' ) )
            {
                this.$ButtonTypes = new QUIButton({
                    name: 'add-types',
                    text: 'Seiten Typ hinzufügen',
                    styles: {
                        width: this.getAttribute( 'selectids' ) ? '50%' : '100%'
                    },
                    events: {
                        onClick: this.openSiteTypes
                    },
                    disabled: true
                }).inject(this.$Buttons);
            }

            if ( this.getAttribute( 'selectids' ) )
            {
                this.$ButtonSite = new QUIButton({
                    name: 'add-site',
                    text: 'Seiten ID hinzufügen',
                    styles: {
                        width: this.getAttribute( 'selecttypes' ) ? '50%' : '100%'
                    },
                    events: {
                        onClick: this.openSitemap
                    },
                    disabled: true
                }).inject(this.$Buttons);
            }

            return this.$Elm;
        },

        /**
         * Resize the control
         */
        resize : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            this.parent();

            var maxSize = this.$Elm.getSize(),
                btnSize = this.$Buttons.getSize();

            this.$Container.setStyle( 'height', maxSize.y - btnSize.y - 2 );
        },

        /**
         * Refresh the control
         */
        refresh : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            this.resize();
            this.refreshValues();
        },

        /**
         * event : on import
         */
        $onImport : function()
        {
            if ( this.$Elm.nodeName != 'INPUT' ) {
                return;
            }

            this.$Input = this.$Elm;
            this.$Input.type = 'hidden';
            this.$Input.set( 'data-quiid', this.getId() );

            this.$Elm = this.create();
            this.$Elm.wraps( this.$Input );

            this.setAttribute( 'name', this.$Input.name );
            this.setAttribute( 'value', this.$Input.value );

            this.setProject(
                this.$Input.get( 'data-project' ),
                this.$Input.get( 'data-lang' )
            );

            if ( this.$Input.value !== '' ) {
                this.setValue( this.$Input.value );
            }

            this.resize();
        },

        /**
         * Set the project
         *
         * @param {String|Object} project - Name of the Project
         * @param {String} [lang] - Language of the Project
         */
        setProject : function(project, lang)
        {
            if ( typeOf( project ) == 'classes/projects/Project' )
            {
                this.$Project = project;

                if ( this.$ButtonTypes ) {
                    this.$ButtonTypes.enable();
                }

                if ( this.$ButtonSite ) {
                    this.$ButtonSite.enable();
                }

                return;
            }

            this.setAttribute( 'projectName', project );
            this.setAttribute( 'projectLang', lang );

            if ( project === '' ) {
                return;
            }

            if ( lang === '' ) {
                return;
            }

            this.$Project = Projects.get(
                this.getAttribute('projectName'),
                this.getAttribute('projectLang')
            );


            if ( this.$ButtonTypes ) {
                this.$ButtonTypes.enable();
            }

            if ( this.$ButtonSite ) {
                this.$ButtonSite.enable();
            }

        },

        /**
         * Set the input value
         *
         * @param {String} value
         */
        setValue : function(value)
        {
            var i, len, val;
            var values = value.split( ';' );

            for ( i = 0, len = values.length; i < len; i++ )
            {
                val = values[ i ];

                if ( parseInt( val ) )
                {
                    this.addSiteId( val );
                } else
                {
                    this.addSiteType( val );
                }
            }
        },

        /**
         * Opens the sitemap window, to add some side ids
         */
        openSitemap : function()
        {
            if ( !this.$Project ) {
                return;
            }

            var self = this;

            new ProjectWindow({
                project : this.$Project.getName(),
                lang    : this.$Project.getLang(),
                events  :
                {
                    onSubmit : function(Win, params)
                    {
                        var ids = params.ids;

                        for ( var i = 0, len = ids.length; i < len; i++ ) {
                            self.addSiteId( ids[ i ] );
                        }
                    }
                }
            }).open();
        },

        /**
         * Opens a site type window, to add some side types
         */
        openSiteTypes : function()
        {
            if ( !this.$Project )
            {
                console.error( 'No Project was given.' );
                return;
            }

            var self = this;

            new TypeWindow({
                multible : true,
                project  : this.$Project.getName(),
                pluginsSelectable : true,
                events :
                {
                    onSubmit : function(Win, values)
                    {
                        for ( var i = 0, len = values.length; i < len; i++ ) {
                            self.addSiteType( values[ i ] );
                        }
                    }
                }
            }).open();
        },

        /**
         * Add a site ID to the select
         *
         * @param {number} siteId
         */
        addSiteId : function(siteId)
        {
            if ( typeof siteId === 'undefined' ) {
                return;
            }

            siteId = parseInt( siteId );

            if ( !siteId ) {
                return;
            }


            var Elm = this.createEntry( siteId ).inject( this.$Container );

            new Element('span', {
                'class' : 'icon-file-alt'
            }).inject( Elm.getElement( '.control-site-select-entry-text' ) );

            Elm.inject( this.$Container );


            this.refreshValues();
        },

        /**
         * Add a site type to the select or a site type selection
         *
         * @param {String} type - eq: "quiqqer/%" "quiqqer/blog:blog/entry" "quiqqer/blog:%"
         */
        addSiteType : function(type)
        {
            if ( typeof type === 'undefined' ) {
                return;
            }

            if ( type === '' ) {
                return;
            }


            if ( !type.match( ':' ) && !type.match( '%' ) ) {
                type = type +':%';
            }

            var Elm = this.createEntry( type );

            new Element('span', {
                'class' : 'icon-magic'
            }).inject( Elm.getElement( '.control-site-select-entry-text' ) );

            Elm.inject( this.$Container );


            this.refreshValues();
        },

        /**
         * Create an entry element
         *
         * @param {String|Number} value
         * @returns {HTMLElement}
         */
        createEntry : function(value)
        {
            var self = this;

            var Item = new Element('div', {
                'class' : 'control-site-select-entry',
                html : '<div class="control-site-select-entry-text">'+ value +'</div>'+
                       '<div class="control-site-select-entry-delete">'+
                           '<span class="icon-remove"></span>'+
                       '</div>',
                "data-value" : value
            });


            Item.getElement( '.icon-remove').addEvent('click', function()
            {
                this.getParent( '.control-site-select-entry').destroy();

                self.refreshValues();
            });

            return Item;
        },

        /**
         * Refresh the value, read the elements and set the value to the input field
         */
        refreshValues : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            var i, len;

            var list   = this.$Elm.getElements( '.control-site-select-entry'),
                values = [];

            for ( i = 0, len = list.length; i < len; i++ )
            {
                values.push(
                    list[ i].get( 'data-value' )
                );
            }

            this.$Input.value = values.join( ';' );
            this.setAttribute( 'value', this.$Input.value );

            this.$Elm.getElements( '.control-site-select-container-placeholder').destroy();

            if ( !values.length )
            {
                this.$Container.set(
                    'html',

                    '<p class="control-site-select-container-placeholder">'+
                        this.getAttribute( 'placeholder' ) +
                    '</p>'
                );
            }
        }
    });
});
