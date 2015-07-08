
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

], function(QUIDOM, QUIButton, QUIPrompt, Permission, QUILocale)
{
    "use strict";

    return new Class({

        Extends: Permission,
        Type: 'controls/permissions/Edit',

        Binds : [
            '$onOpen',
            '$addPermission'
        ],

        initialize : function(Object, options)
        {
            this.parent(null, options);

            this.addEvents({
                onOpen : this.$onOpen
            });
        },

        /**
         * User select
         *
         * @returns {Promise}
         */
        $openBindSelect : function()
        {
            return new Promise(function(resolve) {

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
        $onOpen : function()
        {
            new QUIButton({
                text      : QUILocale.get('quiqqer/system', 'permission.control.btn.add.permission'),
                title     : QUILocale.get('quiqqer/system', 'permission.control.btn.add.permission'),
                textimage : 'icon-plus',
                styles    : {
                    'float' : 'right'
                },
                events : {
                    click : this.$addPermission
                }
            }).inject(this.$Buttons);
        },


        $addPermission : function()
        {
            var self = this;

            new QUIPrompt({
                title       : QUILocale.get('quiqqer/system', 'permissions.panel.window.add.title'),
                icon        : 'icon-add',
                text        : QUILocale.get('quiqqer/system', 'permissions.panel.window.add.text'),
                information : QUILocale.get('quiqqer/system', 'permissions.panel.window.add.information'),
                autoclose   : false,
                maxWidth    : 600,
                maxHeight   : 400,
                events :
                {
                    onOpen : function(Win)
                    {
                        var Body  = Win.getContent(),
                            Input = Body.getElement('input');

                        Body.getElement('.qui-windows-prompt').setStyle('height', null);
                        Body.getElement('.qui-windows-prompt-input').setStyle('marginTop', 100);

                        Input.setStyles({
                            width   : 300,
                            'float' : 'none'
                        });

                        var Area = new Element('select', {
                            name : 'area',
                            html : '<option value="">'+
                                       QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.user') +
                                   '</option>'+
                                   '<option value="site">'+
                                       QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.site') +
                                   '</option>' +
                                   '<option value="project">'+
                                       QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.project') +
                                   '</option>',
//                                   '<option value="media">'+
//                                       QUILocale.get('quiqqer/system', 'permissions.panel.window.add.select.media') +
//                                   '</option>',
                            styles : {
                                width   : 190,
                                margin  : '10px 10px 10px 0'
                            }
                        }).inject(Input, 'after');

                        new Element('select', {
                            name : 'type',
                            html : '<option value="bool" selected="selected">bool</option>' +
                                   '<option value="string">string</option>' +
                                   '<option value="int">int</option>' +
                                   '<option value="group">group</option>' +
                                   '<option value="groups">groups</option>' +
                                   '<option value="user">user</option>' +
                                   '<option value="users">users</option>' +
                                   '<option value="array">array</option>',
                            styles : {
                                width   : 100,
                                margin  : '10px 0 0 0'
                            }
                        }).inject(Area, 'after');

                        Body.getElement('.qui-windows-prompt-information').setStyle('clear', 'both');

                        if ( !self.$Map ) {
                            return;
                        }

                        var sels = self.$Map.getSelectedChildren();

                        if (sels[ 0 ])
                        {
                            Win.getInput().focus();
                            Win.setValue(sels[ 0 ].getAttribute( 'value' ) +'.');
                        }
                    },

                    onSubmit : function(value, Win)
                    {
//                        Win.Loader.show();
//
//                        Ajax.post('ajax_permissions_add', function(result)
//                        {
//                            if ( result )
//                            {
//                                Win.close();
//                                self.$createSitemap();
//                            }
//                        }, {
//                            permission     : value,
//                            area           : Win.getContent().getElement( '[name="area"]' ).value,
//                            permissiontype : Win.getContent().getElement( '[name="type"]' ).value,
//                            onError : function(Exception)
//                            {
//                                QUI.getMessageHandler(function(MessageHandler) {
//                                    MessageHandler.addException( Exception );
//                                });
//
//                                Win.Loader.hide();
//                            }
//                        });
                    }
                }

            }).open();
        }
    });
});