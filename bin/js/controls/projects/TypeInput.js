/**
 * The type input set the type control on the input field
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/projects/TypeButton
 * @requires controls/projects/TypeWindow
 * @requires lib/Plugins
 *
 * @module controls/projects/TypeInput
 * @package com.pcsg.qui.js.controls.projects
 * @namespace QUI.controls.projects
 */

define('controls/projects/TypeInput', [

    'controls/Control',
    'controls/projects/TypeButton',
    'controls/projects/TypeWindow',
    'lib/Plugins'

], function(Control, QUI_TypeButton, QUI_TypeWindow, Plugins)
{
    QUI.namespace( 'controls.projects' );

    /**
     * @class QUI.controls.projects.TypeInput
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
     *
     * @param {Object} options
     * @param {DOMNode} Input - Input field [optional]
     */
    QUI.controls.projects.TypeInput = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.projects.TypeInput',

        options : {
            project : false,
            name    : ''
        },

        initialize : function(options, Input)
        {
            this.init( options );

            this.$Input = Input || null;
            this.$Elm   = null;
            this.$Text  = null;
        },

        /**
         * Create the button and the type field
         *
         * @method QUI.controls.projects.TypeInput#create
         * @return {DOMNode}
         */
        create : function()
        {
            if ( !this.$Input )
            {
                this.$Input = new Element('input', {
                    name : this.getAttribute( 'name' )
                });
            }

            this.$Input.type = 'hidden';

            this.$Elm = new Element( 'div' );
            this.$Elm.wraps( this.$Input );

            // create the type button
            new QUI.controls.projects.TypeButton({
                events :
                {
                    onSubmit : function(result, Btn)
                    {
                        this.$Input.value = result;
                        this.loadTypeName();
                    }.bind( this )
                }
            }).inject( this.$Elm ) ;


            this.$Text = new Element('div.types-text', {
                styles : {
                    margin  : 5,
                    'float' : 'left'
                }
            });

            this.$Text.inject( this.$Elm );

            // load the user type name
            this.loadTypeName();

            return this.$Elm;
        },

        /**
         * Load the user-type-name to the control
         *
         * @method QUI.controls.projects.TypeInput#loadTypeName
         */
        loadTypeName : function()
        {
            this.$Text.set(
                'html',
                '<img src="'+ URL_BIN_DIR +'images/loader.gif" />'
            );

            QUI.lib.Plugins.getTypeName(this.$Input.value, function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                if ( Control.$Text ) {
                    Control.$Text.set( 'html', result );
                }
            }, {
                Control : this,
                onError : function(Exception, Request)
                {
                    var Control = Request.getAttribute( 'Control' );

                    if ( Control.$Text )
                    {
                        Control.$Text.set(
                            'html',
                            '<span style="color: red">#unknown</span>'
                        );
                    }
                }
            });
        }
    });

    return QUI.controls.projects.TypeInput;
});