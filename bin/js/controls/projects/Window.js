/**
 * Displays a Project in Window
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/windows
 * @requires Projects
 * @requires controls/projects/Sitemap
 *
 * @module controls/projects/Window
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.project
 */

define('controls/projects/Window', [

    'controls/Control',
    'controls/windows',
    'Projects',
    'controls/projects/Sitemap'

], function(QUI_Control)
{
    "use strict";

    QUI.namespace( 'controls.projects' );

    /**
     * Displays a Project in Window
     * @class QUI.controls.projects.Window
     *
     * @fires onSubmit - [ids, this]
     * @fires onCancel - this
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.Window = new Class({

        Implements: [ QUI_Control ],

        options : {
            multible    : false,
            project     : false,
            lang        : false,
            information : false,

            title  : '',
            width  : 400,
            height : 500
        },

        initialize : function(options)
        {
            this.init( options );

            this.$Win = null;
            this.$Map = null;

            this.create();
        },

        /**
         * Create the submit window and loads the project in it
         *
         * @method QUI.controls.projects.Window#create
         */
        create : function()
        {
            QUI.Windows.create('submit', {
                title   : this.getAttribute('project'),
                width   : this.getAttribute('width'),
                height  : this.getAttribute('height'),
                icon    : URL_BIN_DIR +'16x16/flags/'+ this.getAttribute('lang') +'.png',
                Control : this,
                events  :
                {
                    onDrawEnd : function(Win)
                    {
                        // load Project Map
                        var Container = Win.getBody(),
                            Control   = Win.getAttribute('Control');

                        if ( Control.getAttribute('information') )
                        {
                            new Element('div', {
                                html    : Control.getAttribute('information'),
                                'class' : 'box',
                                styles  : {
                                    width      : '100%',
                                    padding    : '10px 0 10px 30px',
                                    background : 'url('+ QUI.config('dir') +'controls/projects/images/attention.png) no-repeat left center'
                                }
                            }).inject( Container );
                        }

                        // create the project map
                        this.$Map = new QUI.controls.projects.Sitemap({
                            project : this.getAttribute('project'),
                            lang    : this.getAttribute('lang')
                        });

                        this.$Map.getMap().setAttribute(
                            'multible',
                            Control.getAttribute('multible')
                        );

                        this.$Map.inject( Container );
                        this.$Map.open();

                    }.bind( this ),

                    onSubmit : function(Win)
                    {
                        if ( typeof this.$Map === 'undefined' )
                        {
                            this.fireEvent( 'submit', [[], this] );
                            return;
                        }

                        var i, len;

                        var ids  = [],
                            sels = this.$Map.getSelectedChildren();

                        for ( i = 0, len = sels.length; i < len; i++ )
                        {
                            ids.push(
                                sels[i].getAttribute('value')
                            );
                        }

                        this.fireEvent( 'submit', [ids, this] );

                    }.bind( this ),

                    onCancel : function(Win)
                    {
                        this.fireEvent( 'submit', [this] );
                    }.bind( this )
                }
            });
        }
    });

    return QUI.controls.projects.Window;
});