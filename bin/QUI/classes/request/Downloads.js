
/**
 * QUI file download manager
 * For downloading files via the ajax API
 *
 * @module classes/request/Downloads
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 *
 * @event onComplete
 *
 * @example
 * require(['DownloadManager'], function(DownloadManager)
   {
        DownloadManager.download( 'ajax_downloadTest' );
   })
 */

define('classes/request/Downloads', [

    'qui/classes/DOM',
    'Ajax'

], function (DOM, Ajax) {
    "use strict";

    /**
     * QUI download class for download files to the QUIQQER from external resources
     *
     * @class classes/request/Download
     *
     * @fires onDragenter [event, Target, this]
     * @fires onDragend [event, Target, this]#
     * @fires onDrop [event, file_list, Target, this]
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/request/Downloads',

        $Request : null,
        $result  : null,

        options : {

        },

        initialize : function (options) {
            this.parent(options);
            this.$frames = {};
        },

        /**
         * Create a iframe and call dwnload.php with the wanted params
         *
         * @method classes/request/Downloads#download
         *
         * @params {String} request  - Request (Ajax) Function
         * @params {Object} [params] - extra GET params
         */
        download : function (request, params) {
            var self    = this,
                frameId = String.uniqueID();

            params = params || {};
            params._frameId = frameId;

            var url = Ajax.$url + '?' + Ajax.parseParams(request, params);

            this.$frames[ frameId ] = new Element('iframe', {
                src    : url,
                id     : frameId,
                styles : {
                    position : 'absolute',
                    top      : -200,
                    left     : -200,
                    width    : 50,
                    height   : 50
                },
                events :
                {
                    load : function () {
                        (function () {
                            self.updateStatus(this.id);
                        }).delay(500, this);
                    }
                }
            });

            this.$frames[ frameId ].inject(document.body);
        },

        /**
         * PHP connection for status updates
         *
         * @method classes/request/Downloads#updateStatus
         */
        updateStatus : function (frameId) {
            if (frameId in this.$frames) {
                // dl fertig
                this.$frames[ frameId ].destroy();
                this.fireEvent('complete', [this]);
            }
        }
    });
});
