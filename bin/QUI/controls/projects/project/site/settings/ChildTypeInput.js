/**
 * Settings control to choose the default site type for new child sites.
 *
 * Used as a `data-qui` input inside a site type's settings (site.xml).
 * Stores its value in the bound hidden input. When the input is empty the
 * effective child type (inherited from the site type's `child-type`) is shown
 * as guidance without being persisted.
 */
define('controls/projects/project/site/settings/ChildTypeInput', [

    'qui/controls/Control',
    'controls/projects/TypeInput',
    'Ajax'

], function (QUIControl, TypeInput, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/site/settings/ChildTypeInput',

        Binds: [
            '$onImport',
            '$checkDefault'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$TypeInput = null;

            this.addEvents({
                onImport: this.$onImport,
                onSetAttribute: (key) => {
                    if (key === 'Site') {
                        this.$checkDefault();
                    }
                }
            });
        },

        /**
         * Event: onImport
         */
        $onImport: function () {
            this.$Input = this.getElm();
            this.$Input.type = 'hidden';

            this.$TypeInput = new TypeInput({
                project: this.getAttribute('project') || false
            }, this.$Input);

            this.$TypeInput.create();
        },

        /**
         * Set the project to the control (called by the site panel)
         *
         * @param {Object} Project - classes/projects/Project
         */
        setProject: function (Project) {
            this.setAttribute('project', Project.getName());

            if (this.$TypeInput) {
                this.$TypeInput.setProject(Project);
            }

            this.$checkDefault();
        },

        /**
         * If no override is set, display the inherited child type as guidance
         */
        $checkDefault: function () {
            const Site = this.getAttribute('Site');

            if (!this.$Input || !Site || this.$Input.value) {
                return;
            }

            const Project = Site.getProject();

            QUIAjax.get('ajax_site_children_getChildType', (childType) => {
                if (!this.$Input || this.$Input.value || !childType) {
                    return;
                }

                this.$Input.value = childType;
                this.$TypeInput.loadTypeName();
                this.$Input.value = '';
            }, {
                project: Project.encode(),
                siteId : Site.getId()
            });
        }
    });
});
