/**
 * A media file
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/projects/media/Item
 *
 * @module classes/projects/project/media/File
 */

define('classes/projects/project/media/File', [

    'classes/projects/project/media/Item'

], function(MediaItem)
{
    "use strict";

    /**
     * @class classes/projects/project/media/File
     *
     * @memberof! <global>
     */
    return new Class({
        Extends : MediaItem,
        Type    : 'classes/projects/project/media/File'
    });
});