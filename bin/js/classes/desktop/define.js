/**
 * Message Handler Packet definieren
 *
 * @package com.pcsg.qui.js.classes.desktop
 * @module classes/desktop
 * @author www.pcsg.de (Henning Leutz)
 * @namespace QUI.Desktop
 */

define('classes/desktop', [

    'classes/desktop/Desktop',
    'classes/desktop/Widget',
    'classes/desktop/Starter'

], function(Desktop, Widget, Starter)
{
    "use strict";

    return Desktop;
});