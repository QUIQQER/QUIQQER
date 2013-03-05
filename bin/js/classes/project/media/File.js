/**
 * A media file
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/project/media/Item
 *
 * @module classes/project/media/File
 * @package com.pcsg.qui.js.classes.project.media
 * @namespace classes.project.media
 */

define('classes/project/media/File', [

    'classes/project/media/Item'

], function(MediaItem)
{
    QUI.namespace( 'classes.project.media' );

    /**
     * @class QUI.classes.project.media.File
     */
    QUI.classes.project.media.File = new Class({
        Implements: [MediaItem],
        Type      : 'QUI.classes.project.media.File'
    });

    return QUI.classes.project.media.File;
});