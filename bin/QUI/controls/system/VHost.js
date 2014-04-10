/**
 * VHost control
 * edit and change a vhost entry
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/system/VHost', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/utils/Form',

    'controls/grid/Grid',
    'utils/Controls',
    'Ajax',
    'Locale',
    'Projects',

    'css!controls/system/VHost.css'

], function(QUI, QUIControl, QUILoader, FormUtils, Grid, ControlUtils, Ajax, Locale, Projects)
{
    "use strict";


    return new Class({

        Extends : QUIControl,
        Type    : 'controls/system/VHosts',

        Binds : [
            '$onInject'
        ],

        options : {
            host : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Elm            = null;
            this.$TemplateSelect = null;
            this.$ProjectInput   = null;
            this.$ErrorSite      = null;
            this.$LangGrid       = null;

            this.Loader = new QUILoader();

            this.addEvents({
                onInject : this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'control-system-vhost box'
            });

            this.Loader.inject( this.$Elm );

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get([
                'ajax_vhosts_get',
                'ajax_template_getlist'
            ], function(vhostData, templates)
            {
                var i, len;

                var project = vhostData.project,
                    lang    = vhostData.lang;

                vhostData.domain = self.getAttribute( 'host' );

                delete vhostData.project;
                delete vhostData.lang;

                self.$Elm.set(
                    'html',

                    '<form action="">' +
                    '<table class="data-table">' +
                    '<thead>' +
                        '<tr>' +
                            '<th colspan="2">Host Daten</th>' +
                        '</th>' +
                    '</thead>' +
                    '<tbody>' +
                        '<tr class="odd">' +
                            '<td style="width: 150px;">' +
                                '<label for="">Domain</label>' +
                            '</td>' +
                            '<td>' +
                                '<input type="text" name="domain" disabled="disabled" />' +
                            '</td>' +
                        '</tr>' +
                        '<tr class="even">' +
                            '<td>' +
                                '<label for="">Projekt</label>' +
                            '</td>' +
                            '<td>' +
                                '<input type="text" class="project" name="project" />' +
                            '</td>' +
                        '</tr>' +
                        '<tr class="odd">' +
                            '<td>' +
                                '<label for="">Template</label>' +
                            '</td>' +
                            '<td>' +
                                '<select name="template"></select>' +
                            '</td>' +
                        '</tr>' +
                        '<tr class="even">' +
                            '<td>' +
                                '<label for="">Fehler-Seite</label>' +
                            '</td>' +
                            '<td>' +
                                '<input name="error" class="project-site" />' +
                            '</td>' +
                        '</tr>' +
                    '</tbody>' +
                    '</table>' +

                    '<table class="data-table control-system-vhost-languages">' +
                    '<thead colspan="2">' +
                        '<tr>' +
                            '<th>Sprach Zuweisungen</th>' +
                        '</th>' +
                    '</thead>' +
                    '<tbody></tbody>' +
                    '</table>' +
                    '</form>'
                );

                self.$TemplateSelect = self.$Elm.getElement( '[name="template"]' );
                self.$ProjectInput   = self.$Elm.getElement( '[name="project"]' );
                self.$ErrorSite      = self.$Elm.getElement( '[name="error"]' );

                self.$ProjectInput.value = JSON.encode([{
                    project : project,
                    lang    : lang
                }]);

                // create controls
                ControlUtils.parse( self.$Elm );

                FormUtils.setDataToForm(
                    vhostData,
                    self.$Elm.getElement( 'form' )
                );

                // create template select
                for ( i = 0, len = templates.length; i < len; i++ )
                {
                    new Element('option', {
                        value : templates[ i ].name,
                        html  : templates[ i ].name
                    }).inject( self.$TemplateSelect );
                }

                // get projects langs
                if ( project )
                {
                    self.$loadProjectLangs(function() {
                        self.Loader.show();
                    });

                    return;
                }

                self.Loader.show();

            }, {
                vhost : this.getAttribute( 'host' )
            });
        },

        /**
         * Save the settings to the vhost
         *
         * @param {Function} callback - [optional] callback function after saving
         */
        save : function(callback)
        {
            var data, projectData, errorData;

            var self = this;

            this.Loader.show();

            projectData = {
                project : '',
                lang    : ''
            };

            errorData = '';

            // project data
            if ( self.$ProjectInput.value !== '' )
            {
                projectData = JSON.decode( self.$ProjectInput.value );

                if ( projectData[ 0 ] ) {
                    projectData = projectData[ 0 ];
                }
            }

            // error site
            //self.$ErrorSite

            // complete data
            data = {
                project   : projectData.project,
                lang      : projectData.lang,
                template  : self.$TemplateSelect.value,
                error     : '',
                httpshost : ''
            };

            Ajax.post('ajax_vhosts_save', function()
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            }, {
                vhost : this.getAttribute( 'host' ),
                data  : JSON.encode( data )
            });
        },

        /**
         * load the project langs
         *
         * @param {Function} callback - [optional] callback on end
         */
        $loadProjectLangs : function(callback)
        {
            var self = this,
                data = JSON.decode( this.$ProjectInput.value );

            console.log( this.$ProjectInput.value );

            if ( typeof data[ 0 ] === 'undefined' || !data[ 0 ].project )
            {
                var TBody = self.$Elm.getElement(
                    '.control-system-vhost-languages tbody'
                );

                if ( TBody ) {
                    TBody.set( 'html', '' );
                }

                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                return;
            }


            this.$ProjectInput.addEvents({
                change : function() {
                    self.$loadProjectLangs();
                }
            });

            // get the project langs
            Projects.get( data[ 0 ].project ).getConfig(function(config)
            {
                var langs = config.langs.split( ',' ),

                    TBody = self.$Elm.getElement(
                        '.control-system-vhost-languages tbody'
                    );

                if ( !TBody ) {
                    return;
                }


                TBody.set( 'html', '' );

                var cssClass = 'even';

                for ( var i = 0, len = langs.length; i < len; i++ )
                {
                    if ( data[ 0 ].lang == langs[ i ] ) {
                        continue;
                    }

                    cssClass = cssClass == 'odd' ? 'even' : 'odd';

                    new Element('tr', {
                        'class' : cssClass,
                        html    : '<td style="width: 150px;">'+ langs[ i ] +'</td>' +
                                  '<td><input type="text" value="" /></td>'
                    }).inject( TBody );
                }

                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            });
        }
    });
});