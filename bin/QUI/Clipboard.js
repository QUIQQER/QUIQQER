
/**
 * A mini Clipboard for QUI
 * You can save text data eq for copy and paste a sitemap item
 *
 * its not a real clipboard
 *
 * @module Clipboard
 * @author www.pcsg.de (Henning Leutz)
 */

define(function()
{
    "use strict";

    return {

        $data : null,

        /**
         * set data to the clipboard
         *
         * @param {String|Object|Array} data
         */
        set : function(data)
        {
            this.$data = data;
        },

        /**
         * Return the data
         *
         * @return {String|Object|Array}
         */
        get : function()
        {
            return this.$data;
        },

        /**
         * Clear the data in the clipboard
         */
        clear : function()
        {
            this.$data = null;
        }
    };
});
