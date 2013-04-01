/**
 * A projects field / display
 * the display updates itself
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/Entry
 * @package com.pcsg.qui.js.controls.projects
 * @namespace QUI.controls.projects
 *
 * @require controls/Control
 * @require Projects
 */

define('controls/projects/Entry', [

    'controls/Control',
    'Projects',

    'css!controls/projects/Entry.css'

], function(QUI_Control)
{
    "use strict";

    QUI.namespace( 'controls.projects' );

    /**
     * A projects field / display
     *
     * @class QUI.controls.projects.Entry
     *
     * @param {String} project - Project name
     * @param {String} lang - Project language
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.Entry = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.projects.Entry',

        Binds : [
            '$onProjectUpdate',
            'destroy'
        ],

        initialize : function(project, lang, options)
        {
            this.$Project = QUI.Projects.get( project, lang );
            this.init( options );

            this.$Elm = null;
        },

        /**
         * Return the binded project
         *
         * @method QUI.controls.projects.Entry#getProject
         * @return {QUI.classes.projects.Project} Binded Project
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @method QUI.controls.projects.Entry#create
         * @return {DOMNode} Main DOM-Node Element
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'   : 'projects-entry radius5',
                'data-project' : this.getProject().getName(),
                'data-lang'    : this.getProject().getLang(),

                html : '<div class="text"></div>' +
                       '<div class="close"></div>',

                events :
                {
                    mouseover : function() {
                        this.addClass( 'hover' );
                    },
                    mouseout : function() {
                        this.removeClass( 'hover' );
                    }
                }
            });

            var Close = this.$Elm.getElement( '.close' );

            Close.addEvent( 'click', this.destroy );
            Close.set({
                alt   : 'Projekt entfernen',
                title : 'Projekt entfernen'
            });

            this.getProject().addEvent( 'onRefresh', this.$onProjectUpdate );
            this.refresh();

            return this.$Elm;
        },

        /**
         * event : on entry destroy
         *
         * @method QUI.controls.projects.Entry#$onDestroy
         */
        $onDestroy : function()
        {
            this.getProject().removeEvent( 'refresh', this.$onProjectUpdate );
        },

        /**
         * Refresh the data of the projects
         *
         * @method QUI.controls.projects.Entry#refresh
         * @return {this} self
         */
        refresh : function()
        {
            this.$Elm.getElement( '.text' ).set(
                'html',
                '<img src="'+ URL_BIN_DIR +'images/loader.gif" />'
            );

            if ( this.getProject().getAttribute( 'name' ) )
            {
                this.$onProjectUpdate( this.getProject() );
                return this;
            }

            this.getProject().load();

            return this;
        },

        /**
         * Update the project name
         *
         * @method QUI.controls.projects.Entry#$onProjectUpdate
         * @param {QUI.classes.projects.Project} Project
         * @return {this} self
         */
        $onProjectUpdate : function(Project)
        {
            if ( !this.$Elm ) {
                return this;
            }

            this.$Elm.getElement( '.text' )
                     .set( 'html', Project.getName() );

            return this;
        }
    });

    return QUI.controls.projects.Entry;
});