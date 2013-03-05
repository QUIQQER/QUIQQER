/**
 * a panel based on a xml file
 * eg settings.xml
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/panels/XML
 * @package com.pcsg.qui.js.controls.desktop.panels
 * @namespace QUI.controls.desktop.panels
 *
 * @event createEnd [ this ]
 */

define('controls/desktop/panels/XML', [

    'controls/desktop/Panel',
    'css!controls/desktop/panels/XML.css'

], function(QUI_Panel)
{
    QUI.namespace( 'controls.desktop.panels' );

    /**
     * @class QUI.controls.desktop.panels.XML
     *
     * @param {String} xmlfile - the xml file what would be interpreted
     */
    QUI.controls.desktop.panels.XML = new Class({

        Implements : [ QUI_Panel ],
        Type       : 'QUI.controls.desktop.panels.XML',

        Binds : [
            '$onCreate',
            'loadCategory',
            'unloadCategory',
            'save'
        ],

        initialize: function(xmlfile)
        {
            this.$file   = xmlfile;
            this.$config = null;

            this.addEvent( 'onCreate', this.$onCreate );
        },

        /**
         * Internal creation
         */
        $onCreate : function()
        {
            this.Loader.show();

            QUI.Ajax.get([

                'ajax_settings_window',
                'ajax_settings_get'

            ], function(result, config, Request)
            {
                var Control    = Request.getAttribute( 'Control' ),
                    categories = result.categories || [],
                    buttons    = result.buttons || [];

                if ( typeof result.categories !== 'undefined' ) {
                    delete result.categories;
                }

                if ( typeof result.buttons !== 'undefined' ) {
                    delete result.buttons;
                }

                Control.$config = config;

                Control.getButtonBar().clear();
                Control.getCategoryBar().clear();

                // load categories
                for ( var i = 0, len = categories.length; i < len; i++ )
                {
                    var Category = new QUI.controls.buttons.Button(
                        categories[ i ]
                    );

                    Category.addEvents({
                        onActive : Control.loadCategory,
                        onNormal : Control.unloadCategory
                    });

                    Control.addCategory( Category );
                }

                // load buttons
                Control.addButton({
                    name : 'save',
                    text : 'Änderungen übernehmen',
                    textimage : URL_BIN_DIR +'16x16/save.png',
                    events : {
                        onClick : Control.save
                    }
                });

                Control.addButton({
                    name : 'reload',
                    text : 'Änderungen verwerfen',
                    textimage : URL_BIN_DIR +'16x16/reload.png',
                    events : {
                        onClick : Control.$onCreate
                    }
                });

                if ( buttons.length )
                {
                    Control.addButton(
                        new QUI.controls.buttons.Seperator()
                    );
                }

                for ( i = 0, len = buttons.length; i < len; i++ ) {
                    Control.addButton( buttons[ i ] );
                }


                Control.setAttributes( result );
                Control.refresh();

                Control.fireEvent( 'createEnd', [ Control ] );
                Control.getCategoryBar().firstChild().click();

            }, {
                file    : this.$file,
                Control : this
            });
        },

        /**
         * Request the category
         *
         * @param {QUI.controls.buttons.Button} Category
         */
        loadCategory : function(Category)
        {
            QUI.Ajax.get('ajax_settings_category', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' ),
                    Body    = Control.getBody();

                Control.getBody().set(
                    'html',

                    '<form class="qui-xml-panel">'+
                        result +
                    '</form>'
                );

                // set the form
                var i, len, parts, Elm, value;

                var elements = Body.getElement( 'form' ).elements,
                    config   = Control.$config;

                for ( i = 0, len = elements.length; i < len; i++)
                {
                    Elm   = elements[ i ];
                    value = Object.getValue( Elm.name, config );

                    if ( !value ) {
                        continue;
                    }

                    if ( Elm.type == 'checkbox' || Elm.type == 'radio' )
                    {
                        Elm.checked = ( value ).toInt();
                        continue;
                    }

                    Elm.value = value;
                }

                Control.Loader.hide();

            }, {
                file     : this.$file,
                category : Category.getAttribute( 'name' ),
                Control  : this
            });
        },

        /**
         * Unload the Category and set all settings
         *
         * @param {QUI.controls.buttons.Button} Category
         */
        unloadCategory : function(Category)
        {
            var i, j, len, Elm, name, tok,
                conf, namespace;

            var Body   = this.getBody(),
                Form   = Body.getElement( 'form' ),
                values = {};

            for ( i = 0, len = Form.elements.length; i < len; i++ )
            {
                Elm  = Form.elements[ i ];
                name = Elm.name;

                if ( Elm.type == 'radio' ||
                     Elm.type == 'checkbox' )
                {
                    if ( Elm.checked )
                    {
                        values[ name ] = 1;
                    } else
                    {
                        values[ name ] = 0;
                    }

                    continue;
                }

                values[ name ] = Elm.value;
            }


            // set the values to the $config
            for ( namespace in values )
            {
                if ( !namespace.match( '.' ) )
                {
                    this.$config[ namespace ] = values[ namespace ];
                    continue;
                }

                tok = namespace.split( '.' );

                this.$config[ tok[0] ][ tok[1] ] = values[ namespace ];
            }
        },

        /**
         * Send the configuration to the server
         */
        save : function()
        {
            this.unloadCategory( this.getActiveCategory() );
            var Save = this.getButtonBar().getElement( 'save' );

            Save.setAttribute( 'textimage', URL_BIN_DIR +'images/loader.gif' );

            QUI.Ajax.post('ajax_settings_save', function(result, Request)
            {
                Request.getAttribute( 'Save' )
                       .setAttribute( 'textimage', URL_BIN_DIR +'16x16/save.png' );
            }, {
                file    : this.$file,
                params  : JSON.encode( this.$config ),
                Control : this,
                Save    : Save
            });
        }
    });

    return QUI.controls.desktop.panels.XML;
});