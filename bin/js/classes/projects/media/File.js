/**
 * A media file
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/projects/media/Item
 *
 * @module classes/projects/media/File
 * @package com.pcsg.qui.js.classes.projects.media
 * @namespace classes.projects.media
 */

define('classes/projects/media/File', [

    'classes/projects/media/Item'

], function(MediaItem)
{
    QUI.namespace( 'classes.projects.media' );

    /**
     * @class QUI.classes.projects.media.File
     */
    QUI.classes.projects.media.File = new Class({
        Implements: [ MediaItem ],
        Type      : 'QUI.classes.projects.media.File'
    });

    return QUI.classes.projects.media.File;
});