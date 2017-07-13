/**
 * Permission Controller
 *
 * @author www.pcsg.de (Henning Leutz
 * @module classes/permissions/Permissions
 *
 * @require qui/classes/DOM
 * @require Ajax
 */
define('classes/permissions/Permissions', [

    'qui/classes/DOM',
    'Ajax'

], function (QUIDOM, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'classes/permissions/Permissions',

        initialize: function (options) {
            this.parent(options);

            this.$list = null;

            this.$cache = {
                users   : {},
                groups  : {},
                sites   : {},
                projects: {}
            };
        },

        /**
         * has the session user the permission?
         *
         * @param {String} permission - wanted permission
         * @return {Promise}
         */
        hasPermission: function (permission) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_permissions_session_hasPermission', resolve, {
                    onError   : reject,
                    permission: permission
                });
            });
        },

        /**
         * Return the permission list
         *
         * @returns {Promise}
         */
        getList: function () {
            return new Promise(function (resolve, reject) {

                if (this.$list) {
                    resolve(this.$list);
                    return;
                }

                QUIAjax.get('ajax_permissions_list', function (result) {

                    this.$list = result;

                    resolve(result);

                }.bind(this), {
                    onError: reject
                });

            }.bind(this));
        },

        /**
         * Return the permission list of an object
         *
         * @param {Object} [Bind] - Bind object -> User, Group, Site, Project
         * @returns {Promise}
         */
        getPermissionsByObject: function (Bind) {
            switch (typeOf(Bind)) {
                case 'classes/users/User':
                    return this.getUserPermissionList(Bind);

                case 'classes/groups/Group':
                    return this.getGroupPermissionList(Bind);

                case 'classes/projects/Project':
                    return this.getProjectPermissionList(Bind);

                case 'classes/projects/project/Site':
                    return this.getSitePermissionList(Bind);

                case 'qui/classes/DOM':
                    return this.getList();
            }

            return new Promise(function (resolve, reject) {
                reject('Bind Type not found: ' + typeOf(Bind));
            });
        },

        /**
         * Return the permission list of an user
         *
         * @param {Object} User - classes/groups/Group
         * @returns {Promise}
         */
        getUserPermissionList: function (User) {
            var self = this;

            return new Promise(function (resolve, reject) {

                if (typeof User === 'undefined') {
                    reject();
                    return;
                }

                if (User.getId() in self.$cache.users) {
                    resolve(self.$cache.users[User.getId()]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function (permissions) {
                    self.$cache.users[User.getId()] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        id: User.getId()
                    }),
                    btype  : User.getType(),
                    onError: reject
                });
            });
        },

        /**
         * Return the permission list of an user
         *
         * @param {Object} Group - classes/groups/Group
         * @returns {Promise}
         */
        getGroupPermissionList: function (Group) {
            var self = this;

            return new Promise(function (resolve, reject) {

                if (typeof Group === 'undefined') {
                    reject();
                    return;
                }

                if (Group.getId() in self.$cache.groups) {
                    resolve(self.$cache.groups[Group.getId()]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function (permissions) {
                    self.$cache.groups[Group.getId()] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        id: Group.getId()
                    }),
                    btype  : Group.getType(),
                    onError: reject
                });
            });
        },

        /**
         * Return the permission list of a project
         *
         * @param {Object} Project - classes/projects/Project
         * @returns {Promise}
         */
        getProjectPermissionList: function (Project) {
            var self = this;

            return new Promise(function (resolve, reject) {

                if (typeof Project === 'undefined') {
                    reject();
                    return;
                }

                var cacheName = Project.getName() + '-' + Project.getLang();

                if (cacheName in self.$cache.projects) {
                    resolve(self.$cache.projects[cacheName]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function (permissions) {
                    self.$cache.projects[cacheName] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        project: Project.getName()
                    }),
                    btype  : Project.getType(),
                    onError: reject
                });
            });
        },

        /**
         * Return the permission list of a site
         *
         * @param {Object} Site - classes/projects/project/Site
         * @returns {Promise}
         */
        getSitePermissionList: function (Site) {
            var self = this;

            return new Promise(function (resolve, reject) {

                if (typeof Site === 'undefined') {
                    reject();
                    return;
                }

                var Project   = Site.getProject();
                var cacheName = Project.getName() + '-' + Project.getLang() + '-' + Site.getId();

                if (cacheName in self.$cache.sites) {
                    resolve(self.$cache.sites[cacheName]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function (permissions) {
                    self.$cache.sites[cacheName] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        id     : Site.getId(),
                        project: Project.getName(),
                        lang   : Project.getLang()
                    }),
                    btype  : Site.getType(),
                    onError: reject
                });
            });
        },

        /**
         * Set a permission
         *
         * @param Bind
         * @param name
         * @param value
         *
         * @returns {Promise}
         */
        setPermission: function (Bind, name, value) {
            return new Promise(function (resolve, reject) {
                switch (typeOf(Bind)) {
                    case 'classes/users/User':
                        return this.setUserPermission(Bind, name, value);

                    case 'classes/groups/Group':
                        return this.setGroupPermission(Bind, name, value);

                    case 'classes/projects/Project':
                        return this.setProjectPermission(Bind, name, value);

                    case 'classes/projects/project/Site':
                        return this.setSitePermission(Bind, name, value);

                    default:
                        reject('Bind Type not found: ' + typeOf(Bind));
                }

            }.bind(this));
        },

        /**
         * Set a permission for a site
         *
         * @param {Object} Site
         * @param {String} permission
         * @param {String|Number|*} value
         *
         * @return Promise
         */
        setSitePermission: function (Site, permission, value) {
            var self      = this,
                Project   = Site.getProject(),
                cacheName = Project.getName() + '-' + Project.getLang() + '-' + Site.getId();

            if (cacheName in this.$cache.sites) {

                return new Promise(function (resolve) {
                    self.$cache.sites[cacheName][permission] = value;
                    resolve();
                });
            }

            return this.getSitePermissionList(Site).then(function () {
                self.$cache.sites[cacheName][permission] = value;
            });
        },

        /**
         * Set a permission for a project
         *
         * @param {Object} Project
         * @param {String} permission
         * @param {String|Number|*} value
         *
         * @return Promise
         */
        setProjectPermission: function (Project, permission, value) {
            var self      = this,
                cacheName = Project.getName() + '-' + Project.getLang();

            if (cacheName in this.$cache.projects) {

                return new Promise(function (resolve) {
                    self.$cache.projects[cacheName][permission] = value;
                    resolve();
                });
            }

            return this.getProjectPermissionList(Project).then(function () {
                self.$cache.projects[cacheName][permission] = value;
            });
        },

        /**
         * Set a permission for a group
         *
         * @param {Object} Group
         * @param {String} permission
         * @param {String|Number|*} value
         *
         * @return Promise
         */
        setGroupPermission: function (Group, permission, value) {
            var self      = this,
                cacheName = Group.getId();

            if (cacheName in this.$cache.groups) {

                return new Promise(function (resolve) {
                    self.$cache.groups[cacheName][permission] = value;
                    resolve();
                });
            }

            return this.getGroupPermissionList(Group).then(function () {
                self.$cache.groups[cacheName][permission] = value;
            });
        },

        /**
         * Set a permission for an user
         *
         * @param {Object} User
         * @param {String} permission
         * @param {String|Number|*} value
         *
         * @return Promise
         */
        setUserPermission: function (User, permission, value) {
            var self      = this,
                cacheName = User.getId();

            if (cacheName in this.$cache.users) {

                return new Promise(function (resolve) {
                    self.$cache.users[cacheName][permission] = value;
                    resolve();
                });
            }

            return this.getUserPermissionList(User).then(function () {
                self.$cache.users[cacheName][permission] = value;
            });
        },

        /**
         * Delete a permission
         * only user created permissions can be deleted
         *
         * @param {String} permission - permission name
         * @param {Function} [callback] - callback function
         *
         * @returns {Promise}
         */
        deletePermission: function (permission, callback) {
            return new Promise(function (resolve, reject) {

                QUIAjax.post('ajax_permissions_delete', function () {
                    this.$list = null;

                    this.$cache = {
                        users   : {},
                        groups  : {},
                        sites   : {},
                        projects: {}
                    };

                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }

                }.bind(this), {
                    permission: permission,
                    onError   : reject
                });

            }.bind(this));
        },

        /**
         * Add a user generated permission
         *
         * @param {String} permission
         * @param {String} area
         * @param {String} type
         * @returns {Promise}
         */
        addPermission: function (permission, area, type) {
            return new Promise(function (resolve, reject) {

                QUIAjax.post('ajax_permissions_add', function (result) {
                    this.$list = null;

                    switch (area) {

                        case 'site':
                            this.$cache.sites = {};
                            break;

                        case 'project':
                            this.$cache.projects = {};
                            break;

                        default:
                            this.$cache.users  = {};
                            this.$cache.groups = {};
                    }

                    resolve(result);

                }.bind(this), {
                    permission    : permission,
                    area          : area,
                    permissiontype: type,
                    onError       : reject
                });

            }.bind(this));
        },

        /**
         * Save a permission
         *
         * @param {Object} Bind - Bind object => classes/users/User,
         *                                       classes/groups/Group,
         *                                       classes/projects/Project,
         *                                       classes/projects/project/Site
         * @returns {Promise}
         */
        savePermission: function (Bind) {
            return new Promise(function (resolve, reject) {
                switch (typeOf(Bind)) {
                    case 'classes/users/User':
                        return this.saveUserPermission(Bind).then(resolve);

                    case 'classes/groups/Group':
                        return this.saveGroupPermission(Bind).then(resolve);

                    case 'classes/projects/Project':
                        return this.saveProjectPermission(Bind).then(resolve);

                    case 'classes/projects/project/Site':
                        return this.saveSitePermission(Bind).then(resolve);

                    default:
                        console.error('savePermission: Bind Type not found');
                        reject('Bind Type not found: ' + typeOf(Bind));
                }
            }.bind(this));
        },

        /**
         * Save user permission
         *
         * @param {Object} User - classes/users/User
         * @returns {Promise}
         */
        saveUserPermission: function (User) {
            var self      = this,
                cacheName = User.getId();

            return new Promise(function (resolve, reject) {

                if (!(cacheName in self.$cache.users)) {
                    resolve();
                    return;
                }

                QUIAjax.post('ajax_permissions_save', function () {
                    resolve();
                }, {
                    params     : JSON.encode({
                        id: User.getId()
                    }),
                    btype      : User.getType(),
                    permissions: JSON.encode(self.$cache.users[cacheName]),
                    onError    : reject
                });

            });
        },

        /**
         * Save group permission
         *
         * @param {Object} Group - classes/groups/Group
         * @returns {Promise}
         */
        saveGroupPermission: function (Group) {
            var self      = this,
                cacheName = Group.getId();

            return new Promise(function (resolve, reject) {

                if (!(cacheName in self.$cache.groups)) {
                    resolve();
                    return;
                }

                QUIAjax.post('ajax_permissions_save', function () {
                    resolve();
                }, {
                    params     : JSON.encode({
                        id: Group.getId()
                    }),
                    btype      : Group.getType(),
                    permissions: JSON.encode(self.$cache.groups[cacheName]),
                    onError    : reject
                });

            });
        },

        /**
         * Save project permission
         *
         * @param {Object} Project - classes/projects/Project
         * @returns {Promise}
         */
        saveProjectPermission: function (Project) {
            var self      = this,
                cacheName = Project.getName() + '-' + Project.getLang();

            return new Promise(function (resolve, reject) {

                if (!(cacheName in self.$cache.projects)) {
                    resolve();
                    return;
                }

                QUIAjax.post('ajax_permissions_save', function () {
                    resolve();
                }, {
                    params     : JSON.encode({
                        project: Project.getName()
                    }),
                    btype      : Project.getType(),
                    permissions: JSON.encode(self.$cache.projects[cacheName]),
                    onError    : reject
                });

            });
        },

        /**
         * Save project permission
         *
         * @param {Object} Site - classes/projects/prject/Site
         * @returns {Promise}
         */
        saveSitePermission: function (Site) {
            var self      = this,
                Project   = Site.getProject(),
                cacheName = Project.getName() + '-' + Project.getLang() + '-' + Site.getId();

            return new Promise(function (resolve, reject) {

                if (!(cacheName in self.$cache.sites)) {
                    resolve();
                    return;
                }

                QUIAjax.post('ajax_permissions_save', function () {
                    resolve();
                }, {
                    params     : JSON.encode({
                        project: Project.getName(),
                        lang   : Project.getLang(),
                        id     : Site.getId()
                    }),
                    btype      : Site.getType(),
                    permissions: JSON.encode(self.$cache.sites[cacheName]),
                    onError    : reject
                });
            });
        }
    });
});
