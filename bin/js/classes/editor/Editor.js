/**
 * Editor Main Class
 *
 * The editor main class is the parent class for all WYSIWYG editors.
 * Every WYSIWYG editor must inherit from this class
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/editor/Editor
 * @package com.pcsg.qui.js.classes.editor
 * @namespace QUI.classes.editor
 */

define('classes/editor/Editor', [

    'classes/DOM'

], function(QDOM)
{
    QUI.namespace( 'classes.editor' );

    /**
     * @class QUI.classes.Editor
     *
     * @param {QUI.controls.editor.Manager} Manager
     * @param {Object} options
     *
     * @fires onInit [this]
     * @fires onDraw [DOMNode, this]
     * @fires onDestroy [this]
     * @fires onSetContent [String, this]
     * @fires onGetContent [this]
     * @fires onLoaded [Editor, Instance]
     */
    QUI.classes.editor.Editor = new Class({

        Implements : [ QDOM ],
        Type       : 'QUI.classes.editor.Editor',

        options : {
            content : ''
        },

        initialize : function(Manager, options)
        {
            this.$Manager = Manager;

            this.init( options );
            this.$Instance = null;

            this.addEvent('onLoaded', function(Editor, Instance)
            {
                if ( Editor.getAttribute( 'content' ) ) {
                    Editor.setContent( Editor.getAttribute('content') );
                }
            });

            this.fireEvent( 'init', [ this ] );
        },

        /**
         *
         * @returns
         */
        getManager : function()
        {
            return this.$Manager;
        },

        /**
         * Draw the editor
         *
         * @method QUI.classes.editor.Editor#draw
         *
         * @fires onDraw [DOMNode, this]
         *
         * @param {DOMNode} Container - The DOMNode in which the editor should be displayed
         */
        draw : function(Container)
        {
            this.fireEvent( 'draw', [ Container, this ] );
        },

        /**
         * Destroy the editor
         *
         * @method QUI.classes.editor.Editor#destroy
         *
         * @fires onDestroy [this]
         */
        destroy : function()
        {
            this.fireEvent( 'destroy', [ this ] );
            this.removeEvents();

            this.getManager().destroyEditor( this );
        },

        /**
         * Set the content to the editor
         *
         * @method QUI.classes.editor.Editor#setContent
         *
         * @fires onSetContent [content, this]
         * @param {String} content - HTML String
         */
        setContent : function(content)
        {
            this.setAttribute( 'content', content );
            this.fireEvent( 'setContent', [ content, this ] );
        },

        /**
         * Get the content from the editor
         *
         * @method QUI.classes.editor.Editor#getContent
         *
         * @return {String}
         */
        getContent : function()
        {
            this.fireEvent( 'getContent', [ this ] );

            return this.getAttribute( 'content' );
        },

        /**
         * Set the editor instance
         *
         * @method QUI.classes.editor.Editor#setInstance
         *
         * @param {Editor Instance} Instance
         */
        setInstance : function(Instance)
        {
            this.$Instance = Instance;
        },

        /**
         * Get the editor instance<br />
         * ckeditor, tinymce and so on
         *
         * @method QUI.classes.editor.Editor#getInstance
         *
         * @return {Editor Instance}
         */
        getInstance : function()
        {
            return this.$Instance;
        }
    });

    return QUI.classes.editor.Editor;
});