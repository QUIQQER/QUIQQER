
/**
 * Global Upload Manager
 *
 * @module UploadManager
 * @author www.pcsg.de (Henning Leutz)
 */

define(['controls/upload/Manager'], function(UploadManager)
{
    "use strict";

    if ( typeof QUI.UploadManager !== 'undefined' ) {
        return QUI.UploadManager;
    }

    QUI.UploadManager = new UploadManager();

    return QUI.UploadManager;
});
