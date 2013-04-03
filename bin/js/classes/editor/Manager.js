/**
 * Editor Manager
 *
 * The editor manager creates the editors and load all required classes
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires classes/editor/Editor
 *
 * @module controls/editor/Manager
 * @package com.pcsg.qui.js.classes.editor
 * @namespace QUI.classes.editor
 */

define('classes/editor/Manager', [

    'classes/DOM',
    'classes/editor/Editor'

], function(QDOM)
{
    "use strict";

    QUI.namespace( 'classes/editor' );

    /**
     * Editor Manager
     *
     * @class QUI.controls.editor.Manager
     * @memberof! <global>
     */
    QUI.classes.editor.Manager = new Class({

        Extends : QDOM,
        Type    : 'QUI.classes.editor.Manager',

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
         * @method QUI.classes.editor.Manager#register
         *

@example

Manager.register('package/ckeditor3', {
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
         * @method QUI.classes.editor.Manager#$registerEditor
         * @param {QUI.classes.Editor} Instance
         *
         * @ignore
         */
        $registerEditor : function(Instance)
        {
            this.$instances[ Instance.getId() ] = Instance;
        },

        /**
         * It generate a {QUI.classes.Editor} Instance from an editor parameter
         *
         * @method QUI.classes.editor.Manager#getEditor
         *
         * @param {String|null} name - Editor parameters name, like ckeditor3, if null,
         * @param {Function} func   - Callback function, if editor is loaded,
         *             the Parameter of the function is an {QUI.classes.Editor} Instance
         */
        getEditor : function(name, func)
        {
            name = name || null;

            // use the standard editor
            if ( name === null )
            {
                this.getConfig(function()
                {
                    this.getEditor(
                        this.$config.settings.standard,
                        func
                    );

                }.bind( this ));

                return;
            }

            if ( typeof this.$editors[ name ] !== 'undefined' )
            {
                var Editor = new this.$editors[ name ]( this );

                this.$registerEditor( Editor );

                func( Editor );

                return;
            }

            this.getConfig(function()
            {
                require([ this.$config.editors[ name ] ], function(Editor)
                {
                    this.$editors[ name ] = Editor;
                    this.getEditor( name, func );
                }.bind( this ));

            }.bind( this ));
        },

        /**
         * Destroy an editor
         *
         * @method QUI.classes.editor.Manager#destroyEditor
         * @param {QUI.classes.Editor} Editor
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

            //delete Editor;
        },

        /**
         * Get the main Editor config
         *
         * @method QUI.classes.editor.Manager#getConfig
         * @param {Function} onfinish - Callback function
         */
        getConfig : function(onfinish)
        {
            if ( this.$config ) {
                return onfinish( this.$config );
            }

            QUI.Ajax.get( 'ajax_editor_get_config', function(result, Request)
            {
                Request.getAttribute( 'Manager' ).$config = result;

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, {
                Manager  : this,
                onfinish : onfinish
            } );
        },

        /**
         * Get all available toolbar
         *
         * @method QUI.classes.editor.Manager#getToolbars
         * @param {Function} onfinish - Callback function
         */
        getToolbars : function(onfinish)
        {
            QUI.Ajax.get( 'ajax_editor_get_toolbars', onfinish );
        }
    });

    return QUI.classes.editor.Manager;
});