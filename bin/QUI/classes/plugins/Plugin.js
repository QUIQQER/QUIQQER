/**
 *
 */

define('classes/plugins/Plugin', [

    'qui/classes/DOM'

], function(DOM)
{
    "use strict";

    return new Class({

        Extends : DOM,
        Type    : 'classes/plugins/Plugin',

        initialize : function(options)
        {
            this.parent( options );
        }

    });
});