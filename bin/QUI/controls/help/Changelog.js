/**
 * Help Window
 *
 * @author www.namerobot.com (Henning Leutz)
 */

define('controls/help/Changelog', [

    'qui/controls/windows/Popup',
    'Ajax'

], function(Popup, Ajax)
{
    "use strict";

    return new Class({

        Extends : Popup,
        Type    : 'controls/help/Changelog',

        Binds : [
            '$onOpen'
        ],

        options : {
            maxHeight : 350
        },

        initialize : function(options)
        {
            this.parent( options );
            this.addEvent( 'onOpen', this.$onOpen );
        },

        $onOpen : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_system_changelog', function(result, Request)
            {
                self.getContent().set(
                    'html',
                    '<pre><code>'+ result +'</code></pre>'
                );

                self.getContent().getElement( 'pre' ).setStyles({
                    height     : 190,
                    whiteSpace : 'pre-wrap'
                });

                self.Loader.hide();
            });
        }
    });
});