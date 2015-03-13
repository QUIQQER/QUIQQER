
/**
 * A QUIQQER SystemUser
 *
 * @module classes/users/SystemUser
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require classes/users/Nobody
 *
 * @event onRefresh [ {classes/users/SystemUser} ]
 */

define('classes/users/SystemUser', [

    'qui/QUI',
    'qui/classes/DOM',
    'classes/users/Nobody',
    'classes/users/SystemUser'

], function(QUI, DOM, Nobody)
{
    "use strict";

    /**
     * A QUIQQER User
     *
     * @class classes/users/SystemUser
     * @memberof! <global>
     */
    return new Class({

        Extends : Nobody,
        Type    : 'classes/users/SystemUser',

        attributes : {}, // user attributes

        initialize : function()
        {
            this.$uid    = 5;
            this.$extras = {};
            this.$loaded = true;
        },

        /**
         * Get user id
         *
         * @method classes/users/Nobody#getId
         * @return {Number} User-ID
         */
        getId : function()
        {
            return 5;
        },

        /**
         * Return the user name
         *
         * @method classes/users/Nobody#getName
         * @return {String} Username
         */
        getName : function()
        {
            return 'System-User';
        }
    });
});
