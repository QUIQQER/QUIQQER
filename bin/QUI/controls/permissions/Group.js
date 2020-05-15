/**
 * Permissions for a Group
 *
 * @module controls/permissions/Group
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 * @require Locale
 */
define('controls/permissions/Group', [

    'controls/permissions/Permission',
    'qui/controls/buttons/Button',
    'Locale'

], function (Permission, QUIButton, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: Permission,
        Type   : 'controls/permissions/Group',

        Binds: [
            '$onOpen'
        ],

        initialize: function (Group, options) {
            this.parent(Group, options);

            if (typeOf(Group) === 'classes/groups/Group') {
                this.$Bind = Group;
                this.refresh();
            }

            this.addEvents({
                onOpen   : this.$onOpen,
                onDestroy: function () {
                    if (this.$Input) {
                        this.$Input.destroy();
                    }
                }.bind(this)
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
                this.$Bind.load().then(function () {
                    this.refresh();
                }.bind(this));

                return;
            }

            var Panel = this.getAttribute('Panel'),
                name  = this.$Bind.getName(),
                id    = this.$Bind.getId();

            Panel.setAttribute(
                'title',
                QUILocale.get(lg, 'permissions.panel.title') + ' - ' + name + ' (' + id + ')'
            );

            Panel.refresh();
        },

        /**
         * Group select
         *
         * @returns {Promise}
         */
        $openBindSelect: function () {
            var self = this;

            return new Promise(function (resolve) {

                var Container = new Element('div', {
                    'class': 'controls-permissions-select shadow',
                    styles : {
                        left   : '-100%',
                        opacity: 0
                    }
                }).inject(self.getElm());

                moofx(Container).animate({
                    left   : 0,
                    opacity: 1
                }, {
                    duration: 250,
                    equation: 'ease-in-out',
                    callback: function () {
                        require(['controls/groups/Select'], function (Select) {
                            Container.set(
                                'html',
                                '<span class="controls-permissions-panel-headerIcon fa fa-users"></span>' +
                                '<h2>' + QUILocale.get(lg, 'permissions.panel.select.group.title') + '</h2>' +
                                QUILocale.get(lg, 'permissions.panel.select.group.description')
                            );

                            var size  = Container.getSize(),
                                width = Math.round(size.x / 3);

                            if (width < 500) {
                                width = 500;
                            }

                            self.$Input = new Select({
                                max     : 1,
                                multiple: false,
                                styles  : {
                                    marginTop: 40,
                                    width    : width
                                },
                                events  : {
                                    onAddItem: function (GroupSearch, groupid) {
                                        require(['Groups'], function (Groups) {
                                            self.$Bind = Groups.get(groupid);
                                            self.refresh();

                                            moofx(Container).animate({
                                                left   : '-100%',
                                                opacity: 0
                                            }, {
                                                duration: 250,
                                                equation: 'cubic-bezier(.42,.4,.46,1.29)',
                                                callback: function () {
                                                    Container.destroy();
                                                    resolve();
                                                }
                                            });
                                        });
                                    }
                                }
                            }).inject(Container).focus();
                        });
                    }
                });
            });
        },

        /**
         * event on open
         */
        $onOpen: function () {
            new QUIButton({
                text     : QUILocale.get('quiqqer/quiqqer', 'permission.control.btn.group.save'),
                title    : QUILocale.get('quiqqer/quiqqer', 'permission.control.btn.group.save'),
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
