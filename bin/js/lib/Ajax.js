/**
 * Ajax request for QUIQQER
 * Ajax Manager, collect, exec multible requests
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module lib/Ajax
 * @package com.pcsg.qui.js
 * @namespace QUI.lib
 *
 * @example

QUI.Ajax.post('ajax_project_getlist', function(result, Request)
{
    console.info(result);
});

 */

define('lib/Ajax', [

    'classes/request/Ajax'

], function()
{
    "use strict";

    QUI.namespace('lib');

    QUI.Ajax = QUI.lib.Ajax =
    {
        $onprogress : {},
        $url        : typeof URL_DIR === 'undefined' ? '' : URL_DIR +'admin/ajax.php',

        /**
         * Send a Request
         *
         * @method QUI.lib.Ajax#request
         *
         * @param {String} call            - PHP function
         * @param {String} method         - Send Method -> post or get
         * @param {Function} callback     - Callback function if the request is finish
         * @param {Object} params         - PHP parameter (optional)
         *
         * @return {QUI.classes.Ajax}
         */
        request : function(call, method, callback, params)
        {
         // if sync, the browser freeze
            var async = false,
                id    = String.uniqueID();

            method   = method || 'post'; // is post, put, get or delete
            callback = callback || function() {};

            params = QUI.Utils.combine(params, {
                _rf : call
            });

            this.$onprogress[ id ] = new QUI.classes.Ajax(
                // combine all params, so, they are available in the Request Object
                QUI.Utils.combine(params, {
                    callback : callback,
                    method   : method,
                    url      : QUI.Ajax.$url,
                    events   :
                    {
                        onSuccess : callback,

                        onCancel : function(Request)
                        {
                            if ( Request.getAttribute( 'onCancel' ) ) {
                                return Request.getAttribute( 'onCancel' )( Request );
                            }
                        },

                        onError : function(Exception, Request)
                        {
                            if ( Request.getAttribute( 'onError' ) ) {
                                return Request.getAttribute( 'onError' )( Exception, Request );
                            }

                            QUI.triggerError( Exception, Request );
                        }
                    }
                })
            );

            this.$onprogress[ id ].send( params );

            if ( async ) {
                return this.$onprogress[ id ].getResult();
            }

            return this.$onprogress[ id ];
        },

        /**
         * Send a POST Request
         *
         * @method QUI.lib.Ajax#post
         *
         * @param {String|Array} call - PHP function
         * @param {Function} callback - Callback function if the Request is finish
         * @param {Object} params     - PHP parameter (optional)
         *
         * @return {QUI.classes.Ajax}
         */
        post : function(call, callback, params)
        {
            return this.request( call, 'post', callback, params );
        },

        /**
         * Send a GET Request
         *
         * @method QUI.lib.Ajax#get
         *
         * @param {String|Array} call - PHP function
         * @param {Function} callback - Callback function if the Request is finish
         * @param {Object} params     - PHP parameter (optional)
         *
         * @return {QUI.classes.Ajax]
         */
        get : function(call, callback, params)
        {
            return this.request( call, 'get', callback, params );
        },

        /**
         * Parse params to a ajax request string
         *
         * @method QUI.lib.Ajax#parseParams
         *
         * @param {String|Array} call - PHP function
         * @param {Object} params     - PHP parameter (optional)
         */
        parseParams : function(call, params)
        {
            params = QUI.Utils.combine(params, {
                _rf : call
            });

            if ( typeof this.$AjaxHelper === 'undefined' ) {
                this.$AjaxHelper = new QUI.classes.Ajax();
            }

            return Object.toQueryString(
                this.$AjaxHelper.parseParams( params )
            );
        },


        put : function(call, callback, params)
        {
            return this.request( call, 'put', callback, params );
        },

        del : function()
        {
            return this.request( call, 'delete', callback, params );
        }
    };

    return QUI.Ajax;
});
