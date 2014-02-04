/**
 * Groups manager panel (View)
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/groups/Panel
 * @package com.pcsg.qui.js.controls.groups
 * @namespace QUI.controls.groups
 *
 * @require controls/desktop/Panel
 * @require Groups
 * @require controls/grid/Grid
 * @require controls/Utils
 * @require classes/messages
 * @require controls/groups/sitemap/Window
 * @require controls/windows/Submit
 */

define('controls/groups/Panel', [

    'qui/controls/desktop/Panel',
    'Groups',
    'controls/grid/Grid',
    'controls/groups/sitemap/Window',
    'utils/Template',
    'qui/controls/messages/Attention',
    'qui/controls/windows/Prompt',

    'css!controls/groups/Panel.css'

], function(Panel, Groups, Grid, GroupSitemapWindow, Template, Attention, QUIPrompt)
{
    "use strict";

    /**
     * @class qui/controls/groups/Panel
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : Panel,
        Type    : 'qui/controls/groups/Panel',

        Binds : [
             '$onCreate',
             '$onResize',
             '$onSwitchStatus',
             '$onDeleteGroup',
             '$onRefreshGroup',
             '$onButtonEditClick',
             '$onButtonDelClick',

             '$gridClick',
             '$gridDblClick',
             '$gridBlur',

             'search',
             'createGroup',
             'openPermissions'
        ],

        options : {
            active_image : 'icon-ok',     		// [optional]
            active_text  : 'Gruppe aktivieren', // [optional]

            deactive_image : 'icon-remove',         // [optional]
            deactive_text  : 'Gruppe deaktivieren', // [optional]

            field : 'name',
            order : 'ASC',
            limit : 20,
            page  : 1,
            view  : 'table',

            search       : false,
            searchfields : [ 'id', 'name' ]
        },

        initialize : function(options)
        {
            this.$uid = String.uniqueID();

            this.parent( options );

            this.$Grid      = null;
            this.$Container = null;

            this.addEvent( 'onCreate', this.$onCreate );
            this.addEvent( 'onResize', this.$onResize );

            Groups.addEvents({
                onSwitchStatus : this.$onSwitchStatus,
                onDelete       : this.$onDeleteGroup,
                onRefresh      : this.$onRefreshGroup
            });


            var self = this;

            this.addEvent('onDestroy', function() {
                Groups.removeEvent( 'switchStatus', this.$onSwitchStatus );
            });
        },

        /**
         * Return the group grid
         *
         * @return {controls/grid/Grid|null}
         */
        getGrid : function()
        {
            return this.$Grid;
        },

        /**
         * create the group panel
         */
        $onCreate : function()
        {
            var self = this;

            this.addButton({
                name   : 'groupSearch',
                events :
                {
                    onMousedown : function(Btn) {
                        self.search();
                    }
                },
                alt   : 'Gruppe suchen',
                title : 'Gruppe suchen',
                image : 'icon-search'
            });

            this.addButton({
                type : 'seperator'
            });

            this.addButton({
                name   : 'groupNew',
                events : {
                    onMousedown : this.createGroup
                },
                text : 'Neue Gruppe anlegen'
            });

            this.addButton({
                name   : 'groupEdit',
                events : {
                    onMousedown : this.$onButtonEditClick
                },
                text      : 'Gruppe bearbeiten',
                disabled  : true,
                textimage : 'icon-pencil'
            });

            this.addButton({
                name   : 'groupDel',
                events : {
                    onMousedown : this.$onButtonDelClick
                },
                text      : 'Gruppe löschen',
                disabled  : true,
                textimage : 'icon-trash'
            });


            // create grid
            var Body = this.getContent();

            this.$GridContainer = new Element('div');
            this.$GridContainer.inject( Body );


            this.$Grid = new Grid(this.$GridContainer, {
                columnModel : [{
                    header    : 'Status',
                    dataIndex : 'status',
                    dataType  : 'button',
                    width     : 50
                }, {
                    header    : 'Gruppen-ID',
                    dataIndex : 'id',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : 'Gruppenname',
                    dataIndex : 'name',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : 'Darf in den Admin',
                    dataIndex : 'admin',
                    dataType  : 'string',
                    width     : 150
                }],
                pagination : true,
                filterInput: true,
                perPage    : this.getAttribute( 'limit' ),
                page       : this.getAttribute( 'page' ),
                sortOn     : this.getAttribute( 'field' ),
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : Body.getSize().x - 40,
                height     : Body.getSize().y - 40,
                onrefresh  : function(me)
                {
                    var options = me.options;

                    self.setAttribute( 'field', options.sortOn );
                    self.setAttribute( 'order', options.sortBy );
                    self.setAttribute( 'limit', options.perPage );
                    self.setAttribute( 'page', options.page );

                    self.load();
                },

                alternaterows     : true,
                resizeColumns     : true,
                selectable        : true,
                multipleSelection : true,
                resizeHeaderOnly  : true
            });

            // Events
            this.$Grid.addEvents({
                onClick    : this.$gridClick,
                onDblClick : this.$gridDblClick,
                onBlur     : this.$gridBlur
            });

            // toolbar resize after insert
            (function()
            {
                self.getButtonBar().setAttribute( 'width', '98%' );
                self.getButtonBar().resize();
            }).delay( 200 );

            // start and list the groups
            this.load();
        },

        /**
         * Load the groups with the settings
         */
        load : function()
        {
            this.Loader.show();
            this.$loadGroups();
        },

        /**
         * create a group panel
         *
         * @param {Integer} gid - Group-ID
         * @return {this}
         */
        openGroup : function(gid)
        {
            require([ 'controls/groups/Group' ], function(QUI_Group)
            {
                this.getParent().appendChild(
                    new QUI_Group( gid )
                );

            }.bind( this ));

            return this;
        },

        /**
         * Opens the groups search settings
         */
        search : function()
        {
            this.Loader.show();

            var self  = this,
                Sheet = this.createSheet();

            Sheet.addEvent('onOpen', function(Sheet)
            {
                Template.get('groups_searchtpl', function(result, Request)
                {
                    var i, len, Frm, Search;

                    var Body   = Sheet.getBody(),
                        fields = self.getAttribute('searchfields'),
                        search = self.getAttribute('search');

                    Body.set( 'html', result );
                    self.setAttribute( 'SearchSheet', Sheet );

                    // @todo parse controls
                    // QUI.controls.Utils.parse( Body );

                    Frm    = Body.getElement('form');
                    Search = Frm.elements.search;

                    Search.addEvent('keyup', function(event)
                    {
                        if ( event.key === 'enter' ) {
                            self.execSearch( self.getAttribute( 'SearchSheet' ) );
                        }
                    });

                    Search.value = search || '';
                    Search.focus();

                    for ( i = 0, len = fields.length; i < len; i++ )
                    {
                        switch ( fields[i] )
                        {
                            case 'id':
                                Frm.elements.gid.checked = true;
                            break;

                            case 'name':
                                Frm.elements.name.checked = true;
                            break;
                        }
                    }

                    Frm.addEvent('submit', function(event) {
                        event.stop();
                    });


                    self.Loader.hide();
                });
            });

            Sheet.show();
        },

        /**
         * Execute the search
         *
         * @param {qui/desktop/panels/Sheet}
         */
        execSearch : function(Sheet)
        {
            var fields = [],
                Frm    = Sheet.getBody().getElement('form');

            if ( Frm.elements.gid.checked ) {
                fields.push( 'id' );
            }

            if ( Frm.elements.name.checked ) {
                fields.push( 'name' );
            }

            this.setAttribute( 'search', Frm.elements.search.value );
            this.setAttribute( 'searchfields', fields );

            Sheet.hide();

            this.load();
        },

        /**
         * Open the group create dialog
         */
        createGroup : function()
        {
            var self = this;

            new GroupSitemapWindow({
                title  : 'Gruppe erstellen',
                text   : 'Unter welcher Gruppe soll die neue Gruppe angelegt werden?',
                events :
                {
                    // now we need a groupname
                    onSubmit : function(Win, result)
                    {
                        if ( !result.length ) {
                            return;
                        }

                        new QUIPrompt({
                            title   : 'Neuer Gruppennamen',
                            icon    : 'icon-group',
                            height  : 220,
                            width   : 450,
                            text    : 'Bitte geben Sie den neuen Gruppennamen an',
                            pid     : result[ 0 ],
                            events :
                            {
                                onDrawEnd : function(Win) {
                                    Win.getBody().getElement('input').focus();
                                },

                                onSubmit : function(result, Win)
                                {
                                    Win.Loader.show();

                                    Groups.createGroup(
                                        result,
                                        Win.getAttribute( 'pid' ),
                                        function( newgroupid )
                                        {
                                            self.load();
                                            self.openGroup( newgroupid );

                                            Win.close();
                                        }
                                    );
                                }
                            }
                        }).open();

                    }
                }
            }).open();
        },

        /**
         * Convert a Group to a grid data field
         *
         * @param {QUI.controls.groups.Group} Group
         * @return {Object}
         */
        groupToData : function(Group)
        {
            // defaults
            var data = {
                status  : false,
                id      : Group.getId(),
                name    : Group.getAttribute( 'name' ),
                admin   : '---'
            };

            if ( Group.getAttribute( 'admin' ) ) {
                data.admin = 'Ja';
            }

            data.status = {
                status : Group.isActive(),
                value  : Group.getId(),
                gid    : Group.getId(),
                image  : Group.isActive() ?
                            this.getAttribute( 'active_image' ) :
                            this.getAttribute( 'deactive_image' ),

                alt : Group.isActive() ?
                            this.getAttribute( 'deactive_text' ) :
                            this.getAttribute( 'active_text' ),

                events : {
                    onClick : this.$btnSwitchStatus
                }
            };

            return data;
        },

        /**
         * click on the grid
         *
         * @param {DOMEvent} data
         */
        $gridClick : function(data)
        {
            var len    = data.target.selected.length,
                Edit   = this.getButtons( 'groupEdit' ),
                Delete = this.getButtons( 'groupDel' );

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
         * dblclick on the grid
         *
         * @param {Object} data - grid selected data
         */
        $gridDblClick : function(data)
        {
            this.openGroup(
                data.target.getDataByRow( data.row ).id
            );
        },

        /**
         * onblur on the grid
         */
        $gridBlur : function()
        {
            this.getGrid().unselectAll();
            this.getGrid().removeSections();

            this.getButtons( 'groupEdit' ).disable(),
            this.getButtons( 'groupDel' ).disable();
        },

        /**
         * Resize the groups panel
         */
        $onResize : function()
        {
            var Body = this.getBody();

            if ( !Body ) {
                return;
            }

            if ( !this.getGrid() ) {
                return;
            }

            if ( this.getAttribute( 'search' ) )
            {
                this.getGrid().setHeight( Body.getSize().y - 100 );
            } else
            {
                this.getGrid().setHeight( Body.getSize().y - 40 );
            }

            var Message = Body.getElement( '.message' );

            if ( Message ) {
                Message.setStyle( 'width', this.getBody().getSize().x - 40 );
            }

            this.getGrid().setWidth( Body.getSize().x - 40 );
        },

        /**
         * Load the groups to the grid
         */
        $loadGroups : function()
        {
            var self = this;

            this.Loader.show();

            this.setAttribute( 'title', 'Gruppenverwaltung' );
            this.setAttribute( 'icon', 'icon-refresh icon-spin' );
            this.refresh();

            if ( this.getAttribute( 'search' ) &&
                 !this.getBody().getElement( '.message' ) )
            {
                var Msg = new Attention({
                    message : 'Sucheparameter sind aktiviert. '+
                              'Klicken Sie hier um die Suche zu beenden und alle Gruppen '+
                              'wieder anzeigen zu lassen.',
                    events  :
                    {
                        onClick : function(Message, event)
                        {
                            self.setAttribute( 'search', false );
                            self.setAttribute( 'searchSettings', {} );

                            Message.destroy();
                            self.load();
                        }
                    },
                    styles  : {
                        margin : '0 0 20px',
                        'border-width' : 1,
                        cursor : 'pointer'
                    }
                }).inject( this.getContent(), 'top' );
            }

            this.resize();


            Groups.getList({
                field : this.getAttribute( 'field' ),
                order : this.getAttribute( 'order' ),
                limit : this.getAttribute( 'limit' ),
                page  : this.getAttribute( 'page' ),
                search         : this.getAttribute( 'search' ),
                searchSettings : this.getAttribute( 'searchSettings' )

            }, function(result, Request)
            {
                var i, len, data, group, admin;

                var Panel = Request.getAttribute( 'Panel' ),
                    Grid  = Panel.getGrid();

                if ( !Grid )
                {
                    Panel.Loader.hide();
                    return;
                }

                data = result.data;

                for ( i = 0, len = data.length; i < len; i++ )
                {
                    admin = ( data[i].admin ).toInt();

                    data[i].active = ( data[i].active ).toInt();
                    data[i].admin  = '---';

                    if ( admin ) {
                        data[i].admin = 'Ja';
                    }

                    data[i].status = {
                        status : data[i].active,
                        value  : data[i].id,
                        gid    : data[i].id,
                        image  : data[i].active ?
                                Panel.getAttribute( 'active_image' ) :
                                    Panel.getAttribute( 'deactive_image' ),

                        alt : data[i].active ?
                                Panel.getAttribute( 'deactive_text' ) :
                                    Panel.getAttribute( 'active_text' ),

                        events : {
                            onClick : Panel.$btnSwitchStatus
                        }
                    };
                }

                Grid.setData( result );

                Panel.setAttribute( 'title', 'Gruppenverwaltung' );
                Panel.setAttribute( 'icon', 'icon-group' );
                Panel.refresh();

                Panel.Loader.hide();
            }, {
                Panel : this
            });
        },

        /**
         * execute a group status switch
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $btnSwitchStatus : function(Btn)
        {
            Btn.setAttribute( 'icon', URL_BIN_DIR +'images/loader.gif' );

            QUI.Groups.switchStatus(
                Btn.getAttribute( 'gid' )
            );
        },

        /**
         * event : status change of a group
         * if a group status is changed
         *
         * @param {QUI.classes.groups.Groups} Groups
         * @param {Object} ids - Group-IDs with status
         */
        $onSwitchStatus : function(Groups, ids)
        {
            var i, id, len, Btn, entry, status;

            var Grid = this.getGrid(),
                data = Grid.getData();

            for ( i = 0, len = data.length; i < len; i++ )
            {
                if ( typeof ids[ data[ i ].id ] === 'undefined' ) {
                    continue;
                }

                entry = data[ i ];

                status = ( ids[ data[ i ].id ] ).toInt();
                Btn    = QUI.Controls.getById( entry.status.data.quiid );

                // group is active
                if ( status == 1 )
                {
                    Btn.setAttribute( 'alt', this.getAttribute( 'deactive_text' ) );
                    Btn.setAttribute( 'icon', this.getAttribute( 'active_image' ) );
                    continue;
                }

                // group is deactive
                Btn.setAttribute( 'alt', this.getAttribute( 'active_text' ) );
                Btn.setAttribute( 'icon', this.getAttribute( 'deactive_image' ) );
            }
        },

        /**
         * event : group fresh
         * if a group is refreshed
         *
         * @param {QUI.classes.groups.Groups} Groups
         * @param {QUI.classes.groups.Group} Group
         */
        $onRefreshGroup : function(Groups, Group)
        {
            var i, len;

            var Grid = this.getGrid(),
                data = Grid.getData(),
                id   = Group.getId();

            for ( i = 0, len = data.length; i < len; i++ )
            {
                if ( data[ i ].id != id ) {
                    continue;
                }

                Grid.setDataByRow( i,  this.groupToData( Group ) );
            }
        },

        /**
         * event: group deletion
         * if a group is deleted
         *
         * @param {QUI.classes.groups.Groups} Groups
         * @param {Array} ids - Delete Group-IDs
         */
        $onDeleteGroup : function(Groups, ids)
        {
            var i, id, len;

            var Grid = this.getGrid(),
                data = Grid.getData(),
                _tmp = {};

            for ( i = 0, len = ids.length; i < len; i++ ) {
                _tmp[ ids[i] ] = true;
            }

            for ( i = 0, len = data.length; i < len; i++ )
            {
                id = data[ i ].id;

                if ( _tmp[ id ] )
                {
                    this.load();
                    break;
                }
            }
        },

        /**
         * Open all marked groups
         */
        $onButtonEditClick : function()
        {
            var Parent  = this.getParent(),
                Grid    = this.getGrid(),
                seldata = Grid.getSelectedData();

            if ( !seldata.length ) {
                return;
            }

            if ( seldata.length == 1 )
            {
                this.openGroup( seldata[ 0 ].id );
                return;
            }

            var i, len;

            if ( Parent.getType() === 'QUI.controls.desktop.Tasks' )
            {
                require([ 'controls/groups/Group' ], function(QUI_Group_Control)
                {
                    var Group, Task, TaskGroup;

                    TaskGroup = new QUI.controls.taskbar.Group();
                    Parent.appendTask( TaskGroup );

                    for ( i = 0, len = seldata.length; i < len; i++ )
                    {
                        Group = new QUI.controls.groups.Group( seldata[ i ].id );
                        Task  = Parent.instanceToTask( Group );

                        TaskGroup.appendChild( Task );
                    }

                    // TaskGroup.refresh( Task );
                    TaskGroup.click();
                });

                return;
            }

            for ( i = 0, len = seldata.length; i < len; i++ ) {
                this.openGroup( seldata[ i ].id );
            }
        },

        /**
         * Open deletion popup
         */
        $onButtonDelClick : function()
        {
            var i, len;

            var gids = [],
                data = this.getGrid().getSelectedData();

            for ( i = 0, len = data.length; i < len; i++ ) {
                gids.push( data[ i ].id );
            }

            if ( !gids.length ) {
                return;
            }

            QUI.Windows.create('submit', {
                name        : 'DeleteGroups',
                title       : 'Gruppen löschen',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                text        : 'Sie möchten folgende Gruppen löschen:<br /><br />'+ gids.join(', '),
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                information : 'Die Gruppen werden komplett aus dem System entfernt und können nicht wieder hergestellt werden',

                width  : 500,
                height : 150,
                gids   : gids,
                events :
                {
                    onSubmit : function(Win)
                    {
                        QUI.Groups.deleteGroups(
                            Win.getAttribute( 'gids' )
                        );
                    }
                }
            });
        }

    });
});