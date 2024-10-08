/**
 * Permissions Panel -> Media
 *
 * @module controls/permissions/Media
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/permissions/Media', [

    'controls/permissions/Permission',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'Locale'

], function (Permission, QUIButton, QUIConfirm, QUILocale) {
    "use strict";

    var lg = 'quiqqer/core';

    return new Class({

        Extends: Permission,
        Type   : 'controls/permissions/Media',

        Binds: [
            '$onOpen'
        ],

        initialize: function (Item, options) {
            this.parent(Item, options);

            switch (typeOf(Item)) {
                case 'classes/projects/project/media/File':
                case 'classes/projects/project/media/Folder':
                case 'classes/projects/project/media/Image':
                case 'classes/projects/project/media/Item':
                    this.$Bind = Item;
                    this.refresh();
                    break;
            }

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * Refresh the title
         */
        refresh: function () {
            if (!this.$Bind) {
                return;
            }

            if (!this.$Bind.isLoaded()) {
                this.$Bind.refresh(function () {
                    this.refresh();
                }.bind(this));

                return;
            }

            var Panel = this.getAttribute('Panel'),
                name  = this.$Bind.getAttribute('name'),
                id    = this.$Bind.getId();

            Panel.setAttribute(
                'title',
                QUILocale.get(lg, 'permissions.panel.title') + ' - ' + name + ' (' + id + ')'
            );

            Panel.refresh();
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
                    'controls/projects/project/media/Popup',
                    'Projects'
                ], function (Popup, Projects) {
                    new Popup({
                        events: {
                            onSubmit: function (Popup, data) {
                                console.log(data);

                                var Project = Projects.get(data.project);

                                if (!parseInt(data.id)) {
                                    reject();
                                    return;
                                }

                                Project.getMedia().get(data.id).then(function (Item) {
                                    self.$Bind = Item;
                                    self.refresh();

                                    resolve();
                                });
                            },

                            onCancel: reject
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
                title    : QUILocale.get('quiqqer/core', 'permission.control.btn.site.save.recursive'),
                textimage: 'fa fa-reply-all',
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
                text     : QUILocale.get('quiqqer/core', 'permission.control.btn.site.save'),
                title    : QUILocale.get('quiqqer/core', 'permission.control.btn.site.save.text'),
                textimage: 'fa fa-save',
                styles   : {
                    'float': 'right'
                },
                events   : {
                    onClick: function (Btn) {

                        Btn.setAttribute(
                            'textimage',
                            'fa fa-spinner fa-spin'
                        );

                        this.save().then(function () {
                            Btn.setAttribute('textimage', 'fa fa-save');
                        });

                    }.bind(this)
                }
            }).inject(this.$Buttons);
        },

        /**
         * Opens the set recursive dialog
         */
        openSetRecursiveDialog: function () {
            var self = this;

            new QUIConfirm({
                title      : QUILocale.get('quiqqer/core', 'permission.control.site.recursive.win.title'),
                icon       : 'fa fa-reply-all',
                maxHeight  : 300,
                maxWidth   : 450,
                texticon   : false,
                text       : QUILocale.get('quiqqer/core', 'permission.control.site.recursive.win.text'),
                information: QUILocale.get('quiqqer/core', 'permission.control.site.recursive.win.information'),

                cancel_button: {
                    text     : QUILocale.get('quiqqer/core', 'cancel'),
                    textimage: 'fa fa-remove'
                },
                ok_button    : {
                    text     : QUILocale.get('quiqqer/core', 'accept'),
                    textimage: 'fa fa-check'
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
         * Execute recursive permissions
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
                    var MediaItem = self.$Bind,
                        Project   = MediaItem.getProject();

                    self.save().then(function () {
                        PermUtils.Permissions.getMediaPermissionList(MediaItem).then(function (data) {
                            Ajax.post('ajax_permissions_recursive', resolve, {
                                params     : JSON.encode({
                                    project: Project.getName(),
                                    lang   : Project.getLang(),
                                    id     : MediaItem.getId()
                                }),
                                btype      : MediaItem.getType(),
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
