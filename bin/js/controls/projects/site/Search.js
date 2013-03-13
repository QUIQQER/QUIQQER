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
    QUI.namespace( 'controls.projects.site' );

    QUI.controls.projects.site.Site = new Class({

        Implements: [ QUI_Control ],

        options : {
            id : 'project-site-search'
        },

        initialize : function(options)
        {

        }
    });

    return QUI.controls.projects.site.Site;
});