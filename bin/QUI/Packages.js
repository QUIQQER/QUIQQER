/**
 * Packages
 *
 * @module Packages
 * @author www.pcsg.de (Henning Leutz)
 * @deprecated -> package manager
 */
define('Packages', ['classes/packages/Manager'], function (Manager) {
    "use strict";
    return new Manager();
});
