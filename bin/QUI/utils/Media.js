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

define('utils/Media', [

    'qui/QUI',
    'Locale'

], function(QUI, QUILocale)
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
        },

        /**
         *
         * @param {HTMLElement} Input
         */
        bindCheckMediaName : function(Input)
        {
            Input.addEvent('keyup', function(event) {

                // shift code
                if (event.code == 16) {
                    return;
                }

                // alt+gr code
                if (event.code == 225) {
                    return;
                }

                var Elm = event.target,
                    val = this.value;

                // dots
                var substr_count = val.split('.').length - 1;

                if (substr_count > 1) {
                    QUI.getMessageHandler().then(function(MH) {
                        MH.addAttention(
                            QUILocale.get(
                                'quiqqer/system',
                                'exception.media.check.name.dots'
                            ),
                            Elm
                        );
                    });

                    return;
                }

                // special character
                if (val.match(/[^0-9_a-zA-Z \-.]/g)) {
                    console.log('1');

                    QUI.getMessageHandler().then(function(MH) {
                        MH.addAttention(
                            QUILocale.get(
                                'quiqqer/system',
                                'exception.media.check.name.allowed.signs',
                                {filename : val}
                            ),
                            Elm
                        );
                    });
                }

            });
        }
    };
});