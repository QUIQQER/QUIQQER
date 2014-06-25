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
 * @event onInit [ {self} ]
 * @event onDraw [ {self} ]
 * @event onDestroy[ {self} ]
 * @event onSetContent [ {String} content, {self} ]
 * @event onAddCSS [ {String} file, {self} ]
 */

define(['qui/controls/Control'], function(Control)
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
            content : '',

            bodyId    : false,  // wysiwyg DOMNode body id
            bodyClass : false   // wysiwyg DOMNode body css class
        },

        initialize : function(Manager, options)
        {
            var self = this;

            this.$Manager = Manager;

            this.parent( options );

            this.$Instance  = null;
            this.$Container = null;

            this.addEvents({
                onLoaded : function(Editor, Instance)
                {
                    if ( self.getAttribute( 'bodyId' ) ) {
                        self.getDocument().body.id = self.getAttribute( 'bodyId' );
                    }

                    if ( self.getAttribute( 'bodyClass' ) ) {
                        self.getDocument().body.className = self.getAttribute( 'bodyClass' );
                    }

                    if ( self.getAttribute( 'content' ) ) {
                        self.setContent( self.getAttribute( 'content' ) );
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
         * Return the Document DOM element of the editor frame
         *
         * @param {DOMNode} document
         */
        getDocument : function()
        {
            return this.$Elm.getElement( 'iframe' ).contentWindow.document;
        },

        /**
         * Add an CSS file to the Instance
         */
        addCSS : function(file)
        {
            this.fireEvent( 'addCSS', [ file, this ] );
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