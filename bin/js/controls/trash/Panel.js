/**
 * Trash Panel - Trash Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/trash/Panel
 * @package com.pcsg.qui.js.controls.trash
 * @namespace QUI.controls.trash
 */

define('controls/trash/Panel', [

    'controls/desktop/Panel',
    'lib/Projects',
    'controls/grid/Grid',
    'controls/buttons/Select',
    'controls/projects/Trash',
    'classes/projects/media/Trash'

], function(QUI_Panel, Grid)
{
    QUI.namespace('controls.trash');

    QUI.controls.trash.Panel = new Class({

        Implements : QUI_Panel,
        Type       : 'QUI.controls.trash.Manager',

        Binds : [
            '$onCreate',
            '$onResize',
            '$onDestroy',
            'restoreSites',
            'destroySites'
        ],

        options : {
            id        : 'trash-panel',
            container : false,
            project   : false,
            lang      : false,
            media     : false,

            order : '',
            sort  : '',
            max   : 20,
            page  : 1
        },

        /**
         * @constructor
         *
         * @event onDraBegin [this]
         * @event onDraEnd [this]
         * @event onLoadBegin [this]
         * @event onLoadEnd [this]
         */
        initialize : function(options)
        {
            this.$uid = String.uniqueID();

            this.init( options );

            this.$Grid  = null;
            this.$Trash = null;

            this.addEvent( 'onCreate', this.$onCreate );
            this.addEvent( 'onResize', this.$onResize );
            this.addEvent( 'onDestroy', this.$onDestroy );
        },

        /**
         * Return the trash grid
         * @return {QUI.controls.grid.Grid}
         */
        getGrid : function()
        {
            return this.$Grid;
        },

        /**
         * create the grid and the buttons
         */
        $onCreate : function()
        {
            //this.fireEvent('onDrawBegin', [this]);

            var Container     = this.getBody(),
                GridContainer = Container.getElement( '.gridcontainer' );

            if ( !GridContainer )
            {
                GridContainer = new Element('div.gridcontainer', {
                    styles : {
                        height : Container.getSize().y -20
                    }
                }).inject( Container );
            }

            GridContainer.set( 'html', '' );

            this.$Grid = new QUI.controls.grid.Grid(GridContainer, {
                columnModel : [{
                    header    : '&nbsp;',
                    dataIndex : 'icon',
                    dataType  : 'image',
                    width     : 40,
                    style     : {
                        margin: '5px 0 5px 5px'
                    }
                }, {
                    header    : 'ID',
                    dataIndex : 'id',
                    dataType  : 'integer',
                    width     : 100
                }, {
                    header    : 'Name',
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 300
                }, {
                    header    : 'Titel',
                    dataIndex : 'title',
                    dataType  : 'string',
                    width     : 300
               }],
               //buttons : [],
               pagination  : true,
               filterInput : true,
               serverSort  : true,
               showHeader  : true,
               sortHeader  : true,
               width       : GridContainer.getSize().x,
               height      : GridContainer.getSize().y,

               events :
               {
                   onRefresh : function(Grid)
                   {
                       var options = Grid.options;

                       this.setAttribute( 'order', options.sortOn );
                       this.setAttribute( 'sort', options.sortBy );
                       this.setAttribute( 'max', options.perPage );
                       this.setAttribute( 'page', options.page );

                       this.load();
                   }.bind( this ),

                   onClick : function(data, Grid)
                   {
                       var ButtonBar  = this.getButtonBar(),
                           BtnDel     = ButtonBar.getChildren( 'trashDel' ),
                           BtnRestore = ButtonBar.getChildren( 'trashRestore' ),
                           len        = data.target.selected.length;

                       if ( !len )
                       {
                           BtnDel.setDisable();
                           BtnRestore.setDisable();
                           return;
                       }

                       BtnDel.setEnable();
                       BtnRestore.setEnable();

                   }.bind( this )
               },

               alternaterows     : true,
               resizeColumns     : true,
               selectable        : true,
               multipleSelection : true,
               resizeHeaderOnly  : true
            });

            // buttons
            var ButtonBar = this.getButtonBar(),

                Select    = new QUI.controls.buttons.Select({
                    name   : 'trash_select',
                    events :
                    {
                        onChange : function(value, Select)
                        {
                            var data = value.split(':');

                            this.setAttribute( 'project', data[0] );

                            if ( data[1] == 'media' )
                            {
                                this.setAttribute( 'lang', '' );
                                this.setAttribute( 'media', true );
                            } else
                            {
                                this.setAttribute( 'lang', data[1] );
                                this.setAttribute( 'media', false );
                            }

                            this.fireEvent( 'onDrawEnd', [ this ] );
                            this.load();
                        }.bind( this )
                    }
                });

            ButtonBar.appendChild(
                Select
            ).appendChild(
                new QUI.controls.buttons.Seperator()
            );

            this.addButton({
                name      : 'trashDel',
                Control   : this,
                text      : 'löschen',
                alt       : 'Markierte Elemente zerstören',
                title     : 'Markierte Elemente zerstören',
                disabled  : true,
                textimage : URL_BIN_DIR +'16x16/trashcan_full.png',
                events    : {
                    onClick : this.destroySites
                }
            }).addButton({
                name      : 'trashRestore',
                Control   : this,
                text      : 'Wiederherstellen',
                alt       : 'Markierte Elemente wiederherstellen',
                title     : 'Markierte Elemente wiederherstellen',
                disabled  : true,
                textimage : URL_BIN_DIR +'16x16/restore.png',
                events    : {
                    onClick : this.restoreSites
                }
            });

            QUI.lib.Projects.getList(function(result, Request)
            {
                var i, len, project, langs, Project;

                var ButtonBar = this.getButtonBar(),
                    Select    = ButtonBar.getElement( 'trash_select' );


                for ( project in result )
                {
                    langs = result[ project ].langs.split(',');

                    for ( i = 0, len = langs.length; i < len; i++ )
                    {
                        Select.appendChild(
                            project +' ('+ langs[i] +')',
                            project +':'+ langs[i],
                            URL_BIN_DIR +'16x16/flags/'+ langs[i] +'.png'
                        );
                    }

                    Select.appendChild(
                        project +' (Media)',
                        project +':media',
                        URL_BIN_DIR +'16x16/media.png'
                    );
                }

                ButtonBar.refresh();

                // select the project
                Project = QUI.lib.Projects.get(
                    this.getAttribute( 'project' ),
                    this.getAttribute( 'lang' )
                );

                if ( this.getAttribute( 'media ') )
                {
                    Select.setValue(
                        Project.getAttribute( 'name' ) +':media'
                    );

                    return;
                }

                if ( Project.getAttribute( 'name' ) &&
                     Project.getAttribute( 'lang' ) )
                {
                    Select.setValue(
                        Project.getAttribute( 'name' ) +':'+
                        Project.getAttribute( 'lang' )
                    );

                    return;
                }

                Select.setValue(
                    Select.firstChild().getAttribute( 'value' )
                );

            }.bind( this ));
        },

        /**
         * load the project / media trash in the grid
         */
        load : function()
        {
            this.Loader.show();
            this.fireEvent( 'onLoadBegin', [ this ] );

            var Media     = false,
                ButtonBar = this.getButtonBar(),

                Project = QUI.lib.Projects.get(
                    this.getAttribute( 'project' ),
                    this.getAttribute( 'lang' )
                );

            ButtonBar.getChildren( 'trashDel' ).setDisable();
            ButtonBar.getChildren( 'trashRestore' ).setDisable();


            if ( this.getAttribute( 'media' ) )
            {
                Media = Project.getMedia();

                this.$Trash = Media.getTrash().getControl();
            } else
            {
                this.$Trash = Project.getTrash().getControl();
            }

            this.$Trash.getList( this.$loadList.bind( this ) );
        },

        /**
         * opens the destroy dialog for all selected items
         */
        destroySites : function()
        {
            var ids = [];

            this.$Trash.destroy(
                this.$getSelectedIds(),
                this.load.bind( this )
            );
        },

        /**
         * opens the restore dialog for all selected items
         */
        restoreSites : function()
        {
            this.$Trash.restore(
               this.$getSelectedIds(),
               this.load.bind( this )
           );
        },

        /**
         * return the selected ids
         *
         * @return {Array}
         */
        $getSelectedIds : function()
        {
            var i, len;

            var result = [],
                data   = this.$Grid.getSelectedData();

            for ( i = 0, len = data.length; i < len; i++ ) {
                result.push( data[i].id );
            }

            return result;
        },

        /**
         * Load the Data into the grid
         */
        $loadList : function(result)
        {
            this.$Grid.setData( result );
            this.$refreshTitle();

            this.fireEvent( 'loadEnd', [ this ] );
            this.Loader.hide();
        },

        /**
         * Refresh the panel title
         */
        $refreshTitle : function()
        {
            // set loader image
            this.setAttributes({
                icon  : URL_BIN_DIR +'16x16/trashcan_empty.png',
                title : 'Mülleimer'
            });

            if ( this.$Trash )
            {
                this.setAttributes({
                    title : this.$Trash.getTitle()
                });
            }

            this.refresh();
        },

        /**
         * Resize the trash panel
         */
        $onResize : function()
        {
            var Body = this.getBody();

            if ( !Body ) {
                return;
            }

            if ( this.getAttribute( 'search' ) )
            {
                this.getGrid().setHeight( Body.getSize().y - 100 );
            } else
            {
                this.getGrid().setHeight( Body.getSize().y - 40 );
            }

            this.getGrid().setWidth( Body.getSize().x - 40 );
        }
    });

    return QUI.controls.trash.Panel;
});