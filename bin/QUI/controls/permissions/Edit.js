/**
 * Edit the permission
 * delete and create new permissions
 *
 * @module controls/permissions/Edit
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Prompt
 * @require controls/permissions/Permission
 * @require Locale
 */
define('controls/permissions/Edit', [

    'qui/classes/DOM',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Prompt',
    'controls/permissions/Permission',
    'Locale'

], function (QUIDOM, QUIButton, QUIPrompt, Permission, QUILocale) {
    "use strict";

    return new Class({

        Extends: Permission,
        Type   : 'controls/permissions/Edit',

        Binds: [
            '$onOpen',
            '$addPermission'
        ],

        initialize: function (Object, options) {
            this.parent(null, options);

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
            return new Promise(function (resolve) {

                this.$Bind = new QUIDOM();

                this.$Status.set(
                    'html',
                    QUILocale.get('quiqqer/system', 'permission.control.editcreate.title')
                );

                resolve();

            }.bind(this));
        },

        /**
         * event on open
         */
        $onOpen: function () {
            new QUIButton({
                text     : QUILocale.get('quiqqer/system', 'permission.control.btn.add.permission'),
                title    : QUILocale.get('quiqqer/system', 'permission.control.btn.add.permission'),
                textimage: 'fa fa-plus',
                styles   : {
                    'float': 'right'
                },
                events   : {
                    click: this.$addPermission
                }
            }).inject(this.$Buttons);
        },

        /**
         * opens the add permission dialog
         */
        $addPermission: function () {
            var self = this;

            new QUIPrompt({
                title      : QUILocale.get('quiqqer/system', 'permissions.panel.window.add.title'),
                icon       : 'fa fa-add',
                text       : QUILocale.get('quiqqer/system', 'permissions.panel.window.add.text'),
                information: QUILocale.get('quiqqer/system', 'permissions.panel.window.add.information'),
                autoclose  : false,
                maxWidth   : 600,
                maxHeight  : 400,
                events     : {
                    onOpen: function (Win) {
                        var Body  = Win.getContent(),
                            Input = Body.getElement('input');

                        Body.getElement('.qui-windows-prompt').setStyle('height', null);
                        Body.getElement('.qui-windows-prompt-input').setStyle('marginTop', 100);

                        Input.setStyles({
                            width  : 300,
                            'float': 'none'
                        });

                        var Area = new Element('select', {
                            name  : 'area',
                            html  : '<option value="">' +
                                    QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.user') +
                                    '</option>' +
                                    '<option value="site">' +
                                    QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.site') +
                                    '</option>' +
                                    '<option value="project">' +
                                    QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.project') +
                                    '</option>',
                            //                                   '<option value="media">'+
                            //                                       QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.media') +
                            //                                   '</option>',
                            styles: {
                                width : 190,
                                margin: '10px 10px 10px 0'
                            }
                        }).inject(Input, 'after');

                        new Element('select', {
                            name  : 'type',
                            html  : '<option value="bool" selected="selected">bool</option>' +
                                    '<option value="string">string</option>' +
                                    '<option value="int">int</option>' +
                                    '<option value="group">group</option>' +
                                    '<option value="groups">groups</option>' +
                                    '<option value="user">user</option>' +
                                    '<option value="users">users</option>' +
                                    '<option value="array">array</option>',
                            styles: {
                                width : 100,
                                margin: '10px 0 0 0'
                            }
                        }).inject(Area, 'after');

                        Body.getElement('.qui-windows-prompt-information').setStyle('clear', 'both');
                    },

                    onSubmit: function (value, Win) {
                        Win.Loader.show();

                        var Content = Win.getContent();

                        require([
                            'utils/permissions/Utils'
                        ], function (PermissionUtils) {

                            PermissionUtils.Permissions.addPermission(
                                value,
                                Content.getElement('[name="area"]').value,
                                Content.getElement('[name="type"]').value
                            ).then(function () {
                                Win.close();

                                self.close().then(function () {
                                    self.open();
                                });
                            });

                        });
                    }
                }

            }).open();
        }
    });
});
