
/**
 * Template Manager
 * Use the Template Manager for getting HTML Templates
 *
 * @module utils/Template
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require qui/utils/Object
 * @require Ajax
 */

define('utils/Template', [

    'qui/classes/DOM',
    'qui/utils/Object',
    'Ajax'

], function(DOM, ObjectUtils, Ajax)
{
    "use strict";

    /**
     * Template Manager - Use the Template Manager for getting HTML Templates
     * @namespace
     * @memberof! <global>
     */
    return {

        $hashes : {},

        /**
         * Get the template
         *
         * @param {String} template
         * @param {Function} oncomplete - callback function
         * @param {Object} [params]     - optional
         *
         * @return Promise
         */
        get : function(template, oncomplete, params)
        {
            return new Promise(function(resolve, reject)
            {
                params = ObjectUtils.combine(params, {
                    template : template
                });

                var hash = this.$hash(template, params);

                if (document.id(hash))
                {
                    var result = this.$getCache(hash);

                    if (result && result !== '')
                    {
                        if (typeof oncomplete === 'function') {
                            oncomplete(result);
                        }

                        resolve(result);
                        return;
                    }

                    reject();
                    return;
                }


                params = ObjectUtils.combine(params, {
                    onError : reject
                });

                Ajax.get('ajax_template_get', function(result)
                {
                    this.$setCache(hash, result);

                    if (typeof oncomplete === 'function') {
                        oncomplete(result);
                    }

                    resolve(result);

                }.bind(this), params);

            }.bind(this));
        },

        /**
         * Get the hash of the params
         *
         * @param {String} f - template name
         * @param {Object} params
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

            var Parent = document.id('pcsg-ajax-html-cache');

            if ( !Parent )
            {
                Parent    = document.createElement('div');
                Parent.id = 'pcsg-ajax-html-cache';
                Parent.style.display = 'none';

                document.body.appendChild( Parent );
            }

            document.id('pcsg-ajax-html-cache').appendChild( o );
        }
    };
});