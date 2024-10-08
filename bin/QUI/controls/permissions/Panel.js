/**
 * Permissions Panel
 *
 * @module controls/permissions/Panel
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/permissions/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'Locale',

    'css!controls/permissions/Panel.css'

], function (QUI, QUIPanel, QUILocale) {
    "use strict";

    var lg = 'quiqqer/core';

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/permissions/Panel',

        Binds: [
            '$onCreate',
            '$onShow',
            'openUserPermissions',
            'openGroupPermissions',
            'openSitePermissions',
            'openProjectPermissions',
            'openMediaPermissions',
            'openEditPermissions'
        ],

        options: {
            Object: false
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttribute(
                'title',
                QUILocale.get(lg, 'permissions.panel.title')
            );

            this.setAttribute('icon', 'fa fa-shield');
            this.$PermissionControl = null;

            this.addEvents({
                onCreate : this.$onCreate,
                onShow   : this.$onShow,
                onDestroy: function () {
                    if (this.$PermissionControl) {
                        this.$PermissionControl.destroy();
                    }
                }.bind(this)
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            this.addCategory({
                name  : 'user',
                text  : QUILocale.get(lg, 'permissions.panel.btn.select.user'),
                title : QUILocale.get(lg, 'permissions.panel.btn.select.user'),
                icon  : 'fa fa-user',
                events: {
                    onClick: this.openUserPermissions
                }
            });

            this.addCategory({
                name  : 'group',
                text  : QUILocale.get(lg, 'permissions.panel.btn.select.group'),
                title : QUILocale.get(lg, 'permissions.panel.btn.select.group'),
                icon  : 'fa fa-group',
                events: {
                    onClick: this.openGroupPermissions
                }
            });

            this.addCategory({
                name  : 'site',
                text  : QUILocale.get(lg, 'permissions.panel.btn.select.site'),
                title : QUILocale.get(lg, 'permissions.panel.btn.select.site'),
                icon  : 'fa fa-file-o',
                events: {
                    onClick: this.openSitePermissions
                }
            });

            this.addCategory({
                name  : 'project',
                text  : QUILocale.get(lg, 'permissions.panel.btn.select.project'),
                title : QUILocale.get(lg, 'permissions.panel.btn.select.project'),
                icon  : 'fa fa-home',
                events: {
                    onClick: this.openProjectPermissions
                }
            });

            this.addCategory({
                name  : 'media',
                text  : QUILocale.get(lg, 'permissions.panel.btn.select.media'),
                title : QUILocale.get(lg, 'permissions.panel.btn.select.media'),
                icon  : 'fa fa-picture-o',
                events: {
                    onClick: this.openMediaPermissions
                }
            });

            this.addCategory({
                name  : 'edit',
                text  : QUILocale.get(lg, 'permissions.panel.btn.select.manage'),
                title : QUILocale.get(lg, 'permissions.panel.btn.select.manage'),
                icon  : 'fa fa-gears',
                events: {
                    onClick: this.openEditPermissions
                }
            });

            this.getContent().setStyles({
                padding: 0
            });
        },

        /**
         * event: on open
         */
        $onShow: function () {
            if (this.getAttribute('Object')) {
                switch (typeOf(this.getAttribute('Object'))) {
                    case 'classes/users/User':
                        return this.openUserPermissions(this.getAttribute('Object'));

                    case 'classes/groups/Group':
                        return this.openGroupPermissions(this.getAttribute('Object'));

                    case 'classes/projects/Project':
                        return this.openProjectPermissions(this.getAttribute('Object'));

                    case 'classes/projects/project/Site':
                        return this.openSitePermissions(this.getAttribute('Object'));

                    case 'classes/projects/project/media/File':
                    case 'classes/projects/project/media/Folder':
                    case 'classes/projects/project/media/Image':
                    case 'classes/projects/project/media/Item':
                        return this.openMediaPermissions(this.getAttribute('Object'));
                }
            }

            (function () {
                this.openWelcomeMessage().catch(function (err) {
                    console.error(err);
                });
            }).delay(200, this);
        },

        /**
         * Shows the welcome message and close all permissions controls
         *
         * @returns {Promise}
         */
        openWelcomeMessage: function () {
            var self = this;

            return new Promise(function (resolve) {
                self.$closeLastPermissionControl().then(function () {
                    var Container = new Element('div', {
                        'class': 'controls-permissions-panel-welcome',
                        html   : QUILocale.get(lg, 'permissions.panel.welcome.message')
                    }).inject(self.getContent());

                    new Element('img', {
                        src   : URL_OPT_DIR + 'quiqqer/core/bin/images/QMan/security.svg',
                        styles: {
                            width: 250
                        }
                    }).inject(Container);

                    moofx(Container).animate({
                        opacity: 1
                    }, {
                        duration: 250,
                        callback: function () {
                            self.getCategoryBar()
                                .getChildren()
                                .each(function (Category) {
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
        openUserPermissions: function (User) {
            this.$openPermissionControl(User, 'user');
        },

        /**
         * Permission of a group
         *
         * @param {Object} [Group] - classes/groups/Group
         */
        openGroupPermissions: function (Group) {
            this.$openPermissionControl(Group, 'group');
        },

        /**
         * Permission of a site
         *
         * @param {Object} [Site] - classes/projects/project/Site
         */
        openSitePermissions: function (Site) {
            this.$openPermissionControl(Site, 'site');
        },

        /**
         * Permission of a project
         *
         * @param {Object} [Project] - classes/projects/Project
         */
        openProjectPermissions: function (Project) {
            this.$openPermissionControl(Project, 'project');
        },

        /**
         * Permission of a project
         *
         * @param {Object} [Media] - classes/projects/Media
         */
        openMediaPermissions: function (Media) {
            this.$openPermissionControl(Media, 'media');
        },

        /**
         * Permission edit
         */
        openEditPermissions: function () {
            this.$openPermissionControl(null, 'edit');
        },

        /**
         * Opens the permissions
         *
         * @param {Object} Bind - Bind object eq: classes/projects/Project, classes/projects/project/Site ...
         * @param {String} type
         */
        $openPermissionControl: function (Bind, type) {
            if (typeof Bind === 'undefined') {
                Bind = this.getAttribute('Bind');
            }

            var self = this,
                Bar  = this.getCategoryBar();

            self.$closeLastPermissionControl().then(function () {
                return new Promise(function (resolve, reject) {
                    self.Loader.show();

                    var Button = false,
                        needle = false;

                    switch (type) {
                        case 'user':
                            Button = Bar.getChildren('user');
                            needle = 'controls/permissions/User';
                            break;

                        case 'group':
                            Button = Bar.getChildren('group');
                            needle = 'controls/permissions/Group';
                            break;

                        case 'project':
                            Button = Bar.getChildren('project');
                            needle = 'controls/permissions/Project';
                            break;

                        case 'site':
                            Button = Bar.getChildren('site');
                            needle = 'controls/permissions/Site';
                            break;

                        case 'edit':
                            Button = Bar.getChildren('edit');
                            needle = 'controls/permissions/Edit';
                            break;

                        case 'media':
                            Button = Bar.getChildren('media');
                            needle = 'controls/permissions/Media';
                            break;
                    }

                    if (!needle) {
                        return reject();
                    }

                    if (!Button.isActive()) {
                        Button.setActive();
                    }

                    require([needle], function (Permission) {
                        self.minimizeCategory().then(function () {
                            self.$PermissionControl = new Permission(Bind, {
                                Panel : self,
                                events: {
                                    onLoad     : resolve,
                                    onLoadError: reject
                                }
                            }).inject(self.getContent());

                            self.Loader.hide();
                        });
                    });
                });
            }).catch(function () {
                self.openWelcomeMessage().catch(function (e) {
                    console.error(e);
                });
            });
        },

        /**
         * Close the last permission control
         *
         * @returns {Promise}
         */
        $closeLastPermissionControl: function () {
            var Welcome = this.getContent().getElement('.controls-prmissions-panel-welcome');

            if (Welcome) {
                return new Promise(function (resolved) {
                    moofx(Welcome).animate({
                        opacity: 0
                    }, {
                        duration: 250,
                        callback: function () {
                            Welcome.destroy();

                            this.$PermissionControl = null;
                            this.getContent().set('html');

                            resolved();
                        }.bind(this)
                    });
                }.bind(this));
            }

            if (this.$PermissionControl) {
                return this.$PermissionControl.close().then(function () {
                    this.$PermissionControl = null;
                    this.getContent().set('html');
                }.bind(this));
            }

            return new Promise(function (resolved) {
                this.$PermissionControl = null;
                this.getContent().set('html');
                resolved();
            }.bind(this));
        }
    });
});
