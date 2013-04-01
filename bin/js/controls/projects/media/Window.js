/**
 * A project media in a popup
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/media/Window
 * @package com.pcsg.qui.js.controls.projects
 * @namespace  QUI.controls.projects.media
 */

define('controls/projects/media/Window', [

    'controls/Control'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.projects.media' );

    /**
     * @class QUI.controls.projects.media.Button
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
      *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.media.Window = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.projects.media.Window',

        options : {
            project : false
        },

        initialize : function(options)
        {
            this.init( options );

            this.$Elm = null;
        }
    });

    return QUI.controls.projects.media.Window;
});