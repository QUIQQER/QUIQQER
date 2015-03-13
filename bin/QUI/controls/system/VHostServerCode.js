/**
 * VHost control
 * edit and change a vhost entry
 *
 * @module controls/system/VHostServerCode
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require qui/controls/buttons/Button
 * @require qui/utils/Form
 * @require controls/projects/Popup
 * @require utils/Controls
 * @require qui/utils/String
 * @require Ajax
 * @require Locale
 * @require Projects
 * @require css!controls/system/VHostServerCode.css
 */

define('controls/system/VHostServerCode', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',
    'qui/utils/Form',

    'controls/projects/Popup',
    'utils/Controls',
    'qui/utils/String',
    'Ajax',
    'Locale',

    'css!controls/system/VHostServerCode.css'

], function(
    QUI, QUIControl, QUILoader, QUIButton, FormUtils,
    ProjectPopup, ControlUtils, StringUtils, Ajax, Locale
) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/system/VHosts',

        Binds : [
            '$onInject'
        ],

        options : {
            host : false,
            data : {}
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Elm = null;

            this.Loader = new QUILoader();

            this.addEvents({
                onInject : this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'control-system-vhostServerCode box'
            });

            this.Loader.inject( this.$Elm );

            this.$InputProject = null;
            this.$InputLang    = null;
            this.$InputId      = null;

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get(['ajax_vhosts_get'], function(vhostData)
            {
                vhostData.host = self.getAttribute( 'host' );


                self.$Elm.set(
                    'html',

                    '<form action="">' +
                    '<table class="data-table">' +
                    '<thead>' +
                        '<tr>' +
                            '<th colspan="2">' +
                                Locale.get( lg, 'system.vhost.table.hostdata' ) +
                            '</th>' +
                        '</th>' +
                    '</thead>' +
                    '<tbody>' +
                        '<tr class="odd">' +
                            '<td style="width: 150px;">' +
                                '<label for="">' +
                                    Locale.get( lg, 'system.vhost.label.domain' ) +
                                '</label>' +
                            '</td>' +
                            '<td>' +
                                '<input type="text" name="host" disabled="disabled" />' +
                            '</td>' +
                        '</tr>' +

                        '<tr class="even">' +
                            '<td style="width: 150px;">' +
                                '<label for="">' +
                                    Locale.get( lg, 'project' ) +
                                '</label>' +
                            '</td>' +
                            '<td>' +
                                '<input type="text" name="project" />' +
                            '</td>' +
                        '</tr>' +

                        '<tr class="odd">' +
                            '<td style="width: 150px;">' +
                                '<label for="">'+
                                    Locale.get( lg, 'language' ) +
                                '</label>' +
                            '</td>' +
                            '<td>' +
                                '<input type="text" name="lang" />' +
                            '</td>' +
                        '</tr>' +

                        '<tr class="even">' +
                            '<td style="width: 150px;">' +
                                '<label for="">' +
                                    Locale.get( lg, 'site_id' ) +
                                '</label>' +
                            '</td>' +
                            '<td>' +
                                '<input type="text" name="id" />' +
                            '</td>' +
                        '</tr>' +

                        '<tr class="odd">' +
                            '<td style="width: 150px;"></td>' +
                            '<td class="control-system-vhostServerCode-siteBtn"></td>' +
                        '</tr>' +

                    '</tbody>' +
                    '</table>' +
                    '</form>'
                );

                self.$InputProject = self.$Elm.getElement( '[name="project"]' );
                self.$InputLang    = self.$Elm.getElement( '[name="lang"]' );
                self.$InputId      = self.$Elm.getElement( '[name="id"]' );

                // create controls
                ControlUtils.parse( self.$Elm );

                FormUtils.setDataToForm(
                    vhostData,
                    self.$Elm.getElement( 'form' )
                );

                // site button
                var SiteButton = new QUIButton({
                    textimage : 'icon-file-alt',
                    text : 'Seite auswählen',
                    events :
                    {
                        onClick : function()
                        {
                            new ProjectPopup({
                                events :
                                {
                                    onSubmit : function(Popup, params)
                                    {
                                        self.$InputProject.value = params.project;
                                        self.$InputLang.value    = params.lang;
                                        self.$InputId.value      = params.ids[ 0 ];
                                    }
                                }
                            }).open();
                        }
                    }
                }).inject(
                    self.$Elm.getElement( '.control-system-vhostServerCode-siteBtn' )
                );

                self.$InputProject.addEvent('focus', function() {
                    SiteButton.click();
                });

                self.$InputLang.addEvent('focus', function() {
                    SiteButton.click();
                });

                self.$InputId.addEvent('focus', function() {
                    SiteButton.click();
                });


                self.Loader.show();

            }, {
                vhost : this.getAttribute( 'host' )
            });
        },

        /**
         * Save the settings to the vhost
         *
         * @param {Function} [callback] - (optional), callback function after saving
         */
        save : function(callback)
        {
            var self = this;

            this.Loader.show();

            Ajax.post('ajax_vhosts_save', function()
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            }, {
                vhost : this.getAttribute( 'host' ),
                data  : JSON.encode({
                    project : self.$InputProject.value,
                    lang    : self.$InputLang.value,
                    id      : self.$InputId.value
                })
            });
        }
    });
});