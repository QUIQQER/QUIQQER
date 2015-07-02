
/**
 * Permissions Panel
 *
 * @module controls/permissions/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 * @require Locale
 */
define('controls/permissions/User', [

    'controls/permissions/Permission',
    'Locale'

], function(Permission, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/system';


    return new Class({

        Extends: Permission,
        Types: 'controls/permissions/User',

        initialize : function(User, options)
        {
            this.parent(User, options);

            if (typeOf(User) === 'classes/users/User') {
                this.$Bind = User;
            }
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

                            new Input({
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
        }
    });
});