
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
            '$btnOpenLog',
            '$gridRefresh'
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
            this.setAttribute( 'title', 'Logs' );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/actions/klipper_dock.png' );

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

                    Control.refreshFile();
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
                require([

                     'classes/utils/SyntaxHighlighter'

                 ], function(Highlighter)
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
                onrefresh  : this.$gridRefresh
            });


            this.$Search = new Element('input', {
                'class'     : 'qui-logs-search',
                placeholder : 'Suche nach...',
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

            this.addButton({
                name      : 'search',
                image     : URL_BIN_DIR +'16x16/search.png',
                alt       : 'Suche starten',
                title     : 'Suche starten',
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
            });

            this.addButton({
                type : 'QUI.controls.buttons.Seperator'
            });


            this.addButton({
                name      : 'refresh',
                text      : 'Datei aktualisieren',
                textimage : URL_BIN_DIR +'16x16/actions/reload.png',
                disabled  : true,
                events    : {
                    onClick : this.refreshFile
                }
            });


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
            this.getButtons( 'refresh' ).disable();

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
         * @param {QUI.controls.buttons.button} Btn
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
         * @param {QUI.controls.grid.Grid} Grid
         */
        $gridRefresh : function(Grid)
        {
            this.setAttributes({
                limit : Grid.getAttribute( 'perPage' ),
                page  : Grid.getAttribute( 'page' )
            });

            this.getLogs();
        }
    });

    return QUI.controls.system.logs.Panel;
});