/**
 * Permissions Panel -> Site
 *
 * @module controls/permissions/Site
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require Locale
 */
define('controls/permissions/Site', [

    'controls/permissions/Permission',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'Locale'

], function (Permission, QUIButton, QUIConfirm, QUILocale) {
    "use strict";

    return new Class({

        Extends: Permission,
        Type   : 'controls/permissions/Site',

        Binds: [
            '$onOpen'
        ],

        initialize: function (Site, options) {
            this.parent(Site, options);

            if (typeOf(Site) === 'classes/projects/project/Site') {
                this.$Bind = Site;
            }

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * User select
         *
         * @returns {Promise}
         */
        $openBindSelect: function () {
            var self = this;

            return new Promise(function (resolve, reject) {

                require([
                    'controls/projects/Popup',
                    'Projects'
                ], function (Popup, Projects) {

                    new Popup({
                        events: {
                            onSubmit: function (Popup, data) {

                                var Project = Projects.get(data.project, data.lang);

                                if (!data.ids.length) {
                                    reject();
                                    return;
                                }

                                self.$Bind = Project.get(data.ids[0]);
                                self.$loadStatus();

                                resolve();
                            },

                            onCancel: function () {
                                reject();
                            }
                        }
                    }).open();

                }, function (err) {
                    console.error(err);
                    reject(err);
                });

            });
        },

        /**
         * event on open
         */
        $onOpen: function () {

            new QUIButton({
                title    : QUILocale.get('quiqqer/system', 'permission.control.btn.site.save.recursive'),
                textimage: 'icon-reply-all fa fa-reply-all',
                styles   : {
                    'float': 'right'
                },
                events   : {
                    onClick: function () {
                        this.openSetRecursiveDialog();
                    }.bind(this)
                }
            }).inject(this.$Buttons);

            new QUIButton({
                text     : QUILocale.get('quiqqer/system', 'permission.control.btn.site.save'),
                title    : QUILocale.get('quiqqer/system', 'permission.control.btn.site.save.text'),
                textimage: 'icon-save',
                styles   : {
                    'float': 'right'
                },
                events   : {
                    onClick: function (Btn) {

                        Btn.setAttribute(
                            'textimage',
                            'icon-spinner icon-spin fa fa-spinner fa-spin'
                        );

                        this.save().then(function () {
                            Btn.setAttribute('textimage', 'icon-save');
                        });

                    }.bind(this)
                }
            }).inject(this.$Buttons);

            this.$loadStatus();
        },

        /**
         * Load the title status
         */
        $loadStatus: function () {
            if (!this.$Bind) {
                return;
            }

            var self = this;

            // set status title
            if (self.$Bind.isLoaded()) {
                self.$Status.set(
                    'html',
                    QUILocale.get('quiqqer/system', 'permission.control.edit.title', {
                        name: '<span class="fa fa-file-o icon-file-alt"></span>' +
                              self.$Bind.getAttribute('name') + '.html'
                    })
                );

            } else {

                self.$Bind.load(function () {
                    self.$Status.set(
                        'html',
                        QUILocale.get('quiqqer/system', 'permission.control.edit.title', {
                            name: '<span class="fa fa-file-o icon-file-alt"></span>' +
                                  self.$Bind.getAttribute('name') + '.html'
                        })
                    );
                });
            }
        },

        /**
         * Opens the set recursive dialog
         */
        openSetRecursiveDialog: function () {
            var self = this;

            new QUIConfirm({
                title      : QUILocale.get('quiqqer/system', 'permission.control.site.recursive.win.title'),
                icon       : 'icon-reply-all fa fa-reply-all',
                maxHeight  : 300,
                maxWidth   : 450,
                texticon   : false,
                text       : QUILocale.get('quiqqer/system', 'permission.control.site.recursive.win.text'),
                information: QUILocale.get('quiqqer/system', 'permission.control.site.recursive.win.information'),

                cancel_button: {
                    text     : QUILocale.get('quiqqer/system', 'cancel'),
                    textimage: 'icon-remove fa fa-remove'
                },
                ok_button    : {
                    text     : QUILocale.get('quiqqer/system', 'accept'),
                    textimage: 'icon-ok fa fa-check'
                },

                events: {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        self.setRecursive().then(function () {
                            Win.close();
                        });
                    }
                }
            }).open();
        },

        /**
         *
         * @returns {Promise}
         */
        setRecursive: function () {
            var self = this;

            return new Promise(function (resolve, reject) {

                require([
                    'Ajax',
                    'utils/permissions/Utils'
                ], function (Ajax, PermUtils) {

                    var Site    = self.$Bind,
                        Project = Site.getProject();

                    self.save().then(function () {

                        PermUtils.Permissions.getSitePermissionList(Site).then(function (data) {

                            Ajax.post('ajax_permissions_recursive', function () {
                                resolve();
                            }, {
                                params     : JSON.encode({
                                    project: Project.getName(),
                                    lang   : Project.getLang(),
                                    id     : Site.getId()
                                }),
                                btype      : Site.getType(),
                                permissions: JSON.encode(data),
                                onError    : reject
                            });
                        });

                    }).catch(reject);

                }, reject);
            });
        }
    });
});
