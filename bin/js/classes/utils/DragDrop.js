/**
 * DragDrop Helper with movable Element
 * no ie8
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/utils/DragDrop
 * @package com.pcsg.qui.js.classes.utils
 * @namespace QUI.classes.utils
 *
 * @event onStart [Dragable, event]
 * @event onDrop [Element, Droppable, event]
 * @event onLeave [Element, Droppable]
 * @event onEnter [Element, Droppable]
 * @event onComplete [this, event]
 */

define('classes/utils/DragDrop', [

    'classes/DOM'

], function(DOM)
{
    QUI.namespace( 'classes.utils' );

    /**
     * @class QUI.classes.utils.DragDrop
     *
     * @param {DOMNode} Element - Which Element is dragable
     * @param {Object} options  - QDOM params
     */
    QUI.classes.utils.DragDrop = new Class({

        Implements : [DOM],
        Type       : 'QUI.classes.utils.DragDrop',

        options :
        {
            dropables : [document.body],
            styles    : false,
            cssClass  : false,
            delay     : 250,     // when trigger the dragdrop, after miliseconds

            limit : {
                x : false, // [min, max]
                y : false  // [min, max]
            }
        },

        initialize : function(Element, options)
        {
            this.init( options );

            this.$Drag    = null;
            this.$Element = Element;


            Element.addEvents({

                mousedown : function(event)
                {
                    this.setAttribute( '_stopdrag', false );

                    this.$timer = this.$start.delay(
                        this.getAttribute('delay'),
                        this,
                        event
                    );

                    event.stop();
                }.bind( this ),

                mouseup : function(event)
                {
                    if ( typeof this.$timer !== 'undefined' ) {
                        clearTimeout( this.$timer );
                    }

                    this.$stop( event );
                }.bind( this )
            });
        },

        /**
         * Return the binded Element
         *
         * @return {DOMNode}
         */
        getElm : function()
        {
            return this.$Elm;
        },

        /**
         * Starts the draging by onmousedown
         *
         * @param {DOMEvent} event
         */
        $start : function(event)
        {
            if ( event.rightClick ) {
                return;
            }

            if ( Browser.ie8 ) {
                return;
            }

            if ( this.getAttribute( '_mousedown') ) {
                return;
            }

            if ( this.getAttribute( '_stopdrag' ) ) {
                return;
            }

            this.setAttribute( '_mousedown', true );

            var i, len;

            var mx = event.page.x,
                my = event.page.y,

                Elm     = this.$Element,
                ElmSize = Elm.getSize(),
                limit   = this.getAttribute('limit'),
                docsize = document.body.getSize();

            // create the shadow element
            this.$Drag = new Element('div', {
                'class' : 'box',
                styles : {
                    position   : 'absolute',
                    top        : my - 20,
                    left       : mx - 40,
                    zIndex     : 1000,
                    MozOutline : 'none',
                    outline    : 0,
                    color      : '#fff',
                    padding    : 10,
                    cursor     : 'pointer',

                    width      : ElmSize.x,
                    height     : ElmSize.y,
                    background : 'rgba(0,0,0, 0.5)'
                }
            }).inject( document.body );

            if ( this.getAttribute( 'styles' ) ) {
                this.$Drag.setStyles( this.getAttribute( 'styles' ) );
            }

            if ( this.getAttribute( 'cssClass' ) ) {
                this.$Drag.addClass( this.getAttribute( 'cssClass' ) );
            }


            // set the drag&drop events to the shadow element
            // this.$Drag.addEvent( 'mouseup', this.$stop.bind( this ) );
            // document.body.addEvent( 'mouseup', this.$stop.bind( this ) );

            this.$Drag.focus();
            this.fireEvent( 'start', [ this.$Drag, this ] );


            // if limit exist, checkit
            if ( !limit.x ) {
                limit.x = [ 0, docsize.x - ElmSize.x ];
            }

            if ( !limit.y ) {
                limit.y = [ 0, docsize.y - ElmSize.y ];
            }

            // mootools draging
            new Drag.Move(this.$Drag, {

                droppables : this.getAttribute( 'dropables' ),
                onComplete : this.$complete.bind( this ),

                onDrop     : function(Element, Droppable, event) {
                    this.fireEvent( 'drop', [ Element, Droppable, event ] );
                }.bind( this ),

                onEnter : function(element, Droppable) {
                    this.fireEvent( 'enter', [ element, Droppable ] );
                }.bind( this ),

                onLeave : function(element, Droppable) {
                    this.fireEvent( 'leave', [ element, Droppable ] );
                }.bind( this ),

                limit : limit

            }).start({
                page: {
                    x : mx,
                    y : my
                }
            });
        },

        /**
         * Stops the Draging by onmouseup
         */
        $stop : function()
        {
            if ( Browser.ie8 ) {
                return;
            }

            // Wenn noch kein mousedown drag getätigt wurde
            // mousedown "abbrechen" und onclick ausführen
            if ( !this.getAttribute( '_mousedown' ) )
            {
                this.setAttribute( '_stopdrag', true );
                return;
            }

            this.setAttribute( '_mousedown', false );

            if ( typeof this.$Drag !== 'undefined' || this.$Drag )
            {
                this.fireEvent( 'stop', [ this.$Drag, this ] );

                this.$Drag.destroy();
                this.$Drag = null;
            }
        },

        /**
         * Draging is complete
         */
        $complete : function(event)
        {
            this.fireEvent( 'complete', [ this, event ] );
            this.$stop();
        }
    });

    return QUI.classes.utils.DragDrop;
});
