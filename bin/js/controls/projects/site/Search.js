/**
 * a site search
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/site/Search
 * @package com.pcsg.qui.js.controls.projects.site
 * @namespace QUI.controls.projects.site
 */

define('controls/projects/site/Search', [

    'controls/Control'

], function(QUI_Control)
{
    "use strict";

    QUI.namespace( 'controls.projects.site' );

    /**
     * @class QUI.controls.projects.site.Site
     *
     * @memberof! <global>
     */
    QUI.controls.projects.site.Site = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.projects.site.Site',

        options : {
            id : 'project-site-search'
        },

        initialize : function(options)
        {

        }
    });

    return QUI.controls.projects.site.Site;
});