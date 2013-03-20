/**
 * Template Manager
 * Use the Template Manager for getting HTML Templates
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module lib/Template
 * @package com.pcsg.qui.js
 * @namespace QUI.lib
 */

define('lib/Template', function()
{
    "use strict";

    /**
     * Template Manager - Use the Template Manager for getting HTML Templates
     * @namespace
     * @memberof! <global>
     */
    QUI.Template =
    {
        $hashes : {},

        /**
         * Get the template
         *
         * @param {String} template
         * @param {Function} oncomplete - callback function
         * @param {Object} param        - [optional]
         */
        get : function(template, oncomplete, params)
        {
            params = QUI.Utils.combine(params, {
                template   : template,
                oncomplete : oncomplete
            });

            var hash = this.$hash( template, params );

            if ( $( hash ) )
            {
                var result  = this.$getCache( hash ),
                    Request = new QUI.classes.DOM();

                Request.setAttributes( params );

                if ( result && result !== '' )
                {
                    oncomplete( result, Request );
                    return;
                }
            }

            params.HCF_hash = hash;

            QUI.Ajax.get('ajax_template_get', function(result, Request)
            {
                QUI.Template.$setCache(
                    Request.getAttribute('HCF_hash'),
                    result
                );

                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, params);
        },

        /**
         * Get the hash of the params
         *
         * @param {String} f - template name
         * @param {Function}
         *
         * @return {String}
         */
        $hash : function(f, params)
        {
            var k, hash;
            var ohash = {};

            for ( k in params )
            {
                if ( typeof params[k] === 'object' ) {
                    continue;
                }

                if ( typeof params[k] === 'function' ) {
                    continue;
                }

                ohash[k] = params[k];
            }

            ohash = JSON.encode( ohash )
                        .replace(/"/g, '_')
                        .replace(/[^a-zA-Z_0-9]/g, '')
                        .replace(/[_]{2}/g, '_');

            hash = 'pcsg_ahc_'+ f + ohash;

            if ( typeof this.$hashes[ hash ] === 'undefined' ) {
                this.$hashes[ hash ] = '#HASH'+ (new Date().getTime()).toString();
            }

            return this.$hashes[ hash ];
        },

        /**
         * Return the cache of the hash
         *
         * @return {String}
         */
        $getCache : function(hash)
        {
            var h = $( hash ).get( 'html' );

            // unter IE8 -> html kommentare können nicht per innerHTML gehohlt werden
            h = h.replace(/<\!\-\-/, '');
            h = h.replace(/\-\-\>/, '');

            return h;
        },

        /**
         * Set a new cache for the hash
         */
        $setCache : function(hash, html)
        {
            var o = document.createElement('div');

            if ( typeof html === 'undefined' ) {
                html = '';
            }

            html = html.replace(/<!--[\s\S]*?-->/g, "");

            o.id        = hash;
            o.innerHTML = '<!-- '+ html +' -->';

            var Parent = $('pcsg-ajax-html-cache');

            if ( !Parent )
            {
                Parent    = document.createElement('div');
                Parent.id = 'pcsg-ajax-html-cache';
                Parent.style.display = 'none';

                document.body.appendChild( Parent );
            }

            $('pcsg-ajax-html-cache').appendChild( o );
        }
    };

    return QUI.Template;
});