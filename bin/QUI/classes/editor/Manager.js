/**
 * Editor Manager
 *
 * The editor manager creates the editors and load all required classes
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/classes/DOM
 * @requires Ajax
 *
 * @module controls/editor/Manager
 * @package com.pcsg.quiqqer
 */

define('classes/editor/Manager', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function(QUI, QDOM, Ajax)
{
    "use strict";

    /**
     * Editor Manager
     *
     * @class classes/editor/Manager
     * @memberof! <global>
     */
    return new Class({

        Extends : QDOM,
        Type    : 'classes/editor/Manager',

        options : {

        },

        initialize : function(options)
        {
            this.$config    = null;
            this.$editors   = {};
            this.$instances = {};
        },

        /**
         * Register editor parameter
         * You can register different editors like ckeditor3, tinymce and so on
         * with the params can you define specific functionality for different
         * editors
         *
         * @method classes/editor/Manager#register
         *

@example

Manager.register('package/ckeditor4', {
    events : {},
    methods : {}
});

         *
         * @param {String} name
         * @param {Object} onload - Editor parameters, see example
         */
        register : function(name, onload_params)
        {
            this.$editors[ name ] = onload_params;
        },

        /**
         * Register an editor instance
         *
         * @method classes/editor/Manager#$registerEditor
         * @param {controls/editors/Editor} Instance
         *
         * @ignore
         */
        $registerEditor : function(Instance)
        {
            this.$instances[ Instance.getId() ] = Instance;
        },

        /**
         * It generate a {controls/editors/Editor} Instance from an editor parameter
         *
         * @method classes/editor/Manager#getEditor
         *
         * @param {String|null} name - Editor parameters name, like ckeditor3, if null,
         * @param {Function} func    - Callback function, if editor is loaded,
         *                             the Parameter of the function is an {controls/editors/Editor} Instance
         */
        getEditor : function(name, func)
        {
            var self = this;

            name = name || null;

            // use the standard editor
            if ( name === null )
            {
                this.getConfig(function()
                {
                    self.getEditor(
                        self.$config.settings.standard,
                        func
                    );
                });

                return;
            }

            if ( typeof this.$editors[ name ] !== 'undefined' )
            {
                var Editor = new this.$editors[ name ]( this );

                this.$registerEditor( Editor );

                func( Editor );

                return;
            }

            this.getConfig(function(result)
            {
                require([ self.$config.editors[ name ] ], function(Editor)
                {
                    self.$editors[ name ] = Editor;
                    self.getEditor( name, func );
                });
            });
        },

        /**
         * Destroy an editor
         *
         * @method classes/editor/Manager#destroyEditor
         * @param {controls/editors/Editor} Editor
         */
        destroyEditor : function(Editor)
        {
            var id = Editor.getId();

            if ( typeof this.$instances[ id ] !== 'undefined' ) {
                delete this.$instances[ id ];
            }

            if ( typeof QUI.$storage[ id ] !== 'undefined' ) {
                delete QUI.$storage[ id ];
            }
        },

        /**
         * Get the main Editor config
         *
         * @method classes/editor/Manager#getConfig
         * @param {Function} onfinish - Callback function
         */
        getConfig : function(onfinish)
        {
            if ( this.$config ) {
                return onfinish( this.$config );
            }

            var self = this;

            Ajax.get( 'ajax_editor_get_config', function(result)
            {
                self.$config = result;

                if ( typeof onfinish === 'function' ) {
                    onfinish( result );
                }
            });
        },

        /**
         * Get the toolbar for the user
         *
         * @method classes/editor/Manager#getToolbars
         * @param {Function} onfinish - Callback function
         */
        getToolbar : function(onfinish)
        {
            Ajax.get( 'ajax_editor_get_toolbar', onfinish );
        },

        /**
         * Get all available toolbar
         *
         * @method classes/editor/Manager#getToolbars
         * @param {Function} onfinish - Callback function
         */
        getToolbars : function(onfinish)
        {
            Ajax.get( 'ajax_editor_get_toolbars', onfinish );
        }
    });
});