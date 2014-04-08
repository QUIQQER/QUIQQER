
/**
 * VHost Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/system/VHosts', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/utils/Form',
    'controls/grid/Grid',
    'utils/Controls',
    'Ajax',

    'css!controls/system/VHosts.css'

], function(QUI, QUIPanel, FormUtils, Grid, ControlUtils, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/system/VHosts',

        Binds : [
            '$onCreate',
            '$onResize',

            '$gridClick',
            '$gridDblClick',
            '$gridBlur'
        ],

        options : {
            title : 'Virtual Hosts Einstellungen',
            icon  : 'icon-external-link'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });
        },

        /**
         * event : on create
         */
        $onCreate : function()
        {
            var self = this;

            // buttons
            this.addButton({
                text : 'Virtuellen Host hinzufügen',
                textimage : 'icon-plus',
                events : {
                    onClick : function() {
                        self.openAddVhost();
                    }
                }
            });

            this.addButton({
                type : 'seperator'
            });

            this.addButton({
                name : 'editVhost',
                text : 'Markierten Host editieren',
                textimage : 'icon-edit',
                disabled  : true,
                events : {
                    onClick : function() {
                        self.openEditVhost();
                    }
                }
            });

            this.addButton({
                name : 'delVhost',
                text : 'Markierte Hosts löschen',
                textimage : 'icon-trash',
                disabled  : true,
                events : {
                    onClick : function() {
                        self.openDelVhost();
                    }
                }
            });

            // Grid
            var Container = new Element('div').inject(
                this.getContent()
            );

            this.$Grid = new Grid( Container, {
                columnModel : [{
                    header    : 'Domain',
                    dataIndex : 'host',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Projekt',
                    dataIndex : 'project',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Projektsprache',
                    dataIndex : 'lang',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Template',
                    dataIndex : 'template',
                    dataType  : 'string',
                    width     : 200
                }],
                onrefresh  : function(me) {
                    self.load();
                }
            });

            // Events
            this.$Grid.addEvents({
                onClick    : this.$gridClick,
                onDblClick : this.$gridDblClick,
                onBlur     : this.$gridBlur
            });

            this.load();
        },

        /**
        * event : on resize
        */
        $onResize : function()
        {
            if ( !this.$Grid ) {
                return;
            }

            var Body = this.getContent();

            if ( !Body ) {
                return;
            }


            var size = Body.getSize();

            this.$Grid.setHeight( size.y - 40 );
            this.$Grid.setWidth( size.x - 40 );
        },

        /**
         * Load the users with the settings
         */
        load : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_vhosts_getList', function(result)
            {
                var host, entry;
                var data = [];

                for ( host in result )
                {
                    entry = result[ host ];

                    data.push({
                        host     : host,
                        project  : entry.project,
                        lang     : entry.lang,
                        template : entry.template
                    });
                }

                self.$Grid.setData({
                    data : data
                });

                self.Loader.hide();
            });
        },


        addVhost : function(host, data)
        {

        },


        removeVhost : function(host)
        {

        },


        openAddVhost : function()
        {

        },


        openEditVhost : function(vhost)
        {
            var self = this;

            if ( typeof vhost == 'undefined' ) {
                vhost = this.$Grid.getSelectedData().host;
            }

            var Sheet = this.createSheet({
                title  : vhost +' editieren',
                icon   : 'icon-external-link',
                events :
                {
                    onOpen : function(Sheet)
                    {
                        self.Loader.show();

                        Sheet.getContent().addClass( 'control-system-vhosts-sheet' );

                        Ajax.get([
                            'ajax_vhosts_get',
                            'ajax_template_getlist'
                        ], function(vhostData, templates)
                        {
                            var i, len, TemplateSelect, ProjectInput;

                            var Content = Sheet.getContent(),
                                project = vhostData.project,
                                lang    = vhostData.lang;

                            vhostData.domain = vhost;

                            delete vhostData.project;
                            delete vhostData.lang;

                            Content.set(
                                'html',

                                '<form action="">' +
                                '<table class="data-table">' +
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
                                '</form>'
                            );

                            TemplateSelect = Content.getElement( '[name="template"]' );
                            ProjectInput   = Content.getElement( '[name="project"]' );

                            ProjectInput.value = JSON.encode([{
                                project : project,
                                lang    : lang
                            }]);

                            // create controls
                            ControlUtils.parse( Content );

                            FormUtils.setDataToForm(
                                vhostData,
                                Content.getElement( 'form' )
                            );

                            // create template select
                            for ( i = 0, len = templates.length; i < len; i++ )
                            {
                                new Element('option', {
                                    value : templates[ i ].name,
                                    html  : templates[ i ].name
                                }).inject( TemplateSelect );
                            }

                            self.Loader.hide();

                        }, {
                            vhost : vhost
                        });
                    }
                }
            });

            Sheet.show();
        },

        /**
         * grid events
         */

        /**
         * event : click at the grid
         *
         * @param {Object} data - grid event data
         */
        $gridClick : function(data)
        {
            var len    = data.target.selected.length,
                Edit   = this.getButtons( 'editVhost' ),
                Delete = this.getButtons( 'delVhost' );

            if ( len === 0 )
            {
                Edit.disable();
                Delete.disable();

                return;
            }

            Edit.enable();
            Delete.enable();

            data.evt.stop();
        },

        /**
         * event : double click at the grid
         *
         * @param {Object} data - grid event data
         */
        $gridDblClick : function(data)
        {
            this.openEditVhost(
                data.target.getDataByRow( data.row ).host
            );
        },

        /**
         *
         */
        $gridBlur : function()
        {
            this.$Grid.unselectAll();
            this.$Grid.removeSections();

            this.getButtons( 'editVhost' ).disable(),
            this.getButtons( 'delVhost' ).disable();
        }
    });

});
