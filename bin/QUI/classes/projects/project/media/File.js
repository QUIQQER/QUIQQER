
/**
 * A media file
 *
 * @module classes/projects/project/media/File
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require classes/projects/media/Item
 */

define(['classes/projects/project/media/Item'], function(MediaItem)
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
