/**
 * Systemcache manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/Cache
 *
 * @module classes/system/Cache
 * @package com.pcsg.qui.js.classes.system.Manager
 * @namespace QUI.classes.system
 */

define('classes/system/Cache', [

    'classes/DOM',
    'controls/system/Cache'

], function(DOM, Cache)
{
    QUI.namespace( 'classes.system' );

    /**
     * @class QUI.classes.project.media.Item
     *
     * @param {Object} params - Properties / attributes
     */
    QUI.classes.system.Cache = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.Cache',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the cache manager in a panel
         *
         * @method QUI.classes.system.Cache#openInPanel
         * @param {MUI.Apppanel} Panel
         */
        openInPanel : function(Panel)
        {
            new QUI.controls.system.Cache( this, Panel );
        },

        /**
         * The purge function removes stale data from the cache backends while leaving current data intact.
         * Depending on the size of the cache and the specific drivers in use this can take some time,
         * so it is best called as part of a separate maintenance task or as part of a cron job.
         */
        purge : function(oncomplete)
        {
            QUI.Ajax.post('ajax_system_cache_purge', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, {
                oncomplete : oncomplete
            });
        },

        /**
         * Clear the specific cache
         */
        clear : function(params, oncomplete)
        {
            QUI.Ajax.post('ajax_system_cache_clear', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, {
                oncomplete : oncomplete,
                params     : JSON.encode( params )
            });
        }
    });

    return QUI.classes.system.Cache;
});