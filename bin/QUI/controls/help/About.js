/**
 * Help Window
 *
 * @author www.namerobot.com (Henning Leutz)
 */

define('controls/help/About', [

    'qui/controls/windows/Popup'

], function(Popup)
{
    "use strict";

    return new Class({

        Extends : Popup,
        Type    : 'controls/help/About',

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
            this.getContent().set(
                'html',

                '<div style="text-align: center; margin-top: 30px;">' +
                    '<h2>QUIQQER Management System</h2>' +
                    '<p><a href="http://www.quiqqer.com" target="_blank">www.quiqqer.com</a></p>' +
                    '<br />' +
                    'Version: ' + QUIQQER_VERSION +
                    '<br />' +
                    '<p>' +
                        'Copyright ' +
                        '<a href="http://www.namerobot.com" target="_blank">' +
                            'http://www.namerobot.com' +
                        '</a>' +
                    '</p>' +
                    '<p>Author: Henning Leutz & Moritz Scholz</p>' +
                '</div>'
            );
        }
    });
});