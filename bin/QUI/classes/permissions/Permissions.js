
/**
 * Permission Controler
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

], function(QUIDOM, QUIAjax)
{
    "use strict";


    return new Class({

        Extends : QUIDOM,
        Type : 'classes/permissions/Permissions',

        initialize : function(options)
        {
            this.parent(options);

            this.$list = null;
            this.$cache = {
                users    : {},
                groups   : {},
                sites    : {},
                projects : {}
            };
        },

        /**
         * Return the permission list
         *
         * @returns {Promise}
         */
        getList : function()
        {
            return new Promise(function(resolve, reject) {

                if (this.$list) {
                    resolve(this.$list);
                    return;
                }

                QUIAjax.get('ajax_permissions_list', function(result) {

                    this.$list = result;

                    resolve(result);

                }.bind(this), {
                    onError : reject
                });

            }.bind(this));
        },

        /**
         * Return the permission list of an object
         *
         * @param {Object} [Bind] - Bind object -> User, Group, Site, Project
         * @returns {Promise}
         */
        getPermissionsByObject : function(Bind)
        {
            switch (typeOf(Bind)) {
                case 'classes/users/User':
                    return this.getUserPermissionList(Bind);

                case 'classes/groups/Group':
                    return this.getGroupPermissionList(Bind);

                case 'classes/projects/Project':
                    return this.getProjectPermissionList(Bind);

                case 'classes/projects/project/Site':
                    return this.getSitePermissionList(Bind);
            }

            return new Promise(function(resolve, reject) {
                reject('Bind Type not found: '+ typeOf(Bind));
            });
        },

        /**
         * Return the permission list of an user
         *
         * @param {Object} User - classes/groups/Group
         * @returns {Promise}
         */
        getUserPermissionList : function(User)
        {
            var self = this;

            return new Promise(function(resolve, reject) {

                if (typeof User === 'undefined') {
                    reject();
                    return;
                }

                if (User.getId() in self.$cache.users) {
                    resolve(self.$cache.users[User.getId()]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function(permissions)
                {
                    self.$cache.users[User.getId()] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        id : User.getId()
                    }),
                    btype   : User.getType(),
                    onError : reject
                });
            });
        },

        /**
         * Return the permission list of an user
         *
         * @param {Object} Group - classes/groups/Group
         * @returns {Promise}
         */
        getGroupPermissionList : function(Group)
        {
            var self = this;

            return new Promise(function(resolve, reject) {

                if (typeof Group === 'undefined') {
                    reject();
                    return;
                }

                if (Group.getId() in self.$cache.groups) {
                    resolve(self.$cache.groups[Group.getId()]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function(permissions)
                {
                    self.$cache.groups[Group.getId()] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        id : Group.getId()
                    }),
                    btype   : Group.getType(),
                    onError : reject
                });
            });
        },

        /**
         * Return the permission list of a project
         *
         * @param {Object} Project - classes/projects/Project
         * @returns {Promise}
         */
        getProjectPermissionList : function(Project)
        {
            var self = this;

            return new Promise(function(resolve, reject) {

                if (typeof Project === 'undefined') {
                    reject();
                    return;
                }

                var cacheName = Project.getName()+'-'+Project.getLang();

                if (cacheName in self.$cache.projects) {
                    resolve(self.$cache.projects[cacheName]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function(permissions)
                {
                    self.$cache.projects[cacheName] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        project : Project.getName()
                    }),
                    btype   : Project.getType(),
                    onError : reject
                });
            });
        },

        /**
         * Return the permission list of a site
         *
         * @param {Object} Site - classes/projects/project/Site
         * @returns {Promise}
         */
        getSitePermissionList : function(Site)
        {
            var self = this;

            return new Promise(function(resolve, reject) {

                if (typeof Site === 'undefined') {
                    reject();
                    return;
                }

                var Project = Site.getProject();
                var cacheName = Project.getName()+'-'+Project.getLang()+'-'+Site.getId();

                if (cacheName in self.$cache.sites) {
                    resolve(self.$cache.sites[cacheName]);
                    return;
                }

                QUIAjax.get('ajax_permissions_get', function(permissions)
                {
                    self.$cache.sites[cacheName] = permissions;

                    resolve(permissions);
                }, {
                    params : JSON.encode({
                        id      : Site.getId(),
                        project : Project.getName(),
                        lang    : Project.getLang()
                    }),
                    btype   : Site.getType(),
                    onError : reject
                });
            });
        },

        /**
         * Set a permission
         *
         * @param Bind
         * @param name
         * @param value
         * @returns {Promise}
         */
        setPermission : function(Bind, name, value)
        {
            return new Promise(function(resolve, reject)
            {
                switch (typeOf(Bind)) {
                    case 'classes/users/User':
                        this.setUserPermission(Bind, name, value);
                        break;

                    case 'classes/groups/Group':
                        this.setGroupPermission(Bind, name, value);
                        break;

                    case 'classes/projects/Project':
                        this.setProjectPermission(Bind, name, value);
                        break;

                    case 'classes/projects/project/Site':
                        this.setSitePermission(Bind, name, value);
                        break;

                    default:
                        return reject('Bind Type not found: '+ typeOf(Bind));
                }

            }.bind(this));
        },

        /**
         * Set a permission for a site
         *
         * @param {Object} Site
         * @param {String} permission
         * @param {String|Number|*} value
         */
        setSitePermission : function(Site, permission, value)
        {
            var Project = Site.getProject();
            var cacheName = Project.getName()+'-'+Project.getLang()+'-'+Site.getId();

            if (cacheName in this.$cache.sites) {
                this.$cache.sites[cacheName][permission] = value;
                return;
            }

            this.getSitePermissionList(Site).then(function() {
                this.$cache.sites[cacheName][permission] = value;
            }.bind(this));
        },

        /**
         * Set a permission for a project
         *
         * @param {Object} Project
         * @param {String} permission
         * @param {String|Number|*} value
         */
        setProjectPermission : function(Project, permission, value)
        {
            var cacheName = Project.getName()+'-'+Project.getLang();

            if (cacheName in this.$cache.projects) {
                this.$cache.projects[cacheName][permission] = value;
                return;
            }

            this.getProjectPermissionList(Project).then(function() {
                this.$cache.projects[cacheName][permission] = value;
            }.bind(this));
        },

        /**
         * Set a permission for a group
         *
         * @param {Object} Group
         * @param {String} permission
         * @param {String|Number|*} value
         */
        setGroupPermission : function(Group, permission, value)
        {
            var cacheName = Group.getId();

            if (cacheName in this.$cache.groups) {
                this.$cache.groups[cacheName][permission] = value;
                return;
            }

            this.getGroupPermissionList(Group).then(function() {
                this.$cache.groups[cacheName][permission] = value;
            }.bind(this));
        },

        /**
         * Set a permission for an user
         *
         * @param {Object} User
         * @param {String} permission
         * @param {String|Number|*} value
         */
        setUserPermission : function(User, permission, value)
        {
            var cacheName = User.getId();

            if (cacheName in this.$cache.users) {
                this.$cache.users[cacheName][permission] = value;
                return;
            }

            this.getUserPermissionList(User).then(function() {
                this.$cache.users[cacheName][permission] = value;
            }.bind(this));
        }
    });
});
