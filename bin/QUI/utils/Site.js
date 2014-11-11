
/**
 * Helper for site operations
 *
 * @module utils/Site
 * @author www.pcsg.de (Henning Leutz)
 */

define(function()
{
    "use strict";

    return {

        /**
         * Return the not allowed signs list for an url
         *
         * @return Object
         */
        notAllowedUrlSigns : function()
        {
            return {
                '.' : true,
                ',' : true,
                ':' : true,
                ';' : true,
                '#' : true,
                '`' : true,
                '!' : true,
                '§' : true,
                '$' : true,
                '%' : true,
                '&' : true,
                '?' : true,
                '<' : true,
                '>' : true,
                '=' : true,
                '\'' : true,
                '"' : true,
                '@' : true,
                '_' : true,
                ']' : true,
                '[' : true,
                '+' : true,
                '/' : true
            };
        },

        /**
         * similar function as \QUI\Projects\Site\Utils::clearUrl
         *
         * @param {String} url
         * @return {String}
         */
        clearUrl : function(url)
        {
            var signs = Object.keys( this.notAllowedUrlSigns() ).join("");

            url = url.replace( new RegExp( signs, 'g' ), '' );


            // doppelte leerzeichen löschen
            // $url = preg_replace('/([ ]){2,}/', "$1", $url);


            return url;
        }
    };
});
