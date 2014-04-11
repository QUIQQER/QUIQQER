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
    'qui/utils/String',
    'Ajax',
    'Locale',
    'Projects',

    'css!controls/system/VHost.css'

], function(QUI, QUIControl, QUILoader, FormUtils, Grid, ControlUtils, StringUtils, Ajax, Locale, Projects)
{
    "use strict";


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

            this.$Elm            = null;
            this.$TemplateSelect = null;
            this.$ProjectInput   = null;
            this.$ErrorSite      = null;
            this.$HttpsHost      = null;

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
                    lang    = vhostData.lang,
                    error   = vhostData.error || '';

                vhostData.domain = self.getAttribute( 'host' );

                delete vhostData.project;
                delete vhostData.lang;
                delete vhostData.error;

                self.setAttribute( 'data', vhostData );

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
                        '<tr class="odd">' +
                            '<td>' +
                                '<label for="">HTTPS-Host</label>' +
                            '</td>' +
                            '<td>' +
                                '<input name="httpshost" />' +
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
                self.$HttpsHost      = self.$Elm.getElement( '[name="httpshost"]' );

                // project data
                self.$ProjectInput.value = JSON.encode([{
                    project : project,
                    lang    : lang
                }]);

                // error site
                if ( error != '' )
                {
                    error = error.split(',');

                    self.$ErrorSite.value = 'index.php?' +Object.toQueryString({
                        project : error[ 0 ],
                        lang    : error[ 1 ],
                        id      : error[ 2 ],
                    });
                }

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
            var i, len, data, langFields, siteParts;

            var self        = this,
                errorSite   = '',

                projectData = {
                    project : '',
                    lang    : ''
                };

            this.Loader.show();


            // project data
            if ( self.$ProjectInput.value !== '' )
            {
                projectData = JSON.decode( self.$ProjectInput.value );

                if ( projectData[ 0 ] ) {
                    projectData = projectData[ 0 ];
                }
            }

            // error site
            siteParts = StringUtils.getUrlParams( self.$ErrorSite.value );

            if ( siteParts.project ) {
                errorSite = siteParts.project +','+ siteParts.lang +','+ siteParts.id;
            }

            // complete data
            data = {
                project   : projectData.project,
                lang      : projectData.lang,
                template  : this.$TemplateSelect.value,
                error     : errorSite,
                httpshost : this.$HttpsHost.value
            };

            // lang hosts
            langFields = this.$Elm.getElements(
                '.control-system-vhost-languages tbody input'
            );

            for ( i = 0, len = langFields.length; i < len; i++ )
            {
                if ( langFields[ i ].value != '' ) {
                    data[ langFields[ i ].name ] = langFields[ i ].value;
                }
            }

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

                // create the language data
                var i, len, lang, host;

                var cssClass  = 'even',
                    vhostData = self.getAttribute( 'data' );

                for ( i = 0, len = langs.length; i < len; i++ )
                {
                    if ( data[ 0 ].lang == langs[ i ] ) {
                        continue;
                    }

                    cssClass = cssClass == 'odd' ? 'even' : 'odd';
                    lang     = langs[ i ];
                    host     = '';

                    if ( vhostData[ lang ] )  {
                        host = vhostData[ lang ];
                    }

                    new Element('tr', {
                        'class' : cssClass,
                        html    : '<td style="width: 150px;">'+ lang +'</td>' +
                                  '<td>' +
                                      '<input type="text" value="'+ host +'" name="'+ lang +'" />' +
                                  '</td>'
                    }).inject( TBody );
                }

                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            });
        }
    });
});