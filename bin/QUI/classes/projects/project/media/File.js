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

define('classes/projects/project/media/File', [

    'classes/projects/project/media/Item'

], function(MediaItem)
{
    "use strict";

    /**
     * @class QUI.classes.projects.media.File
     *
     * @memberof! <global>
     */
    return new Class({
        Extends : MediaItem,
        Type    : 'classes/projects/project/media/File'
    });
});