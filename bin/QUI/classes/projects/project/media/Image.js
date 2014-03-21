/**
 * A media image
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module classes/projects/project/media/Image
 * @requires classes/projects/project/media/Item
 */

define('classes/projects/project/media/Image', [

    'classes/projects/project/media/Item'

], function(MediaItem)
{
    "use strict";

    /**
     * @class classes/projects/project/media/Image
     *
     * @memberof! <global>
     */
    return new Class({
        Extends : MediaItem,
        Type    : 'classes/projects/project/media/Image'
    });
});