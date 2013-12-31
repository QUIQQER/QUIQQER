/**
 * Global Upload Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module UploadManager
 * @package com.pcsg.qui.js
 */

define('UploadManager', [

    'controls/upload/Manager'

], function(UploadManager)
{
    "use strict";

    if ( typeof QUI.UploadManager !== 'undefined' ) {
        return QUI.UploadManager;
    }

    QUI.UploadManager = new UploadManager();

    return QUI.UploadManager;
});