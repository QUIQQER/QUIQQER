/**
 * Plugin Class
 *
 * Events
 * - onUserTabLoad [Tab]
 * - onUserTabUnload [Tab]
 * - onSiteTabLoad [Tab]
 * - onSiteTabUnload [Tab]
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/Plugin', ['classes/DOM'], function(DOM)
{
    "use strict";

    QUI.classes.Plugin = new Class({

        Implements: [DOM],

        options : {
            name : '',
            css  : [],
            js   : []
        },

        initialize : function(options)
        {
            this.$loaded = false;
            this.init( options );
        },

        load : function(onfinish)
        {
            if (this.getAttribute('css') &&
                this.getAttribute('css').length)
            {
                QUI.css(this.getAttribute('css'));
            }

            if (this.getAttribute('js') &&
                this.getAttribute('js').length)
            {
                requirejs(this.getAttribute('js'), onfinish);
                return;
            }

            this.$loaded = true;

            onfinish();
        },

        isLoaded : function()
        {
            return this.$loaded;
        },

        onUserTabLoad : function(Tab)
        {
            this.fireEvent('userTabLoad', [Tab]);
        },

        onUserTabUnload : function(Tab)
        {
            this.fireEvent('userTabUnload', [Tab]);
        }
    });

});