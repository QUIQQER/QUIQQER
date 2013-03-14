/**
 * Information
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages/Information', [

    'classes/messages/Message'

], function(Message)
{
    QUI.namespace('classes.messages');

    /**
     * @class QUI.classes.messages.Information
     *
     * @memberof! <global>
     */
    QUI.classes.messages.Information = new Class({

        Implements: [ Message ],

        getType : function() {
            return 'Message.Information';
        }
    });

    return QUI.classes.messages.Information;
});