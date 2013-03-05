/**
 * A media image
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/project/media/Item
 *
 * @module classes/project/media/Image
 * @package com.pcsg.qui.js.classes.project.media
 * @namespace classes.project.media
 */

define('classes/project/media/Image', [

    'classes/project/media/Item'

], function(MediaItem)
{
    QUI.namespace( 'classes.project.media' );

    /**
     * @class QUI.classes.project.media.Image
     */
    QUI.classes.project.media.Image = new Class({
        Implements: [MediaItem],
        Type      : 'QUI.classes.project.media.Image'
    });

    return QUI.classes.project.media.Image;
});