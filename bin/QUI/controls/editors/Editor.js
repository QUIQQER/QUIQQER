/**
 * Editor Main Class
 *
 * The editor main class is the parent class for all WYSIWYG editors.
 * Every WYSIWYG editor must inherit from this class
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/controls/Control
 *
 * @module controls/editor/Editor
 */

define('controls/editors/Editor', ['qui/controls/Control'], function(Control)
{
    "use strict";

    /**
     * Editor Main Class
     *
     * @class controls/editors/Editor
     *
     * @param {classes/editor/Manager} Manager
     * @param {Object} options
     *
     * @fires onInit [this]
     * @fires onDraw [DOMNode, this]
     * @fires onDestroy [this]
     * @fires onSetContent [String, this]
     * @fires onGetContent [this]
     * @fires onLoaded [Editor, Instance]
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : Control,
        Type    : 'controls/editor/Editor',

        Binds : [
            '$onDrop'
        ],

        options : {
            content : ''
        },

        initialize : function(Manager, options)
        {
            this.$Manager = Manager;

            this.parent( options );

            this.$Instance  = null;
            this.$Container = null;

            this.addEvents({
                onLoaded : function(Editor, Instance)
                {
                    if ( Editor.getAttribute( 'content' ) ) {
                        Editor.setContent( Editor.getAttribute( 'content' ) );
                    }
                }
            });

            this.fireEvent( 'init', [ this ] );
        },

        /**
         * Returns the Editor Manager
         *
         * @method controls/editors/Editor#getManager
         * @return {controls/editors/Manager} Editor Manager
         */
        getManager : function()
        {
            return this.$Manager;
        },

        /**
         * Draw the editor
         *
         * @method controls/editors/Editor#create
         * @fires onDraw [DOMNode, this]
         * @return {DOMNode} DOMNode Element
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                styles : {
                    width  : '100%',
                    height : '100%'
                }
            });

            this.$Elm.addClass( 'media-drop' );
            this.$Elm.set( 'data-quiid', this.getId() );

            this.fireEvent( 'draw', [ this.$Elm, this ] );

            return this.$Elm;
        },

        /**
         * Destroy the editor
         *
         * @method controls/editors/Editor#destroy
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
         * @method controls/editors/Editor#setContent
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
         * @method controls/editors/Editor#getContent
         * @return {String} content
         */
        getContent : function()
        {
            this.fireEvent( 'getContent', [ this ] );

            return this.getAttribute( 'content' );
        },

        /**
         * Set the editor instance
         *
         * @method controls/editors/Editor#setInstance
         * @param {Editor Instance} Instance
         */
        setInstance : function(Instance)
        {
            this.$Instance = Instance;
        },

        /**
         * Get the editor instance
         * ckeditor, tinymce and so on
         *
         * @method controls/editors/Editor#getInstance
         * @return {Editor Instance} Instance
         */
        getInstance : function()
        {
            return this.$Instance;
        },

        /**
         * Open the Meda Popup for Image insertion
         *
         * @param {Object} options - controls/projects/project/media/Popup options
         */
        openMedia : function(options)
        {
            require(['controls/projects/project/media/Popup'], function(Popup) {
                new Popup( options ).open();
            });
        },

        /**
         * Open the Meda Popup for Image insertion
         *
         * @param {Object} options - controls/projects/project/project/Popup options
         */
        openProject : function(options)
        {
            require(['controls/projects/Popup'], function(Popup) {
                new Popup( options ).open();
            });
        }
    });
});