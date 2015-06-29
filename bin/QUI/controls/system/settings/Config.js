
/**
 * quiqqer config
 *
 * @module controls/system/settings/Config
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/windows/Confirm
 * @require Ajax
 */

define('controls/system/settings/Config', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Ajax'

], function(QUI, QUIControl, QUIConfirm, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/system/settings/Config',

        Binds : [
            '$onImport'
        ],

        initialize : function(Panel)
        {
            this.$Panel = Panel;

            this.addEvents({
                onImport : this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport : function()
        {
            var self  = this,
                Panel = this.$Panel;

            Ajax.get('ajax_system_getQuiqqerVersions', function(versions)
            {
                var Select = Panel.getContent().getElement( '[name="globals.quiqqer_version"]' );

                if ( !Select ) {
                    return;
                }

                for ( var i = 0, len = versions.length; i < len; i++ )
                {
                    new Element('option', {
                        value : versions[ i ],
                        html  : versions[ i ]
                    }).inject( Select );
                }

                Select.value = QUIQQER_VERSION;

                Select.addEvent('change', function()
                {
                    if ( this.value == 'dev-dev' ) {
                        self.setDevelopment();
                    }
                });

                Panel.Loader.hide();
            });
        },

        /**
         * Set the system to development mode
         */
        setDevelopment : function()
        {
            var self = this;

            new QUIConfirm({
                title     : 'Development Modus',
                maxWidth  : 600,
                maxHeight : 400,
                autoclose : false,
                events    :
                {
                    onOpen : function(Win)
                    {
                        var Content = Win.getContent();

                        Win.Loader.show();

                        Content.set(
                            'html',

                            '<p>Sie möchten QUIQQER in den Entwicklungsmodus stellen.</p>'+
                            '<p>Wir empfehlen folgende Pakete auch in den Entwicklungsmodus zu stellen.</p>'+
                            '<p>Bitte wählen Sie aus welche Pakete in Entwickler Versionen verwendet werden sollen.</p>'+
                            '<br />'
                        );

                        Ajax.get('ajax_system_packages_list', function(result)
                        {
                            var id = Win.getId();

                            result.push(
                                { name : 'quiqqer/qui' },
                                { name : 'quiqqer/quiqqer' },
                                { name : 'quiqqer/qui-php' },
                                { name : 'quiqqer/utils' }
                            );

                            for ( var i = 0, len = result.length; i < len; i++ )
                            {
                                new Element('div', {
                                    html : '<input type="checkbox" value="'+ result[ i ].name +'" id="w'+ id +'_'+ i +'" />' +
                                           '<label for="w'+ id +'_'+ i +'">'+ result[ i ].name +'</label>'
                                }).inject( Content );
                            }

                            Content.getElements( '[type="checkbox"]' ).set( 'checked', true );

                            Win.Loader.hide();

                        }, {
                            params : JSON.encode({
                                type : 'quiqqer-library'
                            })
                        });
                    },

                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        var packages = Win.getContent()
                                          .getElements('[type="checkbox"]:checked')
                                          .map(function(Elm) {
                                              return Elm.get( 'value' );
                                       });

                        if ( !packages.length )
                        {
                            Win.close();
                            return;
                        }

                        Ajax.get('ajax_system_packages_setVersion', function(result)
                        {
                            Win.close();
                        }, {
                            packages : JSON.encode( packages ),
                            version  : 'dev-dev'
                        });
                    },

                    onCancel : function()
                    {
                        self.$Panel
                            .getContent()
                            .getElements( '[name="globals.quiqqer_version"]' )
                            .set( 'value', QUIQQER_VERSION );
                    }
                }
            }).open();
        }
    });
});
