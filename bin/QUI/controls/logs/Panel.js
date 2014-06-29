
/**
 * Panel for the QUIQQER Logs
 */

define('controls/logs/Panel', [

    'qui/controls/desktop/Panel',
    'controls/grid/Grid',
    'Ajax',
    'Locale',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/windows/Confirm',

    'css!controls/logs/Panel.css'

], function(Panel, Grid, Ajax, Locale, QUIButton, QUIButtonSeperator, QUIConfirm)
{
    "use strict";

    var lg = 'quiqqer/system';

    /**
     * @class controls/logs/Panel
     */
    return new Class({

        Extends : Panel,
        Type    : 'controls/logs/Panel',

        Binds : [
            'getLogs',
            'resize',
            'refreshFile',
            'deleteActiveLog',
            '$onCreate',
            '$onResize',
            '$onDestroy',
            '$btnOpenLog',
            '$gridRefresh',
            '$gridClick',
            '$gridDblClick'
        ],

        options : {
            file   : '',
            page   : 1,
            limit  : 20,
            search : '',
            'site-width' : 220
        },

        initialize: function(options)
        {
            // defaults
            this.setAttribute( 'title', Locale.get( lg, 'logs.panel.title' ) );
            this.setAttribute( 'icon', 'icon-terminal' );

            this.parent( options );

            this.$Fx     = null;
            this.$Search = null;
            this.$Grid   = null;
            this.$GridContainer = null;


            this.$openLog = false;
            this.$file    = false;

            this.addEvents({
                onCreate  : this.$onCreate,
                onDestroy : this.$onDestroy,
                onResize  : this.$onResize
            });
        },

        /**
         * Asking for logs, show the log list
         */
        getLogs : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_system_logs_get', function(result)
            {
                // open buttons
                for ( var i = 0, len = result.data.length; i < len; i++ )
                {
                    result.data[ i ].open = {
                        image : URL_BIN_DIR +'16x16/actions/klipper_dock.png',
                        file  : result.data[ i ].file,

                        alt : Locale.get( lg, 'logs.panel.btn.open.log', {
                            date : result.data[ i ].mdate
                        }),

                        title : Locale.get( lg, 'logs.panel.btn.open.log', {
                            date : result.data[ i ].mdate
                        }),

                        events : {
                            onClick : self.$btnOpenLog
                        }
                    };
                }

                self.$Grid.setData( result );
                self.Loader.hide();
            }, {
                page   : this.getAttribute( 'page' ),
                limit  : this.getAttribute( 'limit' ),
                search : this.getAttribute( 'search' )
            });
        },

        /**
         * Open a log file
         *
         * @param {String} file - name of the log
         */
        openLog : function(file)
        {
            if ( !this.$Fx ) {
                return;
            }

            var Control = this;

            Control.Loader.show();

            Control.$openLog = true;
            Control.$file    = file;

            Control.setAttribute(
                'title',
                Locale.get( lg, 'logs.panel.log.title', {
                    file : file
                })
            );

            Control.$Fx.animate({
                width : Control.getAttribute( 'site-width' )
            }, {
                callback : function()
                {
                    var Body   = Control.getContent(),
                        Parent = Body.getParent();

                    var File = Parent.getElement( '.qui-logs-file' );

                    if ( !File ) {
                        File = new Element('div.qui-logs-file').inject( Parent );
                    }

                    Control.refreshFile();
                }
            });
        },

        /**
         * Delete a log
         *
         * @param {String} file - name of the log
         * @param {Function} callback - callback function
         */
        deleteLog : function(file, callback)
        {
            Ajax.get('ajax_system_logs_delete', function(result)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            }, {
                file : file
            });
        },

        /**
         * Delete the active log
         */
        deleteActiveLog : function()
        {
            var self = this,
                sel  = this.$Grid.getSelectedData();

            new QUIConfirm({
                title  : Locale.get( lg, 'logs.panel.delete.window.title', {
                    file : sel[0].file
                }),
                icon   : 'icon-remove',
                text   : Locale.get( lg, 'logs.panel.delete.window.text' ),
                events :
                {
                    onSubmit : function()
                    {
                        self.Loader.show();

                        self.deleteLog(sel[0].file, function() {
                            self.getLogs();
                        });
                    }
                }
            }).open();
        },

        /**
         * Refresh the current file
         *
         * @return {this} self
         */
        refreshFile : function()
        {
            if ( !this.$file ) {
                return this;
            }

            var Control = this,
                File    = this.getBody().getParent().getElement( '.qui-logs-file' );

            this.Loader.show();

            Ajax.get('ajax_system_logs_file', function(result)
            {
                require(['classes/utils/SyntaxHighlighter'], function(Highlighter)
                {
                    File.set(
                        'html',
                        '<pre class="box language-bash" style="margin: 0;">'+ result +'</pre>'
                    );

                    new Highlighter().highlight(
                        File.getElement( 'pre' )
                    );

                    Control.Loader.hide();
                    Control.refresh();
                });
            }, {
                file : this.$file
            });
        },

        /**
         * event : on create
         * build the panel
         */
        $onCreate : function()
        {
            var Control = this;

            this.$GridContainer = new Element('div', {
                'class' : 'qui-logs-container'
            }).inject( this.getContent() );

            this.$Fx = moofx( this.getContent() );

            this.$Grid = new Grid(this.$GridContainer, {
                columnModel : [{
                    header    : '&nbsp;',
                    dataIndex : 'open',
                    dataType  : 'button',
                    width     : 40
                }, {
                    header    : Locale.get( lg, 'logs.panel.log.file' ),
                    dataIndex : 'file',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : Locale.get( lg, 'date' ),
                    dataIndex : 'mdate',
                    dataType  : 'date',
                    width     : 200
                }],

                pagination : true,
                onrefresh  : this.$gridRefresh
            });

            this.$Grid.addEvents({
                onClick    : this.$gridClick,
                onDblClick : this.$gridDblClick
            });


            this.$Search = new Element('input', {
                'class'     : 'qui-logs-search',
                placeholder : Locale.get( lg, 'logs.panel.search.placeholder' ),
                events :
                {
                    keyup : function(event)
                    {
                        if ( event.key == 'enter' ) {
                            Control.getButtons( 'search' ).onclick();
                        }
                    }
                }
            });

            this.getButtonBar().appendChild( this.$Search );

            this.addButton(
                new QUIButton({
                    name      : 'search',
                    image     : 'icon-search',
                    alt       : Locale.get( lg, 'logs.panel.search.btn.start.alt' ),
                    title     : Locale.get( lg, 'logs.panel.search.btn.start.title' ),
                    events    :
                    {
                        onClick : function(Btn)
                        {
                            Control.setAttribute(
                                'search',
                                Control.$Search.value
                            );

                            Control.getLogs();
                        }
                    }
                })
            );

            this.addButton(
                new QUIButtonSeperator()
            );


            this.addButton(
                new QUIButton({
                    name      : 'refresh',
                    text      : Locale.get( lg, 'logs.panel.btn.refresh' ),
                    textimage : 'icon-refresh',
                    disabled  : true,
                    events    : {
                        onClick : this.refreshFile
                    }
                })
            );

            this.addButton(
                new QUIButtonSeperator()
            );

            this.addButton(
                new QUIButton({
                    name      : 'delete',
                    text      : Locale.get( lg, 'logs.panel.btn.delete.marked' ),
                    textimage : 'icon-trash',
                    disabled  : true,
                    events    : {
                        onClick : this.deleteActiveLog
                    }
                })
            );

            //this.resize.delay( 200 );
            this.getLogs.delay( 100, this );
        },

        /**
         * event : on resize
         * resize the panel and all elements
         */
        $onResize : function()
        {
            if ( !this.getBody() ) {
                return;
            }

            var size, height, width;

            var Body   = this.getBody(),
                Header = this.getHeader(),
                Parent = Body.getParent(),
                File   = Parent.getElement( '.qui-logs-file' );

            size   = Parent.getSize();
            height = size.y - Header.getSize().y - 50;

            if ( this.getButtonBar() ) {
                height = height - this.getButtonBar().getElm().getSize().y;
            }

            // file display
            if ( this.$openLog )
            {
                Body.setStyle( 'width', this.getAttribute( 'site-width' ) );

                this.getButtons('refresh').enable();

                this.$Grid.setWidth( 180 );
                this.$Grid.setHeight( height );

                if ( File )
                {
                    width = this.getAttribute( 'site-width' ) + 20;

                    File.setStyles({
                        height : height,
                        width  : size.x - width
                    });

                    File.getElement( 'pre' ).setStyles({
                        height : height,
                        width  : size.x - width
                    });
                }

                return;
            }

            // only log listing
            if ( this.getButtons( 'refresh' ) ) {
                this.getButtons( 'refresh' ).disable();
            }

            this.$Grid.setWidth( size.x - 40 );
            this.$Grid.setHeight( height );
        },

        /**
         * event : on destroy
         */
        $onDestroy : function()
        {
            this.$Grid.destroy();
        },

        /**
         * Click on the log button to open the log
         *
         * @param {qui/controls/buttons/button} Btn
         */
        $btnOpenLog : function(Btn)
        {
            this.openLog(
                Btn.getAttribute( 'file' )
            );
        },

        /**
         * event : grid refresh
         *
         * @param {controls/grid/Grid} Grid
         */
        $gridRefresh : function(Grid)
        {
            this.setAttributes({
                limit : Grid.getAttribute( 'perPage' ),
                page  : Grid.getAttribute( 'page' )
            });

            this.getLogs();
        },

        /**
         * event : on grid click
         *
         * @param {Object} data - Grid Data
         */
        $gridClick : function(data)
        {
            var len    = data.target.selected.length,
                Delete = this.getButtons( 'delete' );

            Delete.disable();

            if ( len )
            {
                Delete.enable();
                return;
            }
        },

        /**
         * event : on grid dbl click
         *
         * @param {Object} data - Grid Data
         */
        $gridDblClick : function(data)
        {
            var target = data.target,
                sel    = this.$Grid.getSelectedData();

            this.openLog( sel[ 0 ].file );
        }
    });
});