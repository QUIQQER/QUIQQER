/**
 * QUI download class for download files to the QUIQQER from external resources
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/request/Download
 * @package com.pcsg.qui.js.classes.request
 * @namespace QUI.classes
 */

define('classes/request/Download', [

    'classes/DOM'

], function(DOM)
{
    QUI.namespace('classes.request');

    /**
     * @fires onDragenter [event, Target, this]
     * @fires onDragend [event, Target, this]#
     * @fires onDrop [event, file_list, Target, this]
     */
    QUI.classes.request.Download = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.request.Download',

        $Request : null,
        $result  : null,

        options : {

        },

        initialize : function(file, options)
        {
            this.$file  = file;
            this.$frame = null;

            this.init( options );
        },

        start : function()
        {
            this.$frame = new Element('iframe', {
                src    : URL_DIR +'admin/bin/update.php?file='+ file +'&',
                styles : {
                    position : 'absolute',
                    top      : -200,
                    left     : -200,
                    width    : 50,
                    height   : 50
                }
            });

            this.$frame.inject( document.body );
        },

        /**
         * PHP connection for status updates
         */
        updateStatus : function(status)
        {


            if ( status == 100 )
            {
                // dl fertig
                this.$frame.destroy();
                this.fireEvent('complete', [this]);
            }
        }
    });

    return QUI.classes.request.Download;
});