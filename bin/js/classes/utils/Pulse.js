/**
 * Pulse Effect
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/utils/Pulse
 * @package com.pcsg.qui.js.classes.utils
 * @namespace QUI.classes.utils
 */

define('classes/utils/Pulse', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace('classes.utils');

    /**
     * @class QUI.classes.utils.Pulse
     *
     * @fires onStart
     * @fires onStop
     * @fires onComplete
     * @fires onTick
     *
     * @param {DOMNode} element
     * @param {Integer} options
     */
    QUI.classes.utils.Pulse = new Class({

        Implements : [ DOM ],
        Type       : 'QUI.classes.utils.Pulse',

        options : {
            min      : 0,   // min opacity
            max      : 1,   // max opacity
            duration : 200, // the duration of one effect
            times    : 5    // how often the effect executed
        },

        initialize : function(element, options)
        {
            this.init( options );

            this.element = $( element );
            this.times   = 0;
        },

        /**
         * Start the pulse effekt
         *
         * @method QUI.classes.utils.Pulse#start
         * @param {Integer} times
         */
        start : function(times)
        {
            if ( typeof times === 'undefined' ) {
                times = this.options.times * 2;
            }

            this.running = 1;
            this.fireEvent( 'start' ).run( times -1 );
        },

        /**
         * Stops the pulse effect
         *
         * @method QUI.classes.utils.Pulse#stop
         */
        stop : function()
        {
            this.running = 0;
            this.fireEvent( 'stop' );
        },

        /**
         * Exec a part
         *
         * @method QUI.classes.utils.Pulse#run
         * @param {Integer} times
         */
        run : function(times)
        {
            var to = this.getAttribute( 'min' );

            if ( this.element.get( 'opacity' ) == this.getAttribute( 'min' ) ) {
                to = this.getAttribute( 'max' );
            }

            moofx( self.element ).animate({
                'opacity' : to
            }, {
                callback : function()
                {
                    this.fireEvent( 'tick' );

                    if ( this.running && times )
                    {
                        this.run( times-1 );
                    } else
                    {
                        this.fireEvent( 'complete' );
                    }
                }.bind( this )
            });
        }
    });

    return QUI.classes.utils.Pulse;
});
