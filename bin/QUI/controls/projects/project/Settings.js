
/**
 * Project settings panel
 *
 * @module controls/projects/project/Settings
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require qui/utils/Form
 * @require utils/Template
 * @require controls/lang/Popup
 * @require Projects
 * @require Ajax
 * @require Locale
 * @require css!controls/projects/project/Settings.css
 */
define('controls/projects/project/Settings', [

    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'utils/Template',
    'controls/lang/Popup',
    'Projects',
    'Ajax',
    'Locale',

    'css!controls/projects/project/Settings.css'

], function(QUIPanel, QUIButton, QUIConfirm, QUIFormUtils, UtilsTemplate, LangPopup, Projects, Ajax, Locale)
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
            '$onCategoryEnter',
            '$onCategoryLeave',

            'save',
            'del',
            'openSettings',
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

            this.$config = {};

            this.addEvents({
                onCreate        : this.$onCreate,
                onResize        : this.$onResize,
                onCategoryEnter : this.$onCategoryEnter,
                onCategoryLeave : this.$onCategoryLeave
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
            this.getContent().addClass( 'qui-project-settings' );

            this.addButton({
                text : Locale.get(
                    'quiqqer/system',
                    'projects.project.panel.settings.btn.save'
                ),
                textimage : 'icon-save',
                events : {
                    onClick : this.save
                }
            });

            this.addButton({
                text : Locale.get(
                    'quiqqer/system',
                    'projects.project.panel.settings.btn.remove'
                ),
                textimage : 'icon-remove',
                events : {
                    onClick : this.del
                }
            });

            this.addCategory({
                name : 'settings',
                text : Locale.get(
                    'quiqqer/system',
                    'projects.project.panel.settings.btn.settings'
                ),
                icon   : 'icon-gear',
                events : {
                    onActive : this.openSettings
                }
            });

            Ajax.get('ajax_project_panel_categories_get', function(list)
            {
                for ( var i = 0, len = list.length; i < len; i++) {
                    self.addCategory( list[ i ] );
                }

                self.getProject().getConfig(function(result)
                {
                    self.setAttributes({
                        name  : 'projects-panel',
                        icon  : 'icon-home',
                        title : self.getProject().getName()
                    });

                    self.$config = result;
                    self.getCategoryBar().firstChild().click();
                });

            }, {
                project : this.getProject().encode()
            });


//            this.addCategory({
//                name   : 'watersign',
//                text   : 'Wasserzeichen',
//                icon   : 'icon-picture',
//                events : {
//                    onClick : this.openWatersign
//                }
//            });

        },

        /**
         * Save the project settings
         */
        save : function()
        {
            var self = this;

            this.Loader.show();
            this.$onCategoryLeave();

            Ajax.post('ajax_project_set_config', function()
            {
                self.Loader.hide();
            }, {
                project : this.$Project.getName(),
                params  : JSON.encode( this.$config )
            });
        },

        /**
         * Opens the delete dialog
         */
        del : function()
        {
            var self = this;

            new QUIConfirm({
                icon : 'icon-exclamation-sign',
                title : Locale.get(
                    'quiqqer/system',
                    'projects.project.project.delete.window.title'
                ),
                text : Locale.get(
                    'quiqqer/system',
                    'projects.project.project.delete.window.text'
                ),
                texticon : 'icon-exclamation-sign',
                information : Locale.get(
                    'quiqqer/system',
                    'projects.project.project.delete.window.information'
                ),
                events :
                {
                    onSubmit : function()
                    {
                        new QUIConfirm({
                            icon : 'icon-exclamation-sign',
                            title : Locale.get(
                                'quiqqer/system',
                                'projects.project.project.delete.window.title'
                            ),
                            text : Locale.get(
                                'quiqqer/system',
                                'projects.project.project.delete.window.text.2'
                            ),
                            texticon : 'icon-exclamation-sign',

                            events :
                            {
                                onSubmit : function()
                                {
                                    Ajax.post('ajax_project_delete', function()
                                    {

                                    }, {
                                        project : self.$Project.getName()
                                    });
                                }
                            }
                        }).open();
                    }
                }
            }).open();
        },


        /**
         * Opens the Settings
         *
         * @method controls/projects/project/Settings#openSettings
         */
        openSettings : function()
        {
            this.Loader.show();

            var self = this,
                Body = this.getBody();

            UtilsTemplate.get('project/settings', function(result)
            {
                Body.set( 'html', result );

                // set data
                var Form     = Body.getElement( 'Form' ),
                    Standard = Form.elements.default_lang,
                    Template = Form.elements.template,
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
                    text : Locale.get(
                        'quiqqer/system',
                        'projects.project.panel.btn.addlanguage'
                    ),
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
                                events : {
                                    onSubmit : function(value) {
                                        self.addLangToProject( value[0] );
                                    }
                                }
                            }).open();
                        }
                    }
                }).inject( Langs, 'after' );


                Standard.value = self.$config.default_lang;
                Template.value = self.$config.template;

                QUIFormUtils.setDataToForm( self.$config, Form );

                self.Loader.hide();
            });
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
        $onCategoryLeave : function()
        {
            var Content = this.getContent(),
                Form    = Content.getElement( 'form' );

            if ( !Form ) {
                return;
            }

            var data = QUIFormUtils.getFormData( Form );

            for ( var i in data ) {
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
         *
         * @param {String} lang
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
        },

        /**
         * event : on category enter
         *
         * @param {Object} Panel - qui/controls/desktop/Panel
         * @param {Object} Category - qui/controls/buttons/Button
         */
        $onCategoryEnter : function(Panel, Category)
        {
            var self = this,
                name = Category.getAttribute( 'name' );

            if ( name == 'settings' ) {
                return;
            }

            this.Loader.show();
            this.getBody().set( 'html', '' );

            Ajax.get('ajax_settings_category', function(result)
            {
                var Body = self.getBody();

                if ( !result ) {
                    result = '';
                }

                Body.set( 'html', '<form>'+ result +'</form>' );
                Body.getElements('tr td:first-child').addClass( 'first' );

                var Form = Body.getElement( 'form' );

                Form.name = Category.getAttribute( 'name' );
                Form.addEvent('submit', function(event) {
                    event.stop();
                });

                // set data to the form
                QUIFormUtils.setDataToForm( self.$config, Form );

                self.Loader.hide();
            }, {
                file     : Category.getAttribute( 'file' ),
                category : Category.getAttribute( 'name' )
            });
        }
    });
});