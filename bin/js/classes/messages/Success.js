/**
 * Information
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages/Success', [

    'classes/messages/Message'

], function(Message)
{
    "use strict";

    QUI.namespace('classes.messages');

    /**
     * @class QUI.classes.messages.Success
     *
     * @memberof! <global>
     */
    QUI.classes.messages.Success = new Class({

        Implements: [ Message ],

        getType : function() {
            return 'Message.Success';
        }
    });

    return QUI.classes.messages.Success;
});