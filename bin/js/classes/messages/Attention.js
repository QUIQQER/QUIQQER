/**
 * Warnung, Hinweis
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages/Attention', [

    'classes/messages/Message'

], function(Message)
{
    QUI.namespace('classes.messages');

    QUI.classes.messages.Attention = new Class({

        Implements: [ Message ],
        Type      : 'QUI.classes.messages.Attention',

        getType : function() {
            return 'Message.Attention';
        }

    });

    return QUI.classes.messages.Attention;
});
