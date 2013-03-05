/**
 * System Update
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires classes/system/Update
 *
 * @module controls/system/Update
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system
 */

define('controls/system/Update', [

    'controls/Control',
    'classes/system/Update'

], function(Control)
{
    QUI.namespace('controls.system');

    /**
     * @class QUI.controls.system.Manager
     */
    QUI.controls.system.Update = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.Update',

        initialize : function(Control, Panel)
        {
            this.$Control = Control;
            this.$Panel   = Panel;

            this.$downloads = {};

            this.load();
        },

        /**
         * Load the Systemupdater
         *
         * @param {QUI.controls.Control} Parent
         */
        load : function()
        {
            this.$Panel.Loader.show();

            this.$Control.loadTemplate(function(result, Request)
            {
                var Body = this.$Panel.getBody();
                    Body.set('html', result);

                var Buttons = Body.getElement('.admin-update-cms-btns');

                /**
                 * load buttons
                 */

                if ( !Buttons )
                {
                    this.$Panel.Loader.hide();
                    return;
                }

                // Setup Button
                new QUI.controls.buttons.Button({
                    name      : 'setup',
                    text      : 'System Setup ausführen',
                    textimage : URL_BIN_DIR +'16x16/setup.png',
                    Control   : this,
                    events    :
                    {
                        onClick : function(Btn)
                        {
                            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

                            Btn.getAttribute('Control').$Control.setup(function(result, Ajax)
                            {
                                if ( this ) {
                                    this.setAttribute('textimage', URL_BIN_DIR +'16x16/setup.png');
                                }

                                QUI.MH.addInformation(
                                    'Setup wurde erfolgreich durchgeführt'
                                );

                            }.bind( Btn ));
                        }
                    }
                }).inject( Buttons );

                // Optimierungs Button
                new QUI.controls.buttons.Button({
                    name      : 'setup',
                    text      : 'Datenbank optimieren',
                    textimage : URL_BIN_DIR +'16x16/database.png',
                    Control   : this,
                    events    :
                    {
                        onClick : function(Btn)
                        {
                            Btn.setAttribute('textimage', URL_BIN_DIR +'images/loader.gif');

                            Btn.getAttribute('Control').$Control.optimize(function(result, Ajax)
                            {
                                if (this) {
                                    this.setAttribute('textimage', URL_BIN_DIR +'16x16/database.png');
                                }

                                QUI.MH.addInformation(
                                    'Datenbank Optimierung wurde erfolgreich durchgeführt'
                                );
                            }.bind( Btn ));
                        }
                    }
                }).inject( Buttons );

                // available quiqqer versions
                this.$displayAvailableVersions();

                this.$Panel.Loader.hide();
            }.bind( this ));
        },

        /**
         * Loads the available quiqqer versions
         */
        $displayAvailableVersions : function()
        {
            // load the cms version
            this.$Control.getVersion(false, function(result, Request)
            {
                var Body = this.$Panel.getBody(),
                    List = Body.getElement('.admin-update-cms-list');

                if ( !List ) {
                    return;
                }

                var i, len, TRs, Elm,
                    Download, Install, installed,
                    func_download_click, func_update_click, func_update_create;

                var TBody = List.getElement('tbody');

                /**
                 * Update Liste
                 */
                List.getElement('tbody tr').destroy();

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    Elm = new Element('tr', {
                        'data-name'       : result[i].version,
                        'data-installed'  : result[i].installed ? 1 : 0,
                        'data-downloaded' : result[i].downloaded ? 1 : 0,
                        'class' : (i%2) ? '' : 'odd',
                        html    : '<td>'+ result[i].version +'</td>' +
                                  '<td>'+ (result[i].installed ? 'installiert' : '') +'</td>' +
                                  '<td></td>'
                    });

                    if ( result[i].installed ) {
                        Elm.addClass('installed');
                    }

                    Elm.inject( TBody );
                }

                // click events
                func_download_click = function(Btn)
                {
                    Btn.getAttribute('Install').setDisable();

                    QUI.extras.system.Update.download(
                        Btn.getAttribute('Row'), function()
                        {
                            this.getAttribute('Install').setEnable();
                        }.bind( Btn )
                    );
                };

                func_update_create = function(Btn)
                {
                    var Row = Btn.getAttribute('Row');

                    if ( Row.get('data-downloaded') != 1 )
                    {
                        Btn.setDisable();
                        return;
                    }

                    if ( Row.cells[1].get('html') === '' ) {
                        Row.cells[1].set('html', 'herunter geladen');
                    }
                };

                func_update_click = function(Btn)
                {
                    QUI.extras.system.Update.install(
                        Btn.getAttribute('Row'),
                        function(result, Ajax) {

                        }
                    );
                };


                TRs = TBody.getElements('tr');

                for ( i = 0, len = TRs.length; i < len; i++ )
                {
                    Download = new QUI.controls.buttons.Button({
                        textimage : URL_BIN_DIR +'16x16/down.png',
                        text      : 'download',
                        alt       : 'Update herrunter laden',
                        title     : 'Update herrunter laden',
                        Row       : TRs[i],
                        events    : {
                            onClick : func_download_click
                        }
                    });

                    Install = new QUI.controls.buttons.Button({
                        textimage : URL_BIN_DIR +'16x16/install.png',
                        text      : 'install',
                        alt       : 'Update installieren',
                        title     : 'Update installieren',
                        Row       : TRs[i],
                        events    :
                        {
                            onClick  : func_update_click,
                            onCreate : func_update_create
                        }
                    });

                    Download.setAttribute('Install', Install);

                    Download.inject( TRs[i].cells[2] );
                    Install.inject( TRs[i].cells[2] );
                }

            }.bind( this ));
        }
    });

    return QUI.controls.system.Update;
});