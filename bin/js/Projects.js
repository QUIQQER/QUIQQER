/**
 * Global projects manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Projects
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('Projects', [

    'classes/projects/Projects'

], function(QUI_Projects)
{
    "use strict";

    if ( typeof QUI.Projects !== 'undefined' ) {
        return QUI.Projects;
    }

    QUI.Projects = new QUI_Projects();

    return QUI.Projects;
});