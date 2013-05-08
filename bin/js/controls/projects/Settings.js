/**
 * Project settings panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires Projects
 *
 * @module controls/projects/Settings
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.project
 */

define('controls/projects/Settings', [

    'controls/desktop/Panel',
    'Projects',

    'css!controls/projects/Settings.css'

], function(QUI_Panel)
{
    "use strict";

    QUI.namespace( 'controls.projects' );

    /**
     * The Project settings panel
     *
     * @class QUI.controls.projects.Settings
     *
     * @param {String} project
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.Settings = new Class({

        Extends : QUI_Panel,
        Type    : 'QUI.controls.projects.Settings',

        Binds : [
            '$onCreate',
            '$onResize',

            'openSettings',
            'openMeta',
            'openBackup',
            'openWatersign'
        ],

        initialize : function(project, options)
        {
            // defaults
            this.$project = project;
            this.$Project = QUI.Projects.get( this.$project );

            this.setAttributes({
                name  : 'projects-panel',
                icon  : URL_BIN_DIR +'16x16/home.png',
                title : this.$project
            });

            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });
        },

        /**
         * Return the Project of the Panel
         *
         * @method QUI.controls.projects.Settings#getProject
         * @return {QUI.classes.projects.Project} Project of the Panel
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Create the project settings body
         *
         * @method QUI.controls.projects.Settings#$onCreate
         */
        $onCreate : function()
        {
            this.Loader.show();

            this.addCategory({
                name   : 'settings',
                text   : 'Einstellungen',
                icon   : URL_BIN_DIR +'32x32/actions/misc.png',
                events : {
                    onClick : this.openSettings
                }
            });

            this.addCategory({
                name   : 'meta',
                text   : 'Meta Angaben',
                icon   : URL_BIN_DIR +'32x32/actions/contents.png',
                events : {
                    onClick : this.openMeta
                }
            });

            this.addCategory({
                name   : 'backup',
                text   : 'Backup',
                icon   : URL_BIN_DIR +'32x32/devices/hdd_mount.png',
                events : {
                    onClick : this.openBackup
                }
            });

            this.addCategory({
                name   : 'watersign',
                text   : 'Wasserzeichen',
                icon   : URL_BIN_DIR +'32x32/actions/thumbnail.png',
                events : {
                    onClick : this.openWatersign
                }
            });

            this.getCategoryBar().firstChild().click();
        },

        /**
         * Opens the Settings
         *
         * @method QUI.controls.projects.Settings#openSettings
         */
        openSettings : function()
        {
            this.Loader.show();

            var Control = this,
                Body    = Control.getBody();

            QUI.Template.get('project/settings', function(result, Request)
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

                    new QUI.controls.buttons.Button({
                        text     : 'Sprache hinzufügen',
                        textimage : URL_BIN_DIR +'16x16/add.png',
                        styles   : {
                            width : 200,
                            clear : 'both'
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
         * @method QUI.controls.projects.Settings#openMeta
         */
        openMeta : function(Plup)
        {
            this.Loader.show();

            var Control = this,
                Body    = Control.getBody();


            Body.set( 'html', '' );
            Control.Loader.hide();
        },

        /**
         * Opens the backup
         *
         * @method QUI.controls.projects.Settings#openBackup
         */
        openBackup : function()
        {
            this.Loader.show();

            var Control = this,
                Body    = Control.getBody();


            Body.set( 'html', '' );
            Control.Loader.hide();
        },

        /**
         * Opens the Watermark
         *
         * @method QUI.controls.projects.Settings#openWatersign
         */
        openWatersign : function()
        {
            this.Loader.show();

            var Control = this,
                Body    = Control.getBody();


            Body.set( 'html', '' );
            Control.Loader.hide();
        },

        /**
         * event : on panel resize
         *
         * @method QUI.controls.projects.Settings#$onResize
         */
        $onResize : function()
        {

        }
    });

    return QUI.controls.projects.Settings;
});