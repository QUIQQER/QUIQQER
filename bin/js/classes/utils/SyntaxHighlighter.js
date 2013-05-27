/**
 * SyntaxHighlighter
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/utils/SyntaxHighlighter
 * @package com.pcsg.qui.js.classes.utils
 * @namespace QUI.classes.utils
 */

define('classes/utils/SyntaxHighlighter', [

    'classes/DOM',
    'lib/prism/Prism'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.utils' );

    /**
     * @class QUI.classes.utils.SyntaxHighlighter
     */
    QUI.classes.utils.SyntaxHighlighter = new Class({

        Extends : DOM,
        Type    : 'SyntaxHighlighter',

        /**
         * Highlight the code into an Element
         *
         * @param {DOMNode} Node
         */
        highlight : function(Node)
        {
            if ( typeof Prism !== 'undefined' ) {
                Prism.highlightElement( Node );
            }
        }
    });

    return QUI.classes.utils.SyntaxHighlighter;
});
