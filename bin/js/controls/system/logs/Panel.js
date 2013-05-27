
/**
 *
 */

define('controls/system/logs/Panel', [

    'controls/desktop/Panel',
    'controls/loader/Loader',
    'controls/grid/Grid',

    'css!controls/system/logs/Panel.css'

], function(QUI_Panel)
{
    "use strict";

    QUI.namespace( 'controls.system.logs' );

    /**
     * @class QUI.controls.desktop.panels.Desktop
     */
    QUI.controls.system.logs.Panel = new Class({

        Extends : QUI_Panel,
        Type    : 'QUI.controls.system.logs.Panel',

        Binds : [
            'getLogs',
            'resize',
            'refreshFile',
            '$onCreate',
            '$onResize',
            '$onDestroy',
            '$btnOpenLog'
        ],

        options : {
            file  : '',
            page  : 1,
            limit : 20,
            'site-width' : 220
        },

        initialize: function(options)
        {
            // defaults
            this.setAttribute( 'title', 'Logs' );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/actions/klipper_dock.png' );
            this.parent( options );

            this.$Grid          = null;
            this.$GridContainer = null;
            this.$Fx            = null;

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
            var Control = this;

            Control.Loader.show();

            QUI.Ajax.get('ajax_system_logs_get', function(result)
            {
                // open buttons
                for ( var i = 0, len = result.data.length; i < len; i++ )
                {
                    result.data[ i ].open = {
                        image  : URL_BIN_DIR +'16x16/actions/klipper_dock.png',
                        alt    : 'Log vom '+ result.data[ i ].mdate  +' öffnen',
                        title  : 'Log vom '+ result.data[ i ].mdate  +' öffnen',
                        file   : result.data[ i ].file,
                        events : {
                            onClick : Control.$btnOpenLog
                        }
                    };
                }

                Control.$Grid.setData( result );
                Control.Loader.hide();
            }, {
                page  : this.getAttribute( 'page' ),
                limit : this.getAttribute( 'limit' )
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

            if ( Control.getButtons( 'refresh' ).length === 0 )
            {
                Control.addButton({
                    name : 'refresh',
                    text : 'Datei aktualisieren',
                    textimage : URL_BIN_DIR +'16x16/actions/reload.png',
                    events : {
                        onClick : Control.refreshFile
                    }
                });
            }


            Control.setAttribute( 'title', 'Logs: '+ file );

            Control.$Fx.animate({
                width : Control.getAttribute( 'site-width' )
            }, {
                callback : function()
                {
                    var Body   = Control.getBody(),
                        Parent = Body.getParent();

                    var File = Parent.getElement( '.qui-logs-file' );

                    if ( !File ) {
                        File = new Element('div.qui-logs-file').inject( Parent );
                    }

                    QUI.Ajax.get('ajax_system_logs_file', function(result)
                    {
                        require([

                            'classes/utils/SyntaxHighlighter'

                        ], function(Highlighter)
                        {
                            File.set(
                                'html',
                                '<pre class="box language-bash">'+ result +'</pre>'
                            );

                            new Highlighter().highlight(
                                File.getElement( 'pre' )
                            );

                            Control.Loader.hide();
                            Control.refresh();
                        });

                    }, {
                        file : file
                    });
                }
            });
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

            QUI.Ajax.get('ajax_system_logs_file', function(result)
            {
                File.set( 'html', '<pre class="box">'+ result +'</pre>' );

                Control.Loader.hide();

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
            this.$GridContainer = new Element('div', {
                'class' : 'qui-logs-container'
            }).inject(
                this.getBody()
            );

            this.$Fx = moofx( this.getBody() );

            this.$Grid = new QUI.controls.grid.Grid(this.$GridContainer, {
                columnModel : [{
                    header    : '&nbsp;',
                    dataIndex : 'open',
                    dataType  : 'button',
                    width     : 40
                }, {
                    header    : 'Log',
                    dataIndex : 'file',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Datum',
                    dataIndex : 'mdate',
                    dataType  : 'date',
                    width     : 200
                }],

                pagination : true,
                onrefresh  : this.getLogs
            });

            this.resize.delay( 200 );
            this.getLogs();
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

            // file display
            if ( this.$openLog )
            {
                size   = Parent.getSize();
                height = size.y - Header.getSize().y - 50;

                if ( this.getButtonBar() ) {
                    height = height - this.getButtonBar().getElm().getSize().y;
                }

                Body.setStyle( 'width', this.getAttribute( 'site-width' ) );

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
            size = Body.getSize();

            this.$Grid.setWidth( size.x - Header.getSize().y - 10 );
            this.$Grid.setHeight( size.y - Header.getSize().y - 10 );
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
         * @param {QUI.controls.buttons.button} Btn
         */
        $btnOpenLog : function(Btn)
        {
            this.openLog(
                Btn.getAttribute( 'file' )
            );
        }
    });

    return QUI.controls.system.logs.Panel;
});