/**
 * Opens a site search panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module admin/search/Site
 * @package
 * @namespace
 */

define('admin/search/Site', [

    'controls/projects/site/Search'

], function(Search)
{
    "use strict";

    return function()
    {
        QUI.Workspace.appendPanel(
            new Search()
        );
    };
});
