/**
 * Global projects manager
 *
 * @module Projects
 * @author www.pcsg.de (Henning Leutz)
 * @require classes/projects/Manager
 */
define('Projects', ['classes/projects/Manager'], function (Manager) {
    "use strict";

    return new Manager();
});
