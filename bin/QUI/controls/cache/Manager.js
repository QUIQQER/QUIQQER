/**
 * system cache manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/controls/desktop/Panel
 * @module controls/system/Cache
 *
 * @depricated???
 */

define('controls/cache/Manager', [

    'qui/QUI',
    'qui/controls/desktop/Panel',

    'css!controls/cache/Manager.css'

], function(QUI, QUIPanel)
{
    "use strict";

    /**
     * @class controls/cache/Manager
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/cache/Manager',

        Binds : [
            '$onCreate'
        ],

        initialize : function(options)
        {
            this.$Clear = null;
            this.$Purge = null;
//
//            this.$caches = [
//                [ 'plugins', 'Plugin Cache leeren' ],
//                [ 'compile', 'Template Compile Cache leeren' ]
//            ];

            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * Load the cache manager
         *
         * @method controls/cache/Manager#load
         */
        $onCreate : function()
        {
            this.Loader.show();

            var i, n, len;

            var Body   = this.getContent(),
                caches = this.$caches,
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

            Body.set( 'html', table );

            this.addButton({
                text      : 'Cache leeren ausführen',
                textimage : URL_BIN_DIR +'16x16/cache.png',
                events    : {
                    onClick : this.clear
                }
            });

            this.addButton({
                text      : 'Cache säubern',
                textimage : URL_BIN_DIR +'16x16/cache.png',
                events    : {
                    onClick : this.purge
                }
            });
//
//            this.$Clear = new QUI.controls.buttons.Button({
//                text      : 'Cache leeren ausführen',
//                textimage : URL_BIN_DIR +'16x16/cache.png',
//                events    :
//                {
//                    onClick : function()
//                    {
//                        this.clear();
//                    }.bind( this )
//                }
//            }).inject( this.$Body.getElement('.clear-cache-btn'));
//
//            this.$Clear.disable();

//            this.$Purge = new QUI.controls.buttons.Button({
//                text      : 'Cache säubern',
//                textimage : URL_BIN_DIR +'16x16/cache.png',
//                events    :
//                {
//                    onClick : function()
//                    {
//                        this.purge();
//                    }.bind( this )
//                }
//            }).inject( this.$Body.getElement('.clear-cache-btn'));

            // this.$Purge.disable();


            // events
//            var checkboxs = this.$Body.getElements('input[type="checkbox"]');
//
//            for ( i = 0, len = checkboxs.length; i < len; i++ ) {
//                checkboxs[ i ].addEvent('change', this.$onChange.bind( this ));
//            }

            this.Loader.hide();
        },


        /**
         * The purge function removes stale data from the cache backends while leaving current data intact.
         * Depending on the size of the cache and the specific drivers in use this can take some time,
         * so it is best called as part of a separate maintenance task or as part of a cron job.
         */
        purge : function(oncomplete)
        {
            Ajax.post('ajax_system_cache_purge', function(result, Request)
            {
                if ( typeof oncomplete !== 'undefined' ) {
                    oncomplete( result, Request );
                }
            });
        },

        /**
         * Clear the specific cache
         */
        clear : function(params, oncomplete)
        {
            Ajax.post('ajax_system_cache_clear', function(result, Request)
            {
                if ( typeof oncomplete !== 'undefined' ) {
                    oncomplete( result, Request );
                }
            }, {
                params : JSON.encode( params )
            });
        }

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

                QUI.getMessageHandler(function(MH)
                {
                    MH.addInformation(
                        'Der Cache wurde erfolgreich geleert'
                    );
                });

            }.bind( this ));
        },

        /**
         * Exceute the cache purging
         */
        purge : function()
        {
            var self = this;

            this.$Purge.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

            this.$Control.purge(function()
            {
                this.$Purge.setAttribute('textimage', URL_BIN_DIR +'16x16/cache.png');

                QUI.getMessageHandler(function(MH)
                {
                    MH.addInformation(
                        'Der Cache wurde erfolgreich gesäubert'
                    );
                });

            }.bind( this ));
        },

        /**
         * Checkbox change event, if no checkbox is selected, the clear button is disable
         */
        $onChange : function()
        {
            var checkboxs = this.$Body.getElements( 'input[type="checkbox"]' );

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
});