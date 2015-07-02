
/**
 * Permissions Panel -> Site
 *
 * @module controls/permissions/Site
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 */
define('controls/permissions/Site', [

    'controls/permissions/Permission'

], function(Permission)
{
    "use strict";

    return new Class({

        Extends: Permission,
        Types: 'controls/permissions/Site',

        initialize : function(Site, options)
        {
            this.parent(Site, options);

            if (typeOf(Site) === 'classes/projects/project/Site') {
                this.$Bind = Site;
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
                    'controls/projects/Popup',
                    'Projects'
                ], function(Popup, Projects) {

                    new Popup({
                        events : {
                            onSubmit : function(Popup, data) {

                                var Project = Projects.get(data.project, data.lang);

                                self.$Bind = Project.get(data.ids[0]);
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