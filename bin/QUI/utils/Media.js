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
        }
    };
});