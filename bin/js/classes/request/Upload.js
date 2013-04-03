/**
 * QUI upload class for multible files
 *
 * drag/drop upload script
 * add dragdrop Events to the elements
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires classes/exceptions/Exception
 *
 * @module classes/request/Upload
 * @package com.pcsg.qui.js.classes.request
 * @namespace QUI.classes
 */

define('classes/request/Upload', [

    'classes/DOM',
    'classes/exceptions/Exception'

], function(DOM)
{
    "use strict";

    QUI.namespace('classes.request');

    /**
     * QUI upload class for multible files
     * add dragdrop Events to the elements
     *
     * @class QUI.classes.request.Upload
     *
     * @fires onDragenter [event, Target, this]
     * @fires onDragend [event, Target, this]#
     * @fires onDrop [event, file_list, Target, this]
     *
     * @param {Array} elements - list pf DOMNode Elements
     * @param {Object} events - list of event functions
     *
     * @memberof! <global>
     */
    QUI.classes.request.Upload = new Class({

        Extends : DOM,
        Type    : 'QUI.classes.request.Upload',

        $Request : null,
        $result  : null,

        options : {

        },

        initialize : function(elements, events)
        {
            this.addEvents( events );

            this.$elms = elements;

            var add_events =
            {
                dragenter : function(event)
                {
                    this.fireEvent( 'dragenter', [ event, event.target, this ] );
                }.bind( this ),

                dragleave : function(event)
                {
                    this.fireEvent( 'dragend', [ event, event.target, this ] );
                }.bind( this ),

                dragover : function(event)
                {
                    event.preventDefault();
                }.bind( this ),

                drop : function(event)
                {
                    if ( QUI.$droped == Slick.uidOf( event.target ) ) {
                        return;
                    }

                    // no double dropping
                    QUI.$droped = Slick.uidOf( event.target );

                    (function() {
                        QUI.$droped = false;
                    }).delay( 200, this );

                    this.fireEvent('drop', [
                        event,
                        this.$getFilesByEvent( event ),
                        event.target,
                        this
                    ]);

                    this.fireEvent( 'dragend', [ event, event.target, this ] );

                    event.preventDefault();
                    event.stop();

                }.bind( this ),

                dragend : function(event)
                {
                    event.preventDefault();
                    event.stop();

                    this.fireEvent( 'dragend', [ event, event.target, this ] );
                }
            };

            for ( var i = 0, len = this.$elms.length; i < len; i++ ) {
                this.$elms[ i ].addEvents( add_events );
            }
        },

        /**
         * Trigger the send event
         *
         * @method QUI.classes.request.Upload#$getFilesByEvent
         * @param {Event} event - event triggerd from onDrop
         * @return {FileList|Array} FileList or an Array
         */
        $getFilesByEvent : function(event)
        {
            var transfer = event.event.dataTransfer,
                files    = transfer.files || false;

            if ( typeof FileReader === 'undefined' ||
                 typeof FileList === 'undefined' )
            {
                QUI.MH.addError( "Your Browser doesn't support Drag & Drop uploads" );
                return [];
            }

            if ( !files || !files.length ) {
                return new FileList();
            }

            return files;
        }
    });

    return QUI.classes.request.Upload;
});