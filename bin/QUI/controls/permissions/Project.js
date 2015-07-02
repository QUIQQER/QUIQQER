
/**
 * Permissions Panel -> Project
 *
 * @module controls/permissions/Project
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 */
define('controls/permissions/Project', [

    'controls/permissions/Permission'

], function(Permission)
{
    "use strict";

    return new Class({

        Extends: Permission,
        Types: 'controls/permissions/Project',

        initialize : function(Project, options)
        {
            this.parent(Project, options);

            if (typeOf(Project) === 'classes/projects/Project') {
                this.$Bind = Project;
            }
        },

        /**
         * User select
         *
         * @returns {Promise}
         */
        $openBindSelect : function()
        {
            var self = this;

            return new Promise(function(resolve, reject) {

                require([
                    'controls/projects/SelectWindow',
                    'Projects'
                ], function(Popup, Projects) {

                    new Popup({
                        events : {
                            onSubmit : function(Popup, data) {

                                self.$Bind = Projects.get(data.project, data.lang);
                                resolve();
                            },

                            onCancel : function() {
                                reject();
                            }
                        }
                    }).open();
                });

            });
        }
    });
});