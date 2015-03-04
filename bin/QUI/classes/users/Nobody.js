
/**
 * A QUIQQER Nobody User
 *
 * @module classes/users/Nobody
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require classes/users/User
 *
 * @event onRefresh [ {classes/users/Nobody} ]
 */

define('classes/users/Nobody', [

    'qui/QUI',
    'qui/classes/DOM',
    'classes/users/User'

], function(QUI, DOM, User)
{
    "use strict";

    /**
     * A QUIQQER User
     *
     * @class classes/users/Nobody
     * @param {Number} uid - the user id
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : User,
        Type    : 'classes/users/Nobody',

        attributes : {}, // user attributes

        initialize : function()
        {
            this.$uid    = 0;
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
            return 0;
        },

        /**
         * Return the user name
         *
         * @method classes/users/Nobody#getName
         * @return {String} Username
         */
        getName : function()
        {
            return 'nobody';
        },

        /**
         * Nobody is always loaded
         *
         * @method classes/users/Nobody#load
         * @param {Function} [onfinish] - (optional), callback
         */
        load: function(onfinish)
        {
            var self = this;

            if ( typeof onfinish !== 'undefined' ) {
                onfinish( this );
            }

            this.fireEvent( 'refresh', [ this ] );

            require(['Users'], function(Users) {
                Users.onRefreshUser( self );
            });
        },

        /**
         * Nobody is always loaded
         * @return {Boolean}
         */
        isLoaded : function()
        {
            return true;
        },

        /**
         * Do nothing, method overwrite
         *
         * @method classes/users/Nobody#save
         */
        save : function()
        {

        },

        /**
         * Do nothing, method overwrite
         *
         * @method classes/users/Nobody#activate
         * @param {Function} [onfinish] - (optional), callback function, calls if activation is finish
         */
        activate : function(onfinish)
        {
            if ( typeof onfinish !== 'undefined' ) {
                onfinish();
            }
        },

        /**
         * Do nothing, method overwrite
         * @method classes/users/Nobody#deactivate
         * @param {Function} [onfinish] - (optional), callback function, calls if deactivation is finish
         */
        deactivate : function(onfinish)
        {
            if ( typeof onfinish !== 'undefined' ) {
                onfinish();
            }
        },

        /**
         * Do nothing, method overwrite
         * @method classes/users/Nobody#deactivate
         */
        savePassword : function()
        {

        },

        /**
         * Is the user activated?
         *
         * @return {Number} 0, 1, -1
         */
        isActive : function()
        {
            return 1;
        }
    });
});
