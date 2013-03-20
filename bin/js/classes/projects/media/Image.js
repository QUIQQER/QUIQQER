/**
 * A media image
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/projects/media/Item
 *
 * @module classes/projects/media/Image
 * @package com.pcsg.qui.js.classes.projects.media
 * @namespace classes.projects.media
 */

define('classes/projects/media/Image', [

    'classes/projects/media/Item'

], function(MediaItem)
{
    "use strict";

    QUI.namespace( 'classes.projects.media' );

    /**
     * @class QUI.classes.projects.media.Image
     *
     * @memberof! <global>
     */
    QUI.classes.projects.media.Image = new Class({
        Implements: [ MediaItem ],
        Type      : 'QUI.classes.projects.media.Image'
    });

    return QUI.classes.projects.media.Image;
});