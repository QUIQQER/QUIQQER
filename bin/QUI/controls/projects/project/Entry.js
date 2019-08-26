/**
 * A projects field / display
 * the display updates itself
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require Projects
 * @require Locale
 */

define('controls/projects/project/Entry', [

    'qui/controls/Control',
    'Projects',
    'Locale',

    'css!controls/projects/project/Entry.css'

], function (QUIControl, Projects, Locale) {
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

        Extends: QUIControl,
        Type   : 'controls/projects/project/Entry',

        Binds: [
            '$onProjectUpdate',
            '$onInject',
            '$onDestroy'
        ],

        options: {
            styles: false
        },

        initialize: function (project, lang, options) {
            this.parent(options);

            this.$Project = Projects.get(project, lang);

            this.$Elm      = null;
            this.$Close    = null;
            this.$Text     = null;
            this.$Icon     = null;
            this.$IconSpan = null;

            this.addEvents({
                onDestroy: this.$onDestroy,
                onInject : this.$onInject
            });
        },

        /**
         * Return the binded project
         *
         * @method controls/projects/project/Entry#getProject
         * @return {Object} Binded Project - classes/projects/Project
         */
        getProject: function () {
            return this.$Project;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @method controls/projects/project/Entry#create
         * @return {HTMLElement} Main DOM-Node Element
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class'       : 'project-entry',
                'data-project': this.getProject().getName(),
                'data-lang'   : this.getProject().getLang(),

                html  : '<div class="project-entry-icon">' +
                        '<span class="fa fa-home"></span>' +
                        '</div>' +
                        '<div class="project-entry-text"></div>' +
                        '<div class="project-entry-close">' +
                        '<span class="fa fa-remove"></span>' +
                        '</div>',
                events: {
                    mouseover: function () {
                        this.addClass('hover');
                    },
                    mouseout : function () {
                        this.removeClass('hover');
                    }
                }
            });

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Close = this.$Elm.getElement('.project-entry-close');
            this.$Icon  = this.$Elm.getElement('.project-entry-icon');
            this.$Text  = this.$Elm.getElement('.project-entry-text');

            this.$IconSpan = this.$Icon.getElement('span');

            this.$Close.addEvent('click', function () {
                self.destroy();
            });

            this.$Close.set({
                alt  : Locale.get(
                    'quiqqer/system',
                    'projects.project.panel.entry.delete.project'
                ),
                title: Locale.get(
                    'quiqqer/system',
                    'projects.project.panel.entry.delete.project'
                )
            });

            this.getProject().addEvent('onRefresh', this.$onProjectUpdate);
            this.refresh();

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            if (this.getAttribute('styles') && this.getAttribute('styles').width) {
                return;
            }

            var iconWidth  = this.$Icon.getSize().x,
                textWidth  = this.$Text.getSize().x,
                closeWidth = this.$Close.getSize().x;


            this.$Elm.setStyles({
                width: (iconWidth).toInt() +
                       (textWidth).toInt() +
                       (closeWidth).toInt()
            });
        },

        /**
         * event : on entry destroy
         *
         * @method controls/projects/project/Entry#$onDestroy
         */
        $onDestroy: function () {
            this.getProject().removeEvent('refresh', this.$onProjectUpdate);
        },

        /**
         * Refresh the data of the projects
         *
         * @method controls/projects/project/Entry#refresh
         * @return {Object} this (controls/projects/project/Entry)
         */
        refresh: function () {
            this.$IconSpan.removeClass('fa-home');
            this.$IconSpan.addClass('fa fa-spinner fa-spin');

            if (this.getProject().getName()) {
                this.$onProjectUpdate(this.getProject());

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
         * @return {Object} this (controls/projects/project/Entry)
         */
        $onProjectUpdate: function (Project) {
            if (!this.$Elm) {
                return this;
            }

            this.$Text.set(
                'html',
                Project.getName() + ' (' + Project.getLang() + ')'
            );

            this.$IconSpan.addClass('fa-home');
            this.$IconSpan.removeClass('fa-spinner');
            this.$IconSpan.removeClass('fa-spin');

            this.$onInject();

            return this;
        }
    });
});
