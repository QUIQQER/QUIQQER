/**
 * Global projects manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Projects
 */

define('Projects', ['classes/projects/Manager'], function(Projects)
{
    "use strict";

    if ( typeof QUI.Projects !== 'undefined' ) {
        return QUI.Projects;
    }

    QUI.Projects = new Projects();

    return QUI.Projects;
});