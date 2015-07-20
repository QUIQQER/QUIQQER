
/**
 * Permission for an user
 *
 * @module controls/permissions/User
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 * @require qui/controls/buttons/Button
 * @require Locale
 */
define('controls/permissions/User', [

    'controls/permissions/Permission',
    'qui/controls/buttons/Button',
    'Locale'

], function(Permission, QUIButton, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/system';


    return new Class({

        Extends: Permission,
        Type: 'controls/permissions/User',

        Binds : [
            '$onOpen'
        ],

        initialize : function(User, options)
        {
            this.parent(User, options);

            this.$Select = null;

            if (typeOf(User) === 'classes/users/User') {
                this.$Bind = User;
            }

            this.addEvents({
                onOpen : this.$onOpen,
                onDestroy : function() {
                    if (this.$Input) {
                        this.$Input.destroy();
                    }
                }.bind(this)
            });
        },

        /**
         * User select
         *
         * @returns {Promise}
         */
        $openBindSelect : function()
        {
            var self = this;

            return new Promise(function(resolve) {

                var Container = new Element('div', {
                    'class' : 'controls-permissions-select shadow',
                    styles : {
                        left: '-100%',
                        opacity : 0
                    }
                }).inject(self.getElm());

                moofx(Container).animate({
                    left    : 0,
                    opacity : 1
                }, {
                    duration : 250,
                    equation : 'ease-in-out',
                    callback : function() {

                        require(['controls/users/Input'], function(Input)
                        {
                            Container.set(
                                'html',
                                '<h2>'+ QUILocale.get(lg, 'permissions.panel.select.user.title') +'</h2>'
                            );

                            self.$Input = new Input({
                                max      : 1,
                                multible : false,
                                styles   : {
                                    'float' : 'none',
                                    margin : '0 auto',
                                    width  : 200
                                },
                                events :
                                {
                                    onAdd : function(UserSearch, userid)
                                    {
                                        require(['Users'], function(Users)
                                        {
                                            self.$Bind = Users.get(userid);

                                            // set status title
                                            if (self.$Bind.isLoaded()) {
                                                self.$Status.set(
                                                    'html',
                                                    QUILocale.get('quiqqer/system', 'permission.control.edit.title', {
                                                        name : '<span class="fa icon-user"></span>'+
                                                               self.$Bind.getName()
                                                    })
                                                );
                                            } else {
                                                self.$Bind.load().then(function() {
                                                    self.$Status.set(
                                                        'html',
                                                        QUILocale.get('quiqqer/system', 'permission.control.edit.title', {
                                                            name : '<span class="fa icon-user"></span>'+
                                                                   self.$Bind.getName()
                                                        })
                                                    );
                                                });
                                            }

                                            moofx(Container).animate({
                                                left : '-100%',
                                                opacity : 0
                                            }, {
                                                duration : 250,
                                                equation : 'cubic-bezier(.42,.4,.46,1.29)',
                                                callback : function() {
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
        $onOpen : function()
        {
            new QUIButton({
                text : QUILocale.get('quiqqer/system', 'permission.control.btn.user.save'),
                title : QUILocale.get('quiqqer/system', 'permission.control.btn.user.save'),
                textimage : 'icon-save',
                styles : {
                    'float' : 'right'
                },
                events : {
                    onClick : function(Btn) {

                        Btn.setAttribute(
                            'textimage',
                            'icon-spinner icon-spin fa fa-spinner fa-spin'
                        );

                        this.save().then(function() {
                            Btn.setAttribute('textimage', 'icon-save');
                        });

                    }.bind(this)
                }
            }).inject(this.$Buttons);
        }
    });
});