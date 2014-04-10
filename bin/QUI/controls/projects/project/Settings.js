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
    'Projects',
    'utils/Template',
    'controls/lang/Popup',

    'css!controls/projects/project/Settings.css'

], function(QUIPanel, QUIButton, Projects, UtilsTemplate, LangPopup)
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
            this.Loader.show();

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
                name   : 'backup',
                text   : 'Backup',
                icon   : 'icon-hdd',
                events : {
                    onClick : this.openBackup
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

            this.getCategoryBar().firstChild().click();
        },

        /**
         * Opens the Settings
         *
         * @method controls/projects/project/Settings#openSettings
         */
        openSettings : function()
        {
            this.Loader.show();

            var Control = this,
                Body    = Control.getBody();

            UtilsTemplate.get('project/settings', function(result, Request)
            {
                Body.set( 'html', result );

                // set data
                Control.getProject().getConfig(function(result, Request)
                {
                    var Form     = Body.getElement( 'Form' ),
                        Standard = Form.elements.default_lang,
                        Langs    = Form.elements.langs,

                        langs = result.langs.split( ',' );

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
                        styles   : {
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
                                            Control.addLangToProject( value[0] );
                                        }
                                    }
                                }).open();
                            }
                        }
                    }).inject( Langs, 'after' );

                    Standard.value = result.default_lang;
                    Form.elements.admin_mail.value = result.admin_mail || '';

                    Control.Loader.hide();
                });
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

            var Control = this,
                Body    = Control.getBody();


            console.warn( 'not implemented' );

            Body.set( 'html', '' );
            Control.Loader.hide();
        },

        /**
         * Opens the backup
         *
         * @method controls/projects/project/Settings#openBackup
         */
        openBackup : function()
        {
            this.Loader.show();

            var Control = this,
                Body    = Control.getBody();


            console.warn( 'not implemented' );

            Body.set( 'html', '' );
            Control.Loader.hide();
        },

        /**
         * Opens the Watermark
         *
         * @method controls/projects/project/Settings#openWatersign
         */
        openWatersign : function()
        {
            this.Loader.show();

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