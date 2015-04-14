
/**
 * CustomCSS for a project
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/projects/project/settings/CustomCSS', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax'

], function(QUI, QUIControl, QUIAjax)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/settings/CustomCSS',

        Binds : [
            '$onInject'
        ],

        options : {
            css : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Textarea = null;
            this.$Project  = this.getAttribute( 'Project' );

            this.addEvents({
                onInject : this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create : function()
        {
            this.$Elm = this.parent();

            this.$Elm.set({
                'class' : 'control-project-setting-custom-css',
                html    : '<textarea></textarea>',
                styles  : {
                    'float' : 'left',
                    height  : '100%',
                    width   : '100%'
                }
            });

            this.$Textarea = this.$Elm.getElement( 'textarea' );

            this.$Textarea.set({
                name   : 'project-custom-css',
                styles : {
                    'float' : 'left',
                    height  : '100%',
                    width   : '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            var self = this;

            if ( this.getAttribute( 'css' ) )
            {
                this.$Textarea.value = this.getAttribute( 'css' );
                this.fireEvent( 'load' );
                return;
            }

            QUIAjax.get('ajax_project_get_customCSS', function(css)
            {
                self.$Textarea.value = css;
                self.fireEvent( 'load' );
            }, {
                project : this.$Project.encode()
            });
        },

        /**
         * Set the project
         *
         * @param {Object} Project - classes/projects/Project
         */
        setProject : function(Project)
        {
            this.$Project = Project;
        }
    });
});