/**
 * Information
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages/Success', [

    'classes/messages/Message'

], function(Message)
{
    QUI.namespace('classes.messages');

    QUI.classes.messages.Success = new Class({

        Implements: [Message],

        getType : function() {
            return 'Message.Success';
        }
    });

    return QUI.classes.messages.Success;
});