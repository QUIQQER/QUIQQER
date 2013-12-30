/**
 * A media Popup
 *
 * @author www.namerobot.com (Henning Leutz)
 */

define('controls/projects/project/media/Popup', [

    'qui/controls/windows/Window'

], function(Window)
{
    "use strict";

    return new Class({

        Extends : Window,
        Type    : 'controls/projects/project/media/Popup',

        initialize : function(options)
        {
            this.parent( options );
        }

    });

});