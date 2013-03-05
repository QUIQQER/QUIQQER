/**
 * system cache manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Settings
 *
 * @module controls/system/Cache
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system
 */

define('controls/system/Cache', [

    'controls/Control',
    'classes/system/Cache'

], function(Control)
{
    QUI.namespace('controls.system');
    QUI.css( QUI.config('dir') +'controls/system/Cache.css' );

    /**
     * @class QUI.controls.system.Cache
     */
    QUI.controls.system.Cache = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.Cache',

        initialize : function(Control, Panel)
        {
            this.$Control = Control;
            this.$Panel   = Panel;
            this.$Clear   = null;
            this.$Purge   = null;

            this.$caches = [
                [ 'plugins', 'Plugin Cache leeren' ],
                [ 'compile', 'Template Compile Cache leeren' ]
            ];

            this.load();
        },

        /**
         * Load the cache manager
         *
         * @method QUI.controls.system.Cache#load
         */
        load : function()
        {
            this.$Panel.Loader.show();
            this.$Body = this.$Panel.getBody();

            var i, n, len;

            var caches = this.$caches,

                table  = '<table class="data-table">' +
                        '<thead>' +
                            '<tr>' +
                                '<th>Cache</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>';

            for ( i = 0, len = caches.length; i < len; i++ )
            {
                n = 'cache'+ this.getId() +'-'+ caches[ i ][ 0 ];

                table = table +
                    '<tr class="'+ (i % 2 ? 'event' : 'odd') +'">' +
                        '<td class="cache-item">' +
                            '<input type="checkbox" ' +
                                'id="'+ n +'" ' +
                                'name="'+ n +'" ' +
                                'value="'+ caches[ i ][ 0 ] +'" ' +
                            '/>' +
                            '<label for="'+ n +'">'+ caches[ i ][ 1 ] +'</label>' +
                        '</td>' +
                    '</tr>';
            }


            table = table + '</tbody></table>';
            table = table + '<div class="clear-cache-btn"></div>';

            this.$Body.set('html', table);


            this.$Clear = new QUI.controls.buttons.Button({
                text      : 'Cache leeren ausführen',
                textimage : URL_BIN_DIR +'16x16/cache.png',
                events    :
                {
                    onClick : function()
                    {
                        this.clear();
                    }.bind( this )
                }
            }).inject( this.$Body.getElement('.clear-cache-btn'));

            this.$Clear.disable();

            this.$Purge = new QUI.controls.buttons.Button({
                text      : 'Cache säubern',
                textimage : URL_BIN_DIR +'16x16/cache.png',
                events    :
                {
                    onClick : function()
                    {
                        this.purge();
                    }.bind( this )
                }
            }).inject( this.$Body.getElement('.clear-cache-btn'));

            // this.$Purge.disable();


            // events
            var checkboxs = this.$Body.getElements('input[type="checkbox"]');

            for ( i = 0, len = checkboxs.length; i < len; i++ ) {
                checkboxs[ i ].addEvent('change', this.$onChange.bind( this ));
            }

            this.$Panel.Loader.hide();
        },

        /**
         * Execute the cache clearing
         */
        clear : function()
        {
            var i, n, len, Elm;

            var params = {},
                caches = this.$caches,
                Body   = this.$Body;

            for ( i = 0, len = caches.length; i < len; i++ )
            {
                n   = caches[ i ][ 0 ];
                Elm = Body.getElement('input[value="'+ n +'"]');

                if ( Elm && Elm.checked )
                {
                    params[ n ] = true;
                } else
                {
                    params[ n ] = false;
                }
            }


            this.$Clear.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

            this.$Control.clear(params, function()
            {
                this.$Clear.setAttribute('textimage', URL_BIN_DIR +'16x16/cache.png');

                QUI.MH.addInformation(
                    'Der Cache wurde erfolgreich geleert'
                );

            }.bind( this ));
        },

        /**
         * Exceute the cache purging
         */
        purge : function()
        {
            this.$Purge.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

            this.$Control.purge(function()
            {
                this.$Purge.setAttribute('textimage', URL_BIN_DIR +'16x16/cache.png');

                QUI.MH.addInformation(
                    'Der Cache wurde erfolgreich gesäubert'
                );

            }.bind( this ));
        },

        /**
         * Checkbox change event, if no checkbox is selected, the clear button is disable
         */
        $onChange : function()
        {
            var checkboxs = this.$Body.getElements('input[type="checkbox"]');

            for ( var i = 0, len = checkboxs.length; i < len; i++ )
            {
                if ( checkboxs[ i ].checked )
                {
                    this.$Clear.enable();
                    this.$Purge.disable();
                    return;
                }
            }

            this.$Clear.disable();
            this.$Purge.enable();
        }
    });

    return QUI.controls.system.Cache;
});