/**
 * With the Project-Manager you can create, delete and edit projects
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires lib/Projects
 * @requires buttons/Button
 * @requires controls/projects/Sitemap
 *
 * @module controls/projects/Manager
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.project
 */

define('controls/projects/Manager', [

    'controls/desktop/Panel',
    'lib/Projects',

    'css!controls/projects/Manager.css'

], function(QUI_Control, QUI_Projects)
{
    QUI.namespace( 'controls.projects' );

    /**
     * @class QUI.controls.projects.Manager
     *
     * @param {Object} options
     */
    QUI.controls.projects.Manager = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.projects.Manager',

        Binds : [
            '$onCreate',
            '$onResize'
        ],

        initialize : function(options)
        {
            this.parent( options );

            this.setAttributes({
                name  : 'projects-manager',
                title : 'Projekt-Manager',
                icon  : URL_BIN_DIR +'16x16/apps/home.png'
            });

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });

            console.log( this );
        },

        /**
         * event: create panel
         */
        $onCreate : function()
        {
            this.addCategory({
                name   : 'edit_projects',
                text   : 'Projekte verwalten',
                icon   : URL_BIN_DIR +'32x32/actions/klipper_dock.png',
                events :
                {
                    onClick : function()
                    {

                    }
                }
            });

            this.addCategory({
                name   : 'add_project',
                text   : 'Neues Projekte erstellen',
                icon   : URL_BIN_DIR +'32x32/actions/edit_add.png',
                events :
                {
                    onClick : function()
                    {

                    }
                }
            });

            this.getCategoryBar().firstChild().click();
        },

        /**
         * event : resize panel
         */
        $onResize : function()
        {

        },

        /**
         * Opens the add project category
         */
        addProject : function()
        {

        }

    });

    return QUI.controls.projects.Manager;
});