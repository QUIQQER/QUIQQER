
/**
 * QUIQQER for the front end Templates
 * QUI provides the API to load Plugins
 *
 * @requires requirejs
 */

var QUI =
{
    $events : {},

    /**
     * Add a callback function to an event
     *
     * @param {String} event
     * @param {Function} callback
     */
    addEvent : function(event, callback)
    {
        "use strict";

        if ( typeof this.$events[ event ] === 'undefined' ) {
            this.$events[ event ] = [];
        }

        this.$events[ event ].push( callback );
    },

    /**
     * Trigger all function that binded to the wanted event
     *
     * @param {String} event
     */
    fireEvent : function(event)
    {
        "use strict";

        if ( typeof this.$events[ event ] === 'undefined' ) {
            return;
        }

        for ( var i = 0, len = this.$events[ event ].length; i < len; i++ ) {
            this.$events[ event ][ i ]();
        }
    }
};

require(['mvc/domReady'], function (domReady)
{
    "use strict";

});