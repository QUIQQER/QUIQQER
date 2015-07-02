
/**
 * Permissions Panel
 *
 * @module controls/permissions/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/permissions/Permission
 * @require Locale
 */
define('controls/permissions/Group', [

    'controls/permissions/Permission',
    'Locale'

], function(Permission, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: Permission,
        Types: 'controls/permissions/Group',

        initialize : function(Group, options)
        {
            this.parent(Group, options);

            if (typeOf(Group) === 'classes/users/Group') {
                this.$Bind = Group;
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

                        require(['controls/groups/Input'], function(Input)
                        {
                            Container.set(
                                'html',
                                '<h2>'+ QUILocale.get(lg, 'permissions.panel.select.group.title') +'</h2>'
                            );

                            new Input({
                                max      : 1,
                                multible : false,
                                styles   : {
                                    margin : '0 auto',
                                    width  : 200
                                },
                                events :
                                {
                                    onAdd : function(GroupSearch, groupid)
                                    {
                                        require(['Groups'], function(Groups)
                                        {
                                            self.$Bind = Groups.get(groupid);

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