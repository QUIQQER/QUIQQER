
/**
 * Groups manager panel
 *
 * @module controls/groups/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/desktop/Panel
 * @require Groups
 * @require Locale
 * @require controls/grid/Grid
 * @require utils/Controls
 * @require controls/groups/sitemap/Window
 * @require utils/Template
 * @require qui/controls/messages/Attention
 * @require qui/controls/windows/Prompt
 * @require qui/controls/windows/Confirm
 * @require qui/controls/buttons/Button
 * @require css!controls/groups/Panel.css
 */

define('controls/groups/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'Groups',
    'Locale',
    'controls/grid/Grid',
    'utils/Controls',
    'controls/groups/sitemap/Window',
    'utils/Template',
    'qui/controls/messages/Attention',
    'qui/controls/windows/Prompt',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',

    'css!controls/groups/Panel.css'

], function()
{
    "use strict";

    var lg = 'quiqqer/system';

    var QUI                = arguments[ 0 ],
        Panel              = arguments[ 1 ],
        Groups             = arguments[ 2 ],
        Locale             = arguments[ 3 ],
        Grid               = arguments[ 4 ],
        ControlUtils       = arguments[ 5 ],
        GroupSitemapWindow = arguments[ 6 ],
        Template           = arguments[ 7 ],
        Attention          = arguments[ 8 ],
        QUIPrompt          = arguments[ 9 ],
        QUIConfirm         = arguments[ 10 ],
        QUIButton          = arguments[ 11 ],
        QUISwitch          = arguments[ 12 ];

    /**
     * @class qui/controls/groups/Panel
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : Panel,
        Type    : 'controls/groups/Panel',

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
            active_text   : '', // (optional)
            deactive_text : '', // (optional)

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

            this.setAttributes({
                active_text   : Locale.get( lg, 'groups.panel.group.is.active' ),
                deactive_text : Locale.get( lg, 'groups.panel.group.is.deactive' )
            });

            var self = this;

            this.addEvent('onDestroy', function() {
                Groups.removeEvent( 'switchStatus', self.$onSwitchStatus );
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
                    onMousedown : function() {
                        self.search();
                    }
                },
                alt   : Locale.get( lg, 'groups.panel.btn.search' ),
                title : Locale.get( lg, 'groups.panel.btn.search' ),
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
                text : Locale.get( lg, 'groups.panel.btn.create' )
            });

            this.addButton({
                name   : 'groupEdit',
                events : {
                    onMousedown : this.$onButtonEditClick
                },
                text      : Locale.get( lg, 'groups.panel.btn.edit' ),
                disabled  : true,
                textimage : 'icon-pencil'
            });

            this.addButton({
                name   : 'groupDel',
                events : {
                    onMousedown : this.$onButtonDelClick
                },
                text      : Locale.get( lg, 'groups.panel.btn.delete' ),
                disabled  : true,
                textimage : 'fa fa-trash-o'
            });


            // create grid
            var Body = this.getContent();

            this.$GridContainer = new Element('div');
            this.$GridContainer.inject( Body );


            this.$Grid = new Grid(this.$GridContainer, {
                columnModel : [{
                    header    : Locale.get( lg, 'status' ),
                    dataIndex : 'status',
                    dataType  : 'QUI',
                    width     : 60
                }, {
                    header    : Locale.get( lg, 'group_id' ),
                    dataIndex : 'id',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'groupname' ),
                    dataIndex : 'name',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'groups.panel.grid.admin' ),
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
         * @param {Number} gid - Group-ID
         * @return {Object} this (controls/groups/Panel)
         */
        openGroup : function(gid)
        {
            require(['controls/groups/Group'], function(Group)
            {
                this.getParent().appendChild(
                    new Group( gid )
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
                Sheet = this.createSheet({
                    title : Locale.get( lg, 'groups.panel.search.title' )
                });

            Sheet.addEvent('onOpen', function(Sheet)
            {
                Template.get('groups_searchtpl', function(result)
                {
                    var i, len, Frm, Search;

                    var Body   = Sheet.getBody(),
                        fields = self.getAttribute('searchfields'),
                        search = self.getAttribute('search');

                    Body.set( 'html', result );
                    self.setAttribute( 'SearchSheet', Sheet );

                    ControlUtils.parse( Body );

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


                    Frm.elements.gid.checked  = false;
                    Frm.elements.name.checked = false;

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


                    // search button
                    new QUIButton({
                        textimage : 'icon-search',
                        text      : Locale.get( lg, 'groups.panel.search.btn.search' ),
                        events    :
                        {
                            onClick : function() {
                                self.execSearch( Sheet );
                            }
                        }
                    }).inject( Search, 'after' );

                    self.Loader.hide();
                });
            });

            Sheet.show();
        },

        /**
         * Execute the search
         *
         * @param {Object} Sheet - qui/desktop/panels/Sheet
         */
        execSearch : function(Sheet)
        {
            var fields = [],
                Frm    = Sheet.getBody().getElement('form');


            // check if one checkbox is active
            if ( !Frm.elements.gid.checked &&
                 !Frm.elements.name.checked )
            {
                Frm.elements.gid.checked  = true;
                Frm.elements.name.checked = true;
            }


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
                title  : Locale.get( lg, 'groups.panel.create.window.title' ),
                text   : Locale.get( lg, 'groups.panel.create.window.sitemap.text' ),
                events :
                {
                    // now we need a groupname
                    onSubmit : function(Win, result)
                    {
                        if ( !result.length ) {
                            return;
                        }

                        new QUIPrompt({
                            title  : Locale.get( lg, 'groups.panel.create.window.new.group.title' ),
                            icon   : 'icon-group',
                            height : 220,
                            width  : 450,
                            text   : Locale.get( lg, 'groups.panel.create.window.new.group.text' ),
                            pid    : result[ 0 ],
                            events :
                            {
                                onDrawEnd : function(Win) {
                                    Win.getBody().getElement( 'input' ).focus();
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
         * @param {Object} Group - controls/groups/Group
         * @return {Object}
         */
        groupToData : function(Group)
        {
            // defaults
            var data = {
                status  : false,
                id      : Group.getId(),
                name    : Group.getAttribute( 'name' ),
                admin   : Locale.get( lg, 'no' )
            };

            if ( Group.getAttribute( 'admin' ) ) {
                data.admin = Locale.get( lg, 'yes' );
            }

            //data.status = {
            //    status : Group.isActive(),
            //    value  : Group.getId(),
            //    gid    : Group.getId(),
            //    image  : Group.isActive() ?
            //                this.getAttribute( 'active_image' ) :
            //                this.getAttribute( 'deactive_image' ),
            //
            //    alt : Group.isActive() ?
            //                this.getAttribute( 'deactive_text' ) :
            //                this.getAttribute( 'active_text' ),
            //
            //    events : {
            //        onClick : this.$btnSwitchStatus
            //    }
            //};

            data.status = new QUISwitch({
                status : Group.isActive(),
                value  : Group.getId(),
                gid    : Group.getId(),
                title  : Group.isActive() ? this.getAttribute( 'active_text' ) : this.getAttribute( 'deactive_text' ),
                events : {
                    onChange : this.$btnSwitchStatus
                }
            });

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

            if ( "evt" in data ) {
                data.evt.stop();
            }
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

            this.getButtons( 'groupEdit' ).disable();
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

            var Message = Body.getElement( '.messages-message' );

            if ( Message ) {
                Message.setStyle( 'width', this.getBody().getSize().x - 40 );
            }

            this.getGrid().setWidth( Body.getSize().x - 40 );

            // resize switches
            var i, len, Control;
            var switches = Body.getElements('.qui-switch');

            for ( i = 0, len = switches.length; i < len; i++ )
            {
                Control = QUI.Controls.getById( switches[ i ].get('data-quiid') );

                if ( Control ) {
                    Control.resize();
                }
            }
        },

        /**
         * Load the groups to the grid
         */
        $loadGroups : function()
        {
            var self = this;

            this.Loader.show();

            this.setAttribute( 'title', Locale.get( lg, 'groups.panel.title') );
            this.setAttribute( 'icon', 'icon-refresh icon-spin' );
            this.refresh();

            if ( this.getAttribute( 'search' ) &&
                 !this.getBody().getElement( '.messages-message' ) )
            {
                new Attention({
                    message : Locale.get( lg, 'groups.panel.search.active.message' ),
                    events  :
                    {
                        onClick : function(Message)
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

            // search
            Groups.getList({
                field  : this.getAttribute( 'field' ),
                order  : this.getAttribute( 'order' ),
                limit  : this.getAttribute( 'limit' ),
                page   : this.getAttribute( 'page' ),
                search : this.getAttribute( 'search' ),
                searchSettings : this.getAttribute( 'searchfields' )
            }, function(result)
            {
                var i, len, data, admin;

                var Grid = self.getGrid();

                if ( !Grid )
                {
                    self.Loader.hide();
                    return;
                }

                data = result.data;

                for ( i = 0, len = data.length; i < len; i++ )
                {
                    admin = ( data[ i ].admin ).toInt();

                    data[ i ].active = ( data[i].active ).toInt();
                    data[ i ].admin  = Locale.get( lg, 'no' );

                    if ( admin ) {
                        data[ i ].admin = Locale.get( lg, 'yes' );
                    }

                    //data[i].status = {
                    //    status : data[i].active,
                    //    value  : data[i].id,
                    //    gid    : data[i].id,
                    //    image  : data[i].active ?
                    //                Panel.getAttribute( 'active_image' ) :
                    //                Panel.getAttribute( 'deactive_image' ),
                    //
                    //    alt : data[i].active ?
                    //                Panel.getAttribute( 'deactive_text' ) :
                    //                Panel.getAttribute( 'active_text' ),
                    //
                    //    events : {
                    //        onClick : Panel.$btnSwitchStatus
                    //    }
                    //};

                    data[ i ].status = new QUISwitch({
                        status : data[ i ].active,
                        value  : data[ i ].id,
                        gid    : data[ i ].id,
                        title  : data[ i ].active ?
                            self.getAttribute( 'active_text' ) :
                            self.getAttribute( 'deactive_text' ),
                        events : {
                            onChange : self.$btnSwitchStatus
                        }
                    });
                }

                Grid.setData( result );

                self.setAttribute( 'title', Locale.get( lg, 'groups.panel.title') );
                self.setAttribute( 'icon', 'icon-group' );
                self.refresh();

                self.Loader.hide();
            });
        },

        /**
         * execute a group status switch
         *
         * @param {Object} Switch - qui/controls/buttons/Switch
         */
        $btnSwitchStatus : function(Switch)
        {
            Groups.switchStatus( Switch.getAttribute( 'gid' ) );
        },

        /**
         * event : status change of a group
         * if a group status is changed
         *
         * @param {Object} Groups - classes/groups/Manager
         * @param {Object} ids - Group-IDs with status
         */
        $onSwitchStatus : function(Groups, ids)
        {
            var i, len, Status, entry, status;

            var Grid = this.getGrid(),
                data = Grid.getData();

            for ( i = 0, len = data.length; i < len; i++ )
            {
                if ( typeof ids[ data[ i ].id ] === 'undefined' ) {
                    continue;
                }

                entry = data[ i ];

                status = ( ids[ data[ i ].id ] ).toInt();
                Status = entry.status;

                // group is active
                if ( status == 1 )
                {
                    Status.setAttribute( 'title', this.getAttribute( 'active_text' ) );
                    continue;
                }

                // group is deactive
                Status.setAttribute( 'title', this.getAttribute( 'deactive_text' ) );
            }
        },

        /**
         * event : group fresh
         * if a group is refreshed
         *
         * @param {Object} Groups - classes/groups/Manager
         * @param {Object} Group - classes/groups/Group
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

                Grid.setDataByRow( i, this.groupToData( Group ) );
            }
        },

        /**
         * event: group deletion
         * if a group is deleted
         *
         * @param {classes/groups/Manager} Groups
         * @param {Array} ids - Delete Group-IDs
         */
        $onDeleteGroup : function(Groups, ids)
        {
            var i, id, len;

            var Grid = this.getGrid(),
                data = Grid.getData(),
                _tmp = {};

            for ( i = 0, len = ids.length; i < len; i++ ) {
                _tmp[ ids[ i ] ] = true;
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

            if ( Parent.getType() === 'qui/controls/desktop/Tasks' )
            {
                require([
                    'controls/groups/Group',
                    'qui/controls/taskbar/Group'
                ], function(GroupControl, QUITaskGroup)
                {
                    var Group, Task, TaskGroup;

                    TaskGroup = new QUITaskGroup();
                    Parent.appendTask( TaskGroup );

                    for ( i = 0, len = seldata.length; i < len; i++ )
                    {
                        Group = new GroupControl( seldata[ i ].id );
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

            new QUIConfirm({
                name        : 'DeleteGroups',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                title       : Locale.get( lg, 'groups.panel.delete.window.title' ),
                text        : Locale.get( lg, 'groups.panel.delete.window.text' ) +'<br /><br />'+ gids.join(', '),
                information : Locale.get( lg, 'groups.panel.delete.window.information' ),

                width  : 500,
                height : 150,
                gids   : gids,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Groups.deleteGroups(
                            Win.getAttribute( 'gids' )
                        );
                    }
                }
            }).open();
        }
    });
});