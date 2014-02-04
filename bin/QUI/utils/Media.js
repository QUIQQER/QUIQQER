/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 *
 * @depricated
 */

define('utils/Media', function()
{
    "use strict";

    return {

        /**
         * Return a icon by its mime type
         *
         * @param {String} mimtype - MimeType of the file
         * @return {String}
         */
        getIconByMimeType : function(mimtype)
        {
            if ( typeof mimtype === 'undefined' ) {
                return URL_BIN_DIR +'16x16/extensions/empty.png';
            }

            if ( mimtype.match('image/') ) {
                return URL_BIN_DIR +'16x16/extensions/image.png';
            }

            if ( mimtype.match('/pdf') ) {
                return URL_BIN_DIR +'16x16/extensions/pdf.png';
            }

            if ( mimtype.match('/zip') ||
                 mimtype.match('/gzip') )
            {
                return URL_BIN_DIR +'16x16/extensions/archive.png';
            }

            if ( mimtype.match('audio/') ) {
               return URL_BIN_DIR +'16x16/extensions/audio.png';
            }

            return URL_BIN_DIR +'16x16/extensions/empty.png';
        },

        /**
         * Return the image url by its image params
         *
         * @param {String} id - id of the file
         * @param {String} project - project name
         *
         * @return {String}
         */
        getUrlByImageParams : function(id, project)
        {
            return 'image.php?id='+ id +'&project='+ project;
        }
    };
});