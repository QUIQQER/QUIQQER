/**
 * A project media in a popup
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/project/media/Window
 * @package com.pcsg.qui.js.controls.projects
 * @namespace  QUI.controls.projects.media
 */

define('controls/project/media/Window', [

    'controls/Control'

], function(Control)
{
    QUI.namespace( 'controls.project.media' );

    /**
     * @class QUI.controls.project.media.Button
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
      *
     * @param {Object} options
     */
    QUI.controls.project.media.Window = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.project.media.Window',

        options : {
            project : false
        },

        initialize : function(options)
        {
            this.init( options );

            this.$Elm = null;
        }


    });

    return QUI.controls.project.media.Window;
});