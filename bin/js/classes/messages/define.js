/**
 * Message Handler Packet definieren
 *
 * @package com.pcsg.qui.js.classes.messages
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages', [

    'classes/messages/Handler',
    'classes/messages/Message',
    'classes/messages/Attention',
    'classes/messages/Error',
    'classes/messages/Information',
    'classes/messages/Success'

], function(Handler)
{
    "use strict";

    return Handler;
});
