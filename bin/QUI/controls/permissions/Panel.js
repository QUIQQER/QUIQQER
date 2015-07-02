
/**
 * Permissions Panel
 *
 * @module controls/permissions/Panel
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/permissions/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'Locale'

], function(QUI, QUIPanel, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/system';


    return new Class({

        Extends: QUIPanel,
        Types : 'controls/permissions/Panel',

        Binds : [
            '$onCreate',
            'openUserPermissions',
            'openGroupPermissions',
            'openSitePermissions',
            'openProjectPermissions',
            'openEditPermissions'
        ],

        options : {
            Object : false
        },

        initialize : function(options)
        {
            this.setAttribute(
                'title',
                QUILocale.get(lg, 'permissions.panel.title')
            );

            this.setAttribute('icon', 'icon-gears');
            this.parent(options);

            this.$PermissionControl = null;

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * event : on create
         */
        $onCreate : function()
        {
            this.addCategory({
                text   : QUILocale.get(lg, 'permissions.panel.btn.select.user'),
                title  : QUILocale.get(lg, 'permissions.panel.btn.select.user'),
                icon   : 'icon-user',
                events : {
                    onClick : this.openUserPermissions
                }
            });

            this.addCategory({
                text   : QUILocale.get(lg, 'permissions.panel.btn.select.group'),
                title  : QUILocale.get(lg, 'permissions.panel.btn.select.group'),
                icon   : 'icon-group',
                events : {
                    onClick : this.openGroupPermissions
                }
            });

            this.addCategory({
                text   : QUILocale.get(lg, 'permissions.panel.btn.select.site'),
                title  : QUILocale.get(lg, 'permissions.panel.btn.select.site'),
                icon   : 'fa fa-file-o icon-file-alt',
                events : {
                    onClick : this.openSitePermissions
                }
            });

            this.addCategory({
                text   : QUILocale.get(lg, 'permissions.panel.btn.select.project'),
                title  : QUILocale.get(lg, 'permissions.panel.btn.select.project'),
                icon   : 'icon-home',
                events : {
                    onClick : this.openProjectPermissions
                }
            });

            this.addCategory({
                text   : QUILocale.get(lg, 'permissions.panel.btn.select.manage'),
                title  : QUILocale.get(lg, 'permissions.panel.btn.select.manage'),
                icon   : 'icon-gears',
                events : {
                    onClick : this.openEditPermissions
                }
            });

            this.getContent().setStyles({
                padding : 0
            });
        },

        openWelcomeMessage : function()
        {
            var self = this;

            return new Promise(function(resolve)
            {
                self.$closeLastPermissionControl().then(function ()
                {
                    var Container = new Element('div', {
                        'class' : 'controls-prmissions-panel-welcome',
                        html : QUILocale.get(lg, 'permissions.panel.welcome.message'),
                        styles : {
                            left     : '-100',
                            opacity  : 0,
                            padding  : 20,
                            position : 'absolute',
                            top      : 0
                        }
                    }).inject(self.getContent());

                    moofx(Container).animate({
                        left : 0,
                        opacity : 1
                    }, {
                        duration : 250,
                        equation : 'ease-in-out',
                        callback : function() {

                            self.getCategoryBar()
                                .getChildren()
                                .each(function(Category) {
                                    Category.setNormal();
                            });

                            resolve();
                        }
                    });
                });
            });
        },

        /**
         * Permission of an user
         *
         * @param {Object} [User] - classes/users/User
         */
        openUserPermissions : function(User)
        {
            this.$openPermissionControl(User, 'user');
        },

        /**
         * Permission of a group
         *
         * @param {Object} [Group] - classes/groups/Group
         */
        openGroupPermissions : function(Group)
        {
            this.$openPermissionControl(Group, 'group');
        },

        /**
         * Permission of a site
         *
         * @param {Object} [Site] - classes/projects/project/Site
         */
        openSitePermissions : function(Site)
        {
            this.$openPermissionControl(Site, 'site');
        },

        /**
         * Permission of a project
         *
         * @param {Object} [Project] - classes/projects/Project
         */
        openProjectPermissions : function(Project)
        {
            this.$openPermissionControl(Project, 'project');
        },

        /**
         * Permission edit
         */
        openEditPermissions : function()
        {
            this.$closeLastPermissionControl().then(function()
            {

            });
        },

        /**
         *
         * @param Bind
         * @param type
         */
        $openPermissionControl : function(Bind, type)
        {
            if (typeof Bind === 'undefined') {
                Bind = this.getAttribute('Bind');
            }

            var self = this;

            self.$closeLastPermissionControl().then(function()
            {
                return new Promise(function(resolve, reject) {

                    self.Loader.show();

                    var needle = false;

                    switch (type) {
                        case 'user':
                            needle = 'controls/permissions/User';
                            break;

                        case 'group':
                            needle = 'controls/permissions/Group';
                            break;

                        case 'project':
                            needle = 'controls/permissions/Project';
                            break;

                        case 'site':
                            needle = 'controls/permissions/Site';
                            break;
                    }

                    if (!needle) {
                        return reject();
                    }

                    require([needle], function(Permission) {

                        self.minimizeCategory().then(function() {

                            self.$PermissionControl = new Permission(Bind, {
                                events : {
                                    onLoad : resolve,
                                    onLoadError : reject
                                }
                            }).inject(self.getContent());

                            self.Loader.hide();
                        });
                    });
                });

            }).catch(function()
            {
                self.openWelcomeMessage();
            });
        },

        /**
         * Close the last permission control
         *
         * @returns {Promise}
         */
        $closeLastPermissionControl : function()
        {
            var Welcome = this.getContent().getElement(
                '.controls-prmissions-panel-welcome'
            );

            if (Welcome) {

                return new Promise(function(resolved) {

                    moofx(Welcome).animate({
                        left : '-100%',
                        opacity : 0
                    }, {
                        duration : 250,
                        equation : 'ease-in-out',
                        callback : function() {

                            Welcome.destroy();

                            this.$PermissionControl = null;
                            this.getContent().set('html');

                            resolved();

                        }.bind(this)
                    });

                }.bind(this));
            }

            if (this.$PermissionControl) {

                return this.$PermissionControl.close().then(function() {
                    this.$PermissionControl = null;
                    this.getContent().set('html');
                }.bind(this));

            }

            return new Promise(function(resolved) {

                this.$PermissionControl = null;
                this.getContent().set('html');
                resolved();

            }.bind(this));
        }
    });
});