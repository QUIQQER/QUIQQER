
/**
 * Edit the permission
 * delete and create new permissions
 *
 * @module controls/permissions/Edit
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require qui/controls/buttons/Button
 * @require controls/permissions/Permission
 * @require Locale
 */
define('controls/permissions/Edit', [

    'qui/classes/DOM',
    'qui/controls/buttons/Button',
    'controls/permissions/Permission',
    'Locale'

], function(QUIDOM, QUIButton, Permission, QUILocale)
{
    "use strict";

    return new Class({

        Extends: Permission,
        Type: 'controls/permissions/Edit',

        Binds : [
            '$onOpen'
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
                }
            }).inject(this.$Buttons);
        }
    });
});