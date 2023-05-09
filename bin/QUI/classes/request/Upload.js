/**
 * QUI upload class for multiple files
 *
 * drag/drop upload script
 * add dragdrop Events to the elements
 *
 * @module classes/request/Upload
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onDragenter [{DOMEvent}, {DOMNode Target}, {self}]
 * @event onDragleave [{DOMEvent}, {DOMNode Target}, {self}]
 * @event onDragover [{DOMEvent}, {DOMNode Target}, {self}]
 * @event onDrop [{DOMEvent}, {Array file list}, {DOMNode Target}, {self}]
 * @event onDragend [{DOMEvent}, {DOMNode Target}, {self}]
 */
define('classes/request/Upload', [

    'qui/QUI',
    'qui/classes/DOM'

], function (QUI, QDOM) {
    "use strict";

    /**
     * QUI upload class for multiple files
     * add dragdrop Events to the elements
     *
     * @class classes/request/Upload
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
    return new Class({

        Extends: QDOM,
        Type   : 'classes/request/Upload',

        $Request: null,
        $result : null,

        options: {},

        initialize: function (elements, events) {
            var self = this;

            // extend mootools with desktop drag drop
            Object.append(Element.NativeEvents, {
                dragenter: 2,
                dragleave: 2,
                dragover : 2,
                dragend  : 2,
                drop     : 2
            });

            this.addEvents(events);
            this.$elms = elements;

            var add_events = {
                dragenter: function (event) {
                    self.fireEvent('dragenter', [event, event.target, self]);
                },

                dragleave: function (event) {
                    self.fireEvent('dragleave', [event, event.target, self]);
                },

                dragover: function (event) {
                    event.preventDefault();
                },

                drop: function (event) {
                    if (QUI.$droped == Slick.uidOf(event.target)) {
                        return;
                    }

                    // no double dropping
                    QUI.$droped = Slick.uidOf(event.target);

                    (function () {
                        QUI.$droped = false;
                    }).delay(200);

                    self.fireEvent('drop', [
                        event,
                        self.$getFilesByEvent(event),
                        event.target,
                        self
                    ]);

                    self.fireEvent('dragend', [event, event.target, self]);

                    event.preventDefault();
                    event.stop();
                },

                dragend: function (event) {
                    event.preventDefault();
                    event.stop();

                    self.fireEvent('dragend', [event, event.target, self]);
                }
            };

            for (var i = 0, len = this.$elms.length; i < len; i++) {
                this.$elms[i].addEvents(add_events);
            }
        },

        /**
         * Trigger the send event
         *
         * @method classes/request/Upload#$getFilesByEvent
         * @param {Event} event - event triggerd from onDrop
         * @return {FileList|Array} FileList or an Array
         */
        $getFilesByEvent: function (event) {
            var transfer = event.event.dataTransfer,
                files    = transfer.files || false;

            if (typeof FileReader === 'undefined' ||
                typeof FileList === 'undefined') {
                QUI.getMessageHandler(function (MH) {
                    MH.addError("Your Browser doesn't support Drag & Drop uploads");
                });

                return [];
            }

            if (!files || !files.length) {
                return [];
            }

            return files;
        }
    });
});
