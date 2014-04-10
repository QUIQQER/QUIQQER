/**
 * SyntaxHighlighter
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/classes/DOM
 * @module classes/utils/SyntaxHighlighter
 */

define('classes/utils/SyntaxHighlighter', [

    'qui/classes/DOM'

], function(DOM)
{
    "use strict";

    /**
     * @class classes/utils/SyntaxHighlighter
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/utils/SyntaxHighlighter',

        /**
         * Highlight the code into an Element
         *
         * @param {DOMNode} Node
         * @todo prism as quiqqer control
         */
        highlight : function(Node)
        {
            require(['plugin/prism/Prism'], function()
            {
                if ( typeof Prism !== 'undefined' ) {
                    Prism.highlightElement( Node );
                }
            }, function()
            {
                // ignore if prism not exist
            });
        }
    });
});
