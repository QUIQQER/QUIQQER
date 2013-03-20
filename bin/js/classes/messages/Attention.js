/**
 * Warnung, Hinweis
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages/Attention', [

    'classes/messages/Message'

], function(Message)
{
    "use strict";

    QUI.namespace('classes.messages');

    /**
     * @class QUI.classes.messages.Attention
     *
     * @memberof! <global>
     */
    QUI.classes.messages.Attention = new Class({

        Implements: [ Message ],
        Type      : 'QUI.classes.messages.Attention',

        getType : function() {
            return 'Message.Attention';
        }

    });

    return QUI.classes.messages.Attention;
});
