/**
 * Settings control to choose the default layout for new child sites.
 *
 * Used as a `data-qui` input inside a site type's settings (site.xml).
 * Stores the selected layout in the bound hidden input. The list of layouts
 * is loaded from the current project; the first (empty) option means the
 * project default layout.
 */
define('controls/projects/project/site/settings/ChildLayoutInput', [

    'qui/controls/Control',
    'Locale',
    'Ajax'

], function (QUIControl, QUILocale, QUIAjax) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/site/settings/ChildLayoutInput',

        Binds: [
            '$onImport',
            '$loadLayouts'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Select = null;
            this.$Project = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Event: onImport
         */
        $onImport: function () {
            this.$Input = this.getElm();
            this.$Input.type = 'hidden';

            this.$Select = new Element('select', {
                'class': 'field-container-field',
                events : {
                    change: () => {
                        this.$Input.value = this.$Select.value;
                    }
                }
            }).inject(this.$Input, 'after');

            new Element('option', {
                value: '',
                html : QUILocale.get(lg, 'projects.project.site.childLayout.default')
            }).inject(this.$Select);

        },

        /**
         * Set the project to the control (called by the site panel)
         *
         * @param {Object} Project - classes/projects/Project
         */
        setProject: function (Project) {
            this.$Project = Project;
            this.setAttribute('project', Project.getName());
            this.$loadLayouts();
        },

        /**
         * Load the available layouts of the project into the select
         */
        $loadLayouts: function () {
            if (!this.$Select || !this.$Project || this.$Select.retrieve('loaded')) {
                return;
            }

            this.$Select.store('loaded', true);

            QUIAjax.get('ajax_project_get_layouts', (layouts) => {
                if (!this.$Select) {
                    return;
                }

                for (let i = 0, len = layouts.length; i < len; i++) {
                    new Element('option', {
                        html : layouts[i].title,
                        value: layouts[i].type
                    }).inject(this.$Select);
                }

                this.$Select.value = this.$Input.value;
            }, {
                project: this.$Project.encode()
            });
        }
    });
});
