/**
 * CustomJavaScript for a project
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/projects/project/settings/CustomJS', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax'

], function (QUI, QUIControl, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/settings/CustomJS',

        Binds: [
            '$onInject'
        ],

        options: {
            javascript: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Editor = null;
            this.$Textarea = null;
            this.$Project = this.getAttribute('Project');

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = this.parent();

            this.$Elm.set({
                'class': 'control-project-setting-custom-js',
                html   : '<textarea></textarea>',
                styles : {
                    border : '1px solid rgb(213 213 213)',
                    'float': 'left',
                    height : '100%',
                    width  : '100%'
                }
            });

            this.$Textarea = this.$Elm.getElement('textarea');

            this.$Textarea.set({
                name  : 'project-custom-javascript',
                styles: {
                    display: 'none'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            const Panel = QUI.Controls.getById(
                this.getElm().getParent('.qui-panel').get('data-quiid')
            );

            const Loader = Panel.Loader;
            Loader.show();

            require(['controls/editors/CodeEditor'], (CodeEditor) => {
                this.$Editor = new CodeEditor({
                    type: 'javascript'
                }).inject(this.getElm());

                if (this.getAttribute('javascript')) {
                    this.$Editor.setValue(this.getAttribute('javascript'));
                    this.$Textarea.value = this.$Editor.getValue();
                    this.fireEvent('load');
                    Loader.hide();
                    return;
                }

                QUIAjax.get('ajax_project_get_customJavaScript', (javascript) => {
                    this.$Editor.setValue(javascript);
                    this.$Textarea.value = this.$Editor.getValue();
                    this.fireEvent('load');
                    Loader.hide();
                }, {
                    project: this.$Project.encode()
                });
            });
        },

        /**
         * Set the project
         *
         * @param {Object} Project - classes/projects/Project
         */
        setProject: function (Project) {
            this.$Project = Project;
        },

        /**
         * set the editor value to the textarea
         *
         * @return string
         */
        save: function () {
            this.$Textarea.value = this.$Editor.getValue();
            return this.$Editor.getValue();
        }
    });
});
