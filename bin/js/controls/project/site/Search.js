/**
 * a site search
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/project/site/Search
 * @package com.pcsg.qui.js.controls.project.site
 * @namespace QUI.controls.project.site
 */

define('controls/project/site/Search', [

    'controls/Control'

], function(QUI_Control)
{
    QUI.namespace( 'controls.project.site' );

    QUI.controls.project.site.Site = new Class({

        Implements: [ QUI_Control ],

        options : {
            id : 'project-site-search'
        },

        initialize : function(options)
        {

        }
    });

    return QUI.controls.project.site.Site;
});