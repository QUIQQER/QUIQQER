/**
 * An QUIQQER JavaScript Exception
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module classes/exceptions/Exception
 * @package com.pcsg.qui.js.classes.exceptions
 * @namespace QUI.classes.exceptions
 */

define('classes/exceptions/Exception', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace('classes.exceptions');

    /**
     * @class QUI.classes.exceptions.Exception
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.classes.exceptions.Exception = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.exceptions.Exception',

        $Request : null,

        options : {
            message  : '',
            code     : 0,
            type     : 'error'
        },

        initialize : function(options)
        {
            this.init( options );
        },

        /**
         * Return the Exception as a String
         *
         * @method QUI.classes.exceptions.Exception#toString
         * @return {String}
         */
        toString : function()
        {
            return this.getAttribute('type') +' '+
                   this.getCode() +' : '+
                   this.getMessage();
        },

        /**
         * Returns the Exception Message
         *
         * @method QUI.classes.exceptions.Exception#getMessage
         * @return {String}
         */
        getMessage : function()
        {
            return this.getAttribute('message');
        },

        /**
         * Returns the Exception Message Typ
         *
         * @method QUI.classes.exceptions.Exception#getMessageType
         * @return {String}
         */
        getMessageType : function()
        {
            return this.getAttribute('type');
        },

        /**
         * Returns the Exception Code
         *
         * @method QUI.classes.exceptions.Exception#getCode
         * @return {Integer}
         */
        getCode : function()
        {
            return parseInt( this.getAttribute('code'), 10 );
        }
    });

    return QUI.classes.exceptions.Exception;
});
