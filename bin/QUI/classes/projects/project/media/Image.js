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

define('classes/projects/project/media/Image', [

    'classes/projects/project/media/Item'

], function(MediaItem)
{
    "use strict";

    /**
     * @class QUI.classes.projects.media.Image
     *
     * @memberof! <global>
     */
    return new Class({
        Extends : MediaItem,
        Type    : 'classes/projects/project/media/Image'
    });
});