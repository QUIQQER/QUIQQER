/**
 * Permissions Panel -> Project
 *
 * @module controls/permissions/Project
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/permissions/Project', [

    'controls/permissions/Permission',
    'qui/controls/buttons/Button',
    'Locale'

], function (Permission, QUIButton, QUILocale) {
    "use strict";

    var lg = 'quiqqer/core';

    return new Class({

        Extends: Permission,
        Type   : 'controls/permissions/Project',

        Binds: [
            '$onOpen'
        ],

        initialize: function (Project, options) {
            this.parent(Project, options);

            if (typeOf(Project) === 'classes/projects/Project') {
                this.$Bind = Project;
                this.refresh();
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

            var Panel = this.getAttribute('Panel'),
                name  = this.$Bind.getName();

            Panel.setAttribute(
                'title',
                QUILocale.get(lg, 'permissions.panel.title') + ' - ' + name
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
                    'controls/projects/SelectWindow',
                    'Projects'
                ], function (Popup, Projects) {
                    new Popup({
                        langSelect: false,
                        events    : {
                            onSubmit: function (Popup, data) {
                                self.$Bind = Projects.get(data.project, data.lang);
                                self.refresh();

                                resolve();
                            },

                            onCancel: function () {
                                reject();
                            }
                        }
                    }).open();
                });
            });
        },

        /**
         * event on open
         */
        $onOpen: function () {
            new QUIButton({
                text     : QUILocale.get('quiqqer/core', 'permission.control.btn.project.save'),
                title    : QUILocale.get('quiqqer/core', 'permission.control.btn.project.save'),
                textimage: 'fa fa-save',
                styles   : {
                    'float': 'right'
                },
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        this.save().then(function () {
                            Btn.setAttribute('textimage', 'fa fa-save');
                        });
                    }.bind(this)
                }
            }).inject(this.$Buttons);
        }
    });
});
