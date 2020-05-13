/**
 * Permission for an user
 *
 * @module controls/permissions/User
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/permissions/User', [

    'controls/permissions/Permission',
    'qui/controls/buttons/Button',
    'Locale'

], function (Permission, QUIButton, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';


    return new Class({

        Extends: Permission,
        Type   : 'controls/permissions/User',

        Binds: [
            '$onOpen'
        ],

        initialize: function (User, options) {
            this.parent(User, options);

            this.$Select = null;

            if (typeOf(User) === 'classes/users/User') {
                this.$Bind = User;
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
         * User select
         *
         * @returns {Promise}
         */
        $openBindSelect: function () {
            var self = this;

            return new Promise(function (resolve) {
                var Container = new Element('div', {
                    'class': 'controls-permissions-select',
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
                        require(['controls/users/Select'], function (Select) {
                            Container.set(
                                'html',
                                '<span class="controls-permissions-panel-headerIcon fa fa-user"></span>' +
                                '<h2>' + QUILocale.get(lg, 'permissions.panel.select.user.title') + '</h2>' +
                                QUILocale.get(lg, 'permissions.panel.select.user.description')
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
                                    onChange: function (Instance) {
                                        require(['Users'], function (Users) {
                                            self.$Bind = Users.get(Instance.getValue());
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
                            }).inject(Container);
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
                text     : QUILocale.get('quiqqer/quiqqer', 'permission.control.btn.user.save'),
                title    : QUILocale.get('quiqqer/quiqqer', 'permission.control.btn.user.save'),
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
        }
    });
});
