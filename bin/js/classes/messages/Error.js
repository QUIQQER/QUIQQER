/**
 * Fehler
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages/Error', [

    'classes/messages/Message'

], function(Message)
{
    QUI.namespace('classes.messages');

    QUI.classes.messages.Error = new Class({

        Implements: [Message],

        getType : function() {
            return 'Message.Error';
        }
    });

    return QUI.classes.messages.Error;
});