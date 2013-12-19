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

define('controls/projects/project/Entry', [

    'qui/controls/Control',
    'Projects',

    'css!controls/projects/project/Entry.css'

], function(QUIControl, Projects)
{
    "use strict";

    /**
     * A projects field / display
     *
     * @class controls/projects/project/Entry
     *
     * @param {String} project - Project name
     * @param {String} lang - Project language
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/Entry',

        Binds : [
            '$onProjectUpdate',
            'destroy'
        ],

        initialize : function(project, lang, options)
        {
            this.$Project = Projects.get( project, lang );
            this.parent( options );

            this.$Elm = null;
        },

        /**
         * Return the binded project
         *
         * @method controls/projects/project/Entry#getProject
         * @return {classes/projects/Project} Binded Project
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @method controls/projects/project/Entry#create
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
         * @method controls/projects/project/Entry#$onDestroy
         */
        $onDestroy : function()
        {
            this.getProject().removeEvent( 'refresh', this.$onProjectUpdate );
        },

        /**
         * Refresh the data of the projects
         *
         * @method controls/projects/project/Entry#refresh
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
         * @method controls/projects/project/Entry#$onProjectUpdate
         * @param {classes/projects/Project} Project
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
});