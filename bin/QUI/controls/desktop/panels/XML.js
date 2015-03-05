
/**
 * a panel based on a xml file
 * eg settings.xml
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/desktop/panels/XML
 */

define('controls/desktop/panels/XML', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/utils/Object',
    'Ajax',
    'Locale',
    'utils/Controls',

    'css!controls/desktop/panels/XML.css'

], function(QUI, QUIPanel, QUIButton, QUISeperator, QUIObjectUtils, Ajax, Locale, ControlUtils)
{
    "use strict";

    /**
     * @class controls/desktop/panels/XML
     *
     * @param {String} xmlfile - the xml file what would be interpreted
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/desktop/panels/XML',

        Binds : [
            '$onCreate',
            '$onCategoryActive',
            'loadCategory',
            'unloadCategory',
            'save'
        ],

        initialize: function(xmlfile, options)
        {
            this.$file    = xmlfile;
            this.$config  = null;
            this.$Control = null;

            this.addEvent( 'onCreate', this.$onCreate );

            this.parent( options );
        },

        /**
         * Return the data for the workspace
         *
         * @method qui/controls/desktop/Tasks#serialize
         * @return {Object}
         */
        serialize : function()
        {
            return {
                attributes : this.getAttributes(),
                type       : this.getType(),
                file       : this.$file,
                config     : this.$config
            };
        },

        /**
         * Import the saved data
         *
         * @method qui/controls/desktop/Tasks#unserialize
         * @param {Object} data
         */
        unserialize : function(data)
        {
            this.setAttributes( data.attributes );

            this.$file    = data.file;
            this.$config  = data.config;

            if ( !this.$Elm )
            {
                this.$serialize = data;
                return this;
            }
        },

        /**
         * Internal creation
         */
        $onCreate : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get([

                'ajax_settings_window',
                'ajax_settings_get'

            ], function(result, config)
            {
                var categories = result.categories || [],
                    buttons    = result.buttons || [];

                if ( typeof result.categories !== 'undefined' ) {
                    delete result.categories;
                }

                if ( typeof result.buttons !== 'undefined' ) {
                    delete result.buttons;
                }

                self.$config = config;

                self.getButtonBar().clear();
                self.getCategoryBar().clear();

                // load categories
                for ( var i = 0, len = categories.length; i < len; i++ )
                {
                    var Category = new QUIButton(
                        categories[ i ]
                    );

                    Category.addEvents({
                        onActive : self.$onCategoryActive
                    });

                    self.addCategory( Category );
                }

                // load buttons
                self.addButton({
                    name      : 'save',
                    text      : Locale.get( 'quiqqer/system', 'desktop.panels.xml.btn.save' ),
                    textimage : 'icon-save',
                    events : {
                        onClick : self.save
                    }
                });

                self.addButton({
                    name      : 'reload',
                    text      : Locale.get( 'quiqqer/system', 'desktop.panels.xml.btn.cancel' ),
                    textimage : 'icon-ban-circle',
                    events : {
                        onClick : self.$onCreate
                    }
                });

                if ( buttons.length )
                {
                    self.addButton(
                        new QUISeperator()
                    );
                }

                for ( i = 0, len = buttons.length; i < len; i++ ) {
                    self.addButton( buttons[ i ] );
                }


                self.setAttributes( result );
                self.refresh();

                self.fireEvent( 'createEnd', [ self ] );
                self.getCategoryBar().firstChild().click();

            }, {
                file : this.$file
            });
        },

        /**
         * Request the category
         *
         * @param {Object} Category - qui/controls/buttons/Button
         */
        loadCategory : function(Category)
        {
            var self = this;

            Ajax.get('ajax_settings_category', function(result)
            {
                var Body = self.getBody();

                if ( !result ) {
                    result = '';
                }

                Body.set( 'html', '<form class="qui-xml-panel">'+ result + '</form>' );

                // set the form
                var i, len, Elm, value;

                var Form     = Body.getElement( 'form'),
                    elements = Form.elements,
                    config   = self.$config;

                for ( i = 0, len = elements.length; i < len; i++)
                {
                    Elm   = elements[ i ];
                    value = QUIObjectUtils.getValue( Elm.name, config );

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

                ControlUtils.parse( Body );
                QUI.parse( Body );


                // require?
                if ( Category.getAttribute( 'require' ) )
                {
                    require([ Category.getAttribute( 'require' ) ], function(R)
                    {
                        var type = typeOf( R );

                        if ( type === 'function' )
                        {
                            R( self );

                        } else if ( type === 'class' )
                        {
                            self.$Control = new R( self );

                            if ( self.getContent().get( 'html' ) === '' )
                            {
                                self.$Control.inject( Form );

                            } else
                            {
                                self.$Control.imports( Form );
                            }

                        } else
                        {
                            self.Loader.show();
                        }

                    }, function(err)
                    {
                        QUI.getMessageHandler(function(MH)
                        {
                            MH.addAttention(
                                'Some error occured. Control could not be loaded: ' +
                                Category.getAttribute( 'require' )
                            );

                            console.error( err );
                        });

                        self.Loader.hide();
                    });

                    return;
                }

                self.Loader.hide();

            }, {
                file     : this.$file,
                category : Category.getAttribute( 'name' )
            });
        },

        /**
         * Unload the Category and set all settings
         */
        unloadCategory : function()
        {
            var i, len, Elm, name, tok, namespace;

            var Body   = this.getBody(),
                Form   = Body.getElement( 'form' ),
                values = {};

            if ( Form )
            {
                for ( i = 0, len = Form.elements.length; i < len; i++ )
                {
                    Elm = Form.elements[i];
                    name = Elm.name;

                    if ( Elm.type == 'radio' || Elm.type == 'checkbox' )
                    {
                        if ( Elm.checked )
                        {
                            values[name] = 1;
                        } else
                        {
                            values[name] = 0;
                        }

                        continue;
                    }

                    values[name] = Elm.value;
                }
            }


            // set the values to the $config
            for ( namespace in values )
            {
                if ( !values.hasOwnProperty( namespace ) ) {
                    continue;
                }

                if ( !namespace.match( '.' ) )
                {
                    this.$config[ namespace ] = values[ namespace ];
                    continue;
                }

                tok = namespace.split( '.' );

                if ( typeof tok[0] !== 'undefined' &&
                     typeof tok[1] !== 'undefined' &&
                     typeof this.$config[ tok[0] ] !== 'undefined' &&
                     typeof this.$config[ tok[0] ][ tok[1] ] !== 'undefined' )
                {
                    this.$config[ tok[0] ][ tok[1] ] = values[ namespace ];
                }
            }

            if ( this.$Control )
            {
                this.$Control.destroy();
                this.$Control = null;
            }
        },

        /**
         * event : click on category button
         *
         * @param {Object} Category - qui/controls/buttons/Button
         */
        $onCategoryActive : function(Category)
        {
            this.unloadCategory();
            this.loadCategory( Category );
        },

        /**
         * Send the configuration to the server
         */
        save : function()
        {
            this.unloadCategory( this.getActiveCategory() );

            var Save = this.getButtonBar().getElement( 'save' );

            Save.setAttribute( 'textimage', 'icon-refresh icon-rotate' );

            Ajax.post('ajax_settings_save', function()
            {
                Save.setAttribute( 'textimage', 'icon-save' );
            }, {
                file   : this.$file,
                params : JSON.encode( this.$config )
            });
        }
    });
});