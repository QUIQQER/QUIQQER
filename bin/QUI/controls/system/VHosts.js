
/**
 * VHost Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/system/VHosts', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Prompt',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'controls/system/VHost',
    'controls/system/VHostServerCode',
    'Ajax'

], function(QUI, QUIPanel, QUIPrompt, QUIConfirm, Grid, Vhost, VhostServerCode, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/system/VHosts',

        Binds : [
            '$onCreate',
            '$onResize',

            '$gridClick',
            '$gridDblClick'
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
                events :
                {
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
                events :
                {
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
                events :
                {
                    onClick : function() {
                        self.openRemoveVhost();
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
                onDblClick : this.$gridDblClick
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

        /**
         * add a vhost
         *
         * @param {String} host - name of the host
         * @param {Function} callback - [optional] callback function
         */
        addVhost : function(host, callback)
        {
            var self = this;

            Ajax.get('ajax_vhosts_add', function(result)
            {
                self.load();

                if ( typeOf( callback ) === 'function' ) {
                    callback( host );
                }
            }, {
                vhost : host
            });
        },

        /**
         * Edit a vhost
         *
         * @param {String} host - virtual host eq: www.something.com
         * @param {Array} data - virtual host data
         * @param {Function} callback - [optional] callback function
         */
        editVhost : function(host, data, callback)
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_vhosts_edit', function(result)
            {
                self.load();

                if ( typeOf( callback ) === 'function' ) {
                    callback( host, data );
                }
            }, {
                vhost : host,
                data  : JSON.encode( data )
            });
        },

        /**
         * Delete a vhost
         *
         * @param {String} host - virtual host eq: www.something.com
         * @param {Function} callback - [optional] callback function
         */
        removeVhost : function(host, callback)
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_vhosts_remove', function(result)
            {
                self.load();

                if ( typeOf( callback ) === 'function' ) {
                    callback( host );
                }
            }, {
                vhost : host
            });
        },

        /**
         * window & sheet methods
         */

        /**
         * opens a add vhost window
         */
        openAddVhost : function()
        {
            var self = this;

            new QUIPrompt({
                icon  : 'icon-plus',
                title : 'Neuen Virtuellen-Host hinzufügen',
                information : 'Bitte geben Sie den neuen Host ein oder einen Server-Error-Code.<br />' +
                              'Zum Beispiel: www.meine-domain.de order einfach nur 404',
                events :
                {
                    onSubmit : function(value, Win)
                    {
                        self.addVhost( value, function(host)
                        {
                            Win.close();
                            self.openEditVhost( host );
                        });
                    }
                }
            }).open();
        },

        /**
         * Open the edit sheet
         *
         * @param {String} vhost - host name
         */
        openEditVhost : function(vhost)
        {
            var self = this;

            if ( typeof vhost === 'undefined' )
            {
                var data = this.$Grid.getSelectedData();

                if ( data[ 0 ] && data[ 0 ].host ) {
                    vhost = data[0].host;
                }
            }

            if ( typeof vhost === 'undefined' ) {
                return;
            }

            var Sheet = this.createSheet({
                title  : vhost +' editieren',
                icon   : 'icon-external-link',
                events :
                {
                    onOpen : function(Sheet)
                    {
                        self.Loader.show();

                        // only numbers -> server error codes
                        if ( /^\d+$/.test( vhost ) )
                        {
                            var Host = new VhostServerCode({
                                host : vhost
                            }).inject( Sheet.getContent() );

                        } else
                        {
                            var Host = new Vhost({
                                host : vhost
                            }).inject( Sheet.getContent() );
                        }


                        Sheet.addButton({
                            text      : 'Speichern',
                            textimage : 'icon-save',
                            events    :
                            {
                                onClick : function()
                                {
                                    Host.save(function() {
                                        Sheet.hide();
                                    });
                                }
                            }
                        });

                        self.Loader.hide();
                    },

                    onClose : function() {
                        self.load();
                    }
                }
            });

            Sheet.show();
        },

        /**
         * Open the remove window
         *
         * @param {String} vhost - host name
         */
        openRemoveVhost : function(vhost)
        {
            var self = this;

            if ( typeof vhost === 'undefined' )
            {
                var data = this.$Grid.getSelectedData();

                if ( data[ 0 ] && data[ 0 ].host ) {
                    vhost = data[0].host;
                }
            }

            if ( typeof vhost === 'undefined' ) {
                return;
            }


            new QUIConfirm({
                title : 'Virtuellen-Host löschen',
                icon  : 'icon-trash',
                text  : 'Möchten Sie den Host '+ vhost +' wirklich löschen?',
                texticon    : 'icon-trash',
                information : 'Beachten Sie, der Host Eintrag ist nicht wieder herstellbar und wird unwiederruflich gelöscht',

                closeButtonText : 'Abbrechen',

                ok_button : {
                    text      : 'Löschen',
                    textimage : 'icon-trash'
                },

                events :
                {
                    onSubmit : function() {
                        self.removeVhost( vhost );
                    }
                }
            }).open();
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
        }
    });

});
