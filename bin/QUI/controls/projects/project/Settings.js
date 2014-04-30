/**
 * Project settings panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires Projects
 *
 * @module controls/projects/Settings
 */

define('controls/projects/project/Settings', [

    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/utils/Form',
    'utils/Template',
    'controls/lang/Popup',
    'Projects',
    'Ajax',

    'css!controls/projects/project/Settings.css'

], function(QUIPanel, QUIButton, QUIFormUtils, UtilsTemplate, LangPopup, Projects, Ajax)
{
    "use strict";

    /**
     * The Project settings panel
     *
     * @class controls/projects/project/Settings
     *
     * @param {String} project
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/Settings',

        Binds : [
            '$onCreate',
            '$onResize',

            'save',
            'openSettings',
            'openMeta',
            'openBackup',
            'openWatersign'
        ],

        options : {
            project : ''
        },

        initialize : function(options)
        {
            this.parent( options );

            // defaults
            this.$Project = Projects.get(
                this.getAttribute( 'project' )
            );

            this.setAttributes({
                name  : 'projects-panel',
                icon  : 'icon-home',
                title : this.getAttribute( 'project' )
            });

            this.$config = {};

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });
        },

        /**
         * Return the Project of the Panel
         *
         * @method controls/projects/project/Settings#getProject
         * @return {classes/projects/Project} Project of the Panel
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Create the project settings body
         *
         * @method controls/projects/project/Settings#$onCreate
         */
        $onCreate : function()
        {
            var self = this;

            this.Loader.show();

            this.addButton({
                text : 'Speichern',
                textimage : 'icon-save',
                events : {
                    onClick : this.save
                }
            });

            this.addCategory({
                name   : 'settings',
                text   : 'Einstellungen',
                icon   : 'icon-gear',
                events : {
                    onClick : this.openSettings
                }
            });

            this.addCategory({
                name   : 'meta',
                text   : 'Meta Angaben',
                icon   : 'icon-inbox',
                events : {
                    onClick : this.openMeta
                }
            });

            this.addCategory({
                name   : 'watersign',
                text   : 'Wasserzeichen',
                icon   : 'icon-picture',
                events : {
                    onClick : this.openWatersign
                }
            });

            this.getProject().getConfig(function(result, Request)
            {
                self.$config = result;
                self.getCategoryBar().firstChild().click();
            });
        },

        /**
         * Save the project settings
         */
        save : function()
        {
            var self = this;

            this.Loader.show();
            this.$unloadCategory();

            Ajax.post('ajax_project_set_config', function()
            {
                self.Loader.hide();
            }, {
                project : this.$Project.getName(),
                params  : JSON.encode( this.$config )
            });
        },

        /**
         * Opens the Settings
         *
         * @method controls/projects/project/Settings#openSettings
         */
        openSettings : function()
        {
            this.Loader.show();
            this.$unloadCategory();

            var self = this,
                Body = this.getBody();

            UtilsTemplate.get('project/settings', function(result)
            {
                Body.set( 'html', result );

                // set data
                var Form     = Body.getElement( 'Form' ),
                    Standard = Form.elements.default_lang,
                    Langs    = Form.elements.langs,

                    langs = self.$config.langs.split( ',' );

                for ( var i = 0, len = langs.length; i < len; i++ )
                {
                    new Element('option', {
                        html  : langs[ i ],
                        value : langs[ i ]
                    }).inject( Standard );

                    new Element('option', {
                        html  : langs[ i ],
                        value : langs[ i ]
                    }).inject( Langs );
                }

                new QUIButton({
                    text : 'Sprache hinzufügen',
                    textimage : 'icon-plus',
                    styles : {
                        width : 200,
                        clear : 'both'
                    },
                    events :
                    {
                        onClick : function()
                        {
                            new LangPopup({
                                events :
                                {
                                    onSubmit : function(value, Popup) {
                                        self.addLangToProject( value[0] );
                                    }
                                }
                            }).open();
                        }
                    }
                }).inject( Langs, 'after' );


                Standard.value = self.$config.default_lang;

                QUIFormUtils.setDataToForm( self.$config, Form );

                self.Loader.hide();
            });
        },

        /**
         * Opens the Meta
         *
         * @method controls/projects/project/Settings#openMeta
         */
        openMeta : function(Plup)
        {
            this.Loader.show();
            this.$unloadCategory();

            var self = this,
                Body = this.getContent();

            UtilsTemplate.get('project/meta', function(result)
            {
                Body.set( 'html', result );

                QUIFormUtils.setDataToForm(
                    self.$config,
                    Body.getElement( 'form' )
                );

                self.Loader.hide();
            });

            /*
            Autor
            Herausgeber
            Copyright
            Suchmaschinen Indizierung
            Stichworte
            Beschreibung
            */

        },

        /**
         * Opens the Watermark
         *
         * @method controls/projects/project/Settings#openWatersign
         */
        openWatersign : function()
        {
            this.Loader.show();
            this.$unloadCategory();

            var Control = this,
                Body    = Control.getBody();

            console.warn( 'not implemented' );

            Body.set( 'html', '' );
            Control.Loader.hide();
        },

        /**
         * event : on panel resize
         *
         * @method controls/projects/project/Settings#$onResize
         */
        $onResize : function()
        {

        },

        /**
         * unload the category and set the values into the config
         */
        $unloadCategory : function()
        {
            var Content = this.getContent(),
                Form    = Content.getElement( 'form' );

            if ( !Form ) {
                return;
            }

            var data = QUIFormUtils.getFormData( Form );

            for ( var i in data )  {
                this.$config[ i ] = data[ i ];
            }

            // exist langs?
            if ( typeof Form.elements.langs !== 'undefined' )
            {
                var Langs = Form.elements.langs,
                    langs = Langs.getElements('option').map(function(Elm) {
                    return Elm.value;
                });

                this.$config.langs = langs.join(',');
            }
        },

        /**
         * Add a language to the project
         */
        addLangToProject : function(lang)
        {
            var self = this;

            self.Loader.show();

            this.$Project.getConfig(function(config)
            {
                var langs = config.langs.split( ',' );
                langs.push( lang );

                self.$Project.setConfig(function()
                {
                    self.Loader.hide();
                }, {
                    langs : langs.join( ',' )
                });
            });
        }
    });
});