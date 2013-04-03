/**
 * QUI Ajax Class
 *
 * Communication between server and client
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires classes/exceptions/Exception
 *
 * @module classes/request/Ajax
 * @package com.pcsg.qui.js.classes.request
 * @namespace QUI.classes
 */

define('classes/request/Ajax', [

    'classes/DOM',
    'classes/exceptions/Exception'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.request' );
    QUI.$storage = {};

    /**
     * QUIQQER Ajax
     *
     * @class QUI.classes.Ajax
     *
     * @fires onComplete [this]
     * @fires onSuccess [result, this]
     * @fires onProgress [this]
     * @fires onCancel [this]
     * @fires onDestroy [this]
     * @fires onError [QUI.classes.exceptions.Exception, this]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.classes.Ajax = new Class({

        Extends : DOM,
        Type    : 'QUI.classes.request.Ajax',

        Binds : [
            '$parseResult'
        ],

        $Request : null,
        $result  : null,

        options : {
            method : 'post',
            url    : '',
            async  : true
        },

        initialize : function(options)
        {
            this.init( options );
        },

        /**
         * Send the Request
         *
         * @method QUI.classes.Ajax#send
         *
         * @param {Object} params - Parameters which to be sent
         * @return {Request} Request Object
         */
        send : function(params)
        {
            params = this.parseParams( params || {} );

            this.setAttribute( 'params', params );

            this.$Request = new Request({
                url    : this.getAttribute('url'),
                method : this.getAttribute('method'),
                async  : this.getAttribute('async'),

                onProgress : function(event, xhr)
                {
                    this.fireEvent( 'progress', [ this ] );
                }.bind( this ),

                onComplete : function()
                {
                    this.fireEvent( 'complete', [ this ] );
                }.bind( this ),

                onSuccess : this.$parseResult,

                onCancel : function()
                {
                    this.fireEvent( 'cancel', [ this ] );
                }.bind( this )
            });

            this.$Request.send( Object.toQueryString( params ) );

            return this.$Request;
        },

        /**
         * Cancel the Request
         *
         * @method QUI.classes.Ajax#cancel
         */
        cancel : function()
        {
            this.$Request.cancel();
        },

        /**
         * Fires the onDestroy Event
         *
         * @method QUI.classes.Ajax#destroy
         * @fires onDestroy
         */
        destroy : function()
        {
            this.fireEvent( 'destroy', [ this ] );
        },

        /**
         * If the Request is synchron, with getResult you can get the result from the request
         *
         * @method QUI.classes.Ajax#getResult
         *
         * @return {unknown_type} result
         *
         * @example
         * Ajax.send( myparams );
         * var result = Ajax.getResult();
         */
        getResult : function()
        {
            return this.$result;
        },

        /**
         * Parse Params for the request
         * It filters undefined, objects and so on
         *
         * @method QUI.classes.Ajax#parseParams
         *
         * @param {Object} params - params that will be send
         * @return {Object} Param list
         */
        parseParams : function(params)
        {
            var k, type_of;

            var result = {},
                value  = '';

            if ( typeof params.lang === 'undefined' &&
                 typeof QUI.Locale !== 'undefined' )
            {
                params.lang = QUI.Locale.getCurrent();
            }

            for ( k in params )
            {
                if ( typeof params[ k ] === 'undefined' ) {
                    continue;
                }

                type_of = typeOf( params[ k ] );

                if ( type_of != 'string' &&
                     type_of != 'number' &&
                     type_of != 'array' )
                {
                    continue;
                }

                if ( k != '_rf' && type_of == 'array' ) {
                    continue;
                }

                // if _rf is no array, make an array to it
                if ( k == '_rf' )
                {
                    if ( typeOf( params[ k ] ) != 'array' ) {
                        params[ k ] = [ params[ k ] ];
                    }

                    params[ k ] = JSON.encode( params[ k ] );
                }

                value = params[ k ].toString();
                value = value.replace(/\+/g, '%2B');
                value = value.replace(/\&/g, '%26');
                value = value.replace(/\'/g, '%27');

                result[ k ] = value;
            }

            return result;
        },

        /**
         * Parse the result and fire the Events
         *
         * @method QUI.classes.Ajax#$parseResult
         * @param {String} responseText - request result
         * @param {String} responseXML
         *
         * if changes exists, please update the controls/upload/File.js
         *
         * @ignore
         */
        $parseResult : function(responseText, responseXML)
        {
            var i;

            var str   = responseText || '',
                len   = str.length,
                start = 9,
                end   = len-10;

            if ( !str.match('<quiqqer>') || !str.match('</quiqqer>') )
            {
                return this.fireEvent('error', [
                    new QUI.classes.exceptions.Exception({
                        message : 'No QUIQQER XML',
                        code    : 500
                    }),
                    this
                ]);
            }

            if ( str.substring(0, start) != '<quiqqer>' ||
                 str.substring(end, len) != '</quiqqer>' )
            {
                return this.fireEvent('error', [
                    new QUI.classes.exceptions.Exception({
                        message : 'No QUIQQER XML',
                        code    :  500
                    }),
                    this
                ]);
            }

            // callback
            var res, func;

            var result = eval( '('+ str.substring( start, end ) +')' ),
                params = this.getAttribute( 'params' ),
                rfs    = JSON.decode( params._rf || [] ),

                event_params = [];

            // exist messages?
            if ( result.message_handler &&
                 result.message_handler.length &&
                 typeof QUI.MH !== 'undefined' )
            {
                var messages = result.message_handler;

                for ( i = 0, len = messages.length; i < len; i++ )
                {
                    QUI.MH.add(
                        QUI.MH.parse( messages[ i ] )
                    );
                }
            }

            // exist a main exception?
            if ( result.Exception )
            {
                return this.fireEvent('error', [
                    new QUI.classes.exceptions.Exception({
                        message : result.Exception.message || '',
                        code    : result.Exception.code || 0,
                        type    : result.Exception.type || 'Exception'
                    }),
                    this
                ]);
            }

            // check the single function
            for ( i = 0, len = rfs.length; i < len; i++ )
            {
                func = rfs[ i ];
                res  = result[ func ];

                if ( !res )
                {
                    event_params.push( null );
                    continue;
                }

                if ( res.Exception )
                {
                    this.fireEvent('error', [
                        new QUI.classes.exceptions.Exception({
                            message : res.Exception.message || '',
                            code    : res.Exception.code || 0,
                            type    : res.Exception.type || 'Exception'
                        }),
                        this
                    ]);

                    event_params.push( null );
                    continue;
                }

                if ( res.result )
                {
                    event_params.push( res.result );
                    continue;
                }

                event_params.push( null );
            }

            event_params.push( this );

            this.fireEvent( 'success', event_params );
        }
    });

    return QUI.classes.Ajax;
});