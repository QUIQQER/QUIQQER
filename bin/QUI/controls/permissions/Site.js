/**
 * Permissions Panel -> Site
 *
 * @module controls/permissions/Site
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 * @require qui/controls/buttons/Button
 * @require Locale
 */
define('controls/permissions/Site', [

    'controls/permissions/Permission',
    'qui/controls/buttons/Button',
    'Locale'

], function (Permission, QUIButton, QUILocale) {
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
                text     : QUILocale.get('quiqqer/system', 'permission.control.btn.site.save'),
                title    : QUILocale.get('quiqqer/system', 'permission.control.btn.site.save'),
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
        }
    });
});