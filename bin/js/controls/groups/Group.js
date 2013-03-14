/**
 * A group panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/groups/Group
 * @package com.pcsg.qui.js.controls.groups
 * @namespace QUI.controls.groups
 *
 * @require controls/desktop/Panel
 * @require controls/grid/Grid
 * @require Groups
 */

define('controls/groups/Group', [

    'controls/desktop/Panel',
    'controls/grid/Grid',
    'Groups',

    'css!controls/groups/Group.css'

], function(Panel)
{
    QUI.namespace( 'controls.groups' );

    /**
     * @class QUI.controls.groups.Group
     *
     * @param {Integer} gid - Group-ID
     *
     * @memberof! <global>
     */
    QUI.controls.groups.Group = new Class({

        Implements : [ Panel ],
        Type       : 'QUI.controls.groups.Group',

        Binds : [
            'save',
            'del',
            'refreshUser',
            'openPermissions',

            '$onCreate',
            '$onDestroy',
            '$onResize',
            '$onCategoryLoad',
            '$onCategoryUnload',
            '$onGroupRefresh',
            '$onGroupStatusChange',
            '$onGroupDelete',
            '$onGroupGetUser'
        ],

        options : {
            'user-sort'  : 'DESC',
            'user-field' : 'id',
            'user-limit' : 20,
            'user-page'  : 1
        },

        initialize : function(gid)
        {
            this.$Group    = QUI.Groups.get( gid );
            this.$UserGrid = null;

            this.addEvents({
                'onCreate'  : this.$onCreate,
                'onDestroy' : this.$onDestroy,
                'onResize'  : this.$onResize
            });
        },

        /**
         * Return the assigned group
         *
         * @return {QUi.classes.groups.Group}
         */
        getGroup : function()
        {
            return this.$Group;
        },

        /**
         * Save the group
         */
        save : function()
        {
            this.$onCategoryUnload();
            this.getGroup().save();
        },

        /**
         * Delete the Group
         * Opens the delete dialog
         */
        del : function()
        {
            QUI.Windows.create('submit', {
                name        : 'DeleteUser'+ this.getGroup().getId(),
                title       : 'Möchten Sie die Gruppe wirklich löschen?',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                text        : 'Die Gruppe '+ this.getGroup().getAttribute('name') +' wirklich löschen?',
                information : 'Die Gruppe wird komplett aus dem System entfernt und ' +
                              'kann nicht wieder hergestellt werden',
                width  : 500,
                height : 150,
                Panel  : this,
                events :
                {
                    onSubmit : function(Win)
                    {
                        QUI.Groups.deleteGroups([
                            Win.getAttribute( 'Panel' ).getGroup().getId()
                        ]);
                    }
                }
            });
        },

        /**
         * Opens the group permissions
         */
        openPermissions : function()
        {
            var Parent = this.getParent(),
                Group  = this.getGroup();

            require([ 'controls/permissions/Panel' ], function(PermPanel)
            {
                Parent.appendChild(
                    new QUI.controls.permissions.Panel(
                        null,
                        Group
                    )
                );
            });
        },

        /**
         * event : on create
         * Group panel content creation
         */
        $onCreate : function()
        {
            this.$drawButtons();

            this.$drawCategories(function()
            {
                var Group = this.getGroup();

                Group.addEvents({
                    'onRefresh' : this.$onGroupRefresh
                });

                QUI.Groups.addEvents({
                    'onSwitchStatus' : this.$onGroupStatusChange,
                    'onActivate'     : this.$onGroupStatusChange,
                    'onDeactivate'   : this.$onGroupStatusChange,
                    'onDelete'       : this.$onGroupDelete
                });

                this.setAttribute( 'icon', URL_BIN_DIR +'16x16/group.png' );

                if ( Group.getAttribute( 'title' ) === false )
                {
                    Group.load();
                    return;
                }

                this.$onGroupRefresh();

            }.bind( this ));
        },

        /**
         * event: on panel destroying
         */
        $onDestroy : function()
        {
            this.getGroup().removeEvents({
                'refresh' : this.$onGroupRefresh
            });

            QUI.Groups.removeEvents({
                'switchStatus' : this.$onGroupStatusChange,
                'activate'     : this.$onGroupStatusChange,
                'deactivate'   : this.$onGroupStatusChange,
                'delete'       : this.$onGroupDelete
            });
        },

        /**
         * event : onresize
         * Resize the panel
         */
        $onResize : function()
        {

        },

        /**
         * event : on group refresh
         * if the group will be refreshed
         */
        $onGroupRefresh : function()
        {
            this.setAttribute(
                'title',
                'Gruppe: '+ this.getGroup().getAttribute( 'name' )
            );

            this.refresh();

            var Bar = this.getCategoryBar();

            if ( Bar.getActive() )
            {
                this.$onCategoryLoad( Bar.getActive() );
                return;
            }

            Bar.firstChild().click();

            // button bar refresh
            (function()
            {
                this.getButtonBar().setAttribute( 'width', '98%' );
                this.getButtonBar().resize();
            }).delay( 200, this );
        },

        /**
         * event: groups on delete
         * if one group deleted, check if the group is this group
         *
         * @param {QUI.classes.groups.Groups} Groups
         * @param {Array} ids - Array of group ids which have been deleted
         */
        $onGroupDelete : function(Groups, ids)
        {
            var id = this.getGroup().getId();

            for ( var i = 0, len = ids.length; i < len; i++ )
            {
                if ( ids[ i ] == id ) {
                    this.destroy();
                }
            }
        },

        /**
         * event: groups on status change
         * if one groups status change, check if the group is this group
         *
         * @param {QUI.classes.groups.Groups} Groups
         * @param {Object} groups - groups that change the status
         */
        $onGroupStatusChange : function(Groups, groups)
        {
            var id = this.getGroup().getId();

            for ( var gid in groups )
            {
                if ( gid != id ) {
                    continue;
                }

                if ( this.getActiveCategory() )
                {
                    this.$onCategoryLoad( this.getActiveCategory() );
                    return;
                }
            }
        },

        /**
         * Draw the panel action buttons
         *
         * @method QUI.controls.groups.Group#$drawButtons
         */
        $drawButtons : function()
        {
            this.addButton({
                name      : 'groupSave',
                text      : 'Änderungen speichern',
                textimage : URL_BIN_DIR +'16x16/save.png',
                events    : {
                    onClick : this.save
                }
            });

            this.addButton({
                name      : 'groupDelete',
                text      : 'Gruppe löschen',
                textimage : URL_BIN_DIR +'16x16/trashcan_empty.png',
                events    : {
                    onClick : this.del
                }
            });

            // permissions
            new QUI.controls.buttons.Button({
                image  : URL_BIN_DIR +'16x16/permissions.png',
                alt    : 'Gruppen Zugriffsrechte einstellen',
                title  : 'Gruppen Zugriffsrechte einstellen',
                styles : {
                    'float' : 'right',
                    margin  : 4
                },
                events : {
                    onClick : this.openPermissions
                }
            }).inject(
                this.getHeader()
            );
        },

        /**
         * Get the category buttons for the pannel
         *
         * @method QUI.controls.groups.Group#drawCategories
         *
         * @param {Function} onfinish - Callback function
         * @ignore
         */
        $drawCategories : function(onfinish)
        {
            this.Loader.show();

            QUI.Ajax.get('ajax_groups_panel_categories', function(result, Request)
            {
                var Panel = Request.getAttribute('Panel');

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    result[i].events = {
                        onActive : Panel.$onCategoryLoad,
                        onNormal : Panel.$onCategoryUnload
                    };

                    Panel.addCategory( result[i] );
                }

                Request.getAttribute( 'onfinish' )( result, Request );
            }, {
                gid      : this.getGroup().getId(),
                Panel    : this,
                onfinish : onfinish
            });
        },

        /**
         * event: on category click
         *
         * @param {QUI.controls.buttons.Button} Category
         */
        $onCategoryLoad : function(Category)
        {
            this.Loader.show();

            QUI.Ajax.get('ajax_groups_panel_category', function(result, Request)
            {
                var Form;

                var Panel    = Request.getAttribute( 'Panel' ),
                    Category = Request.getAttribute( 'Category' ),
                    Group    = Panel.getGroup(),
                    Body     = Panel.getBody();

                Body.set(
                    'html',
                    '<form name="group-panel-'+ Group.getId() +'">'+ result +'</form>'
                );

                Form = Body.getElement( 'form' );

                QUI.controls.Utils.parse( Body );
                QUI.lib.Utils.setDataToForm( Group.getAttributes(), Form );

                switch ( Category.getAttribute( 'name' ) )
                {
                    case 'settings':
                        Panel.$onCategorySettingsLoad();
                    break;

                    case 'users':
                        Panel.$onCategoryUsersLoad();
                    break;


                    default:
                        Category.fireEvent( 'onLoad', [ Category, Panel ] );
                }

                Panel.Loader.hide();

            }, {
                plugin   : Category.getAttribute('plugin'),
                tab      : Category.getAttribute('name'),
                gid      : this.getGroup().getId(),
                Category : Category,
                Panel    : this
            });
        },

        /**
         * event: on set normal a category = unload a category
         */
        $onCategoryUnload : function()
        {
            var Content = this.getBody(),
                Frm     = Content.getElement( 'form' ),
                data    = QUI.lib.Utils.getFormData( Frm );

            this.getGroup().setAttributes( data );
        },

        /**
         * event: on category click (settings)
         */
        $onCategorySettingsLoad : function()
        {
            // load the wysiwyg toolbars
            QUI.Editors.getToolbars(function(toolbars)
            {
                var i, len, Sel;

                var Group   = this.getGroup(),
                    Content = this.getBody(),
                    Toolbar = Content.getElement( '.toolbar-listing' );

                if ( !Toolbar ) {
                    return;
                }

                Toolbar.set( 'html', '' );

                Sel = new Element('select', {
                    name : 'toolbar'
                });

                for ( i = 0, len = toolbars.length; i < len; i++ )
                {
                    new Element('option', {
                        value : toolbars[ i ],
                        html  : toolbars[ i ].replace( '.xml', '' )
                    }).inject( Sel );
                }

                Sel.inject( Toolbar );
                Sel.value = this.getAttribute('toolbar');

            }.bind( this ));
        },

        /**
         * event: on category click (user listing)
         */
        $onCategoryUsersLoad : function()
        {
            var Group   = this.getGroup(),
                Content = this.getBody(),

                GridCon = new Element('div');

            Content.set( 'html', '' );
            GridCon.inject( Content );

            this.$UserGrid = new QUI.controls.grid.Grid(GridCon, {
                columnModel : [{
                    header    : 'Status',
                    dataIndex : 'status',
                    dataType  : 'node',
                    width     : 50
                }, {
                    header    : 'Benutzer-ID',
                    dataIndex : 'id',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : 'Benutzername',
                    dataIndex : 'username',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : 'E-Mail',
                    dataIndex : 'email',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Vorname',
                    dataIndex : 'firstname',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Nachname',
                    dataIndex : 'lastname',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Erstellungsdatum',
                    dataIndex : 'regdate',
                    dataType  : 'date',
                    width     : 150
                }],
                pagination : true,
                filterInput: true,
                perPage    : this.getAttribute( 'user-limit' ),
                page       : this.getAttribute( 'user-page' ),
                sortOn     : this.getAttribute( 'user-sort' ),
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : Content.getSize().x,
                height     : Content.getSize().y - 45,
                onrefresh  : this.refreshUser,

                alternaterows     : true,
                resizeColumns     : true,
                selectable        : true,
                multipleSelection : true,
                resizeHeaderOnly  : true
            });

            this.$UserGrid.addEvents({
                onDblClick : function(data)
                {
                    require([ 'controls/users/User' ], function(QUI_User)
                    {
                        this.getParent().appendChild(
                            new QUI_User(
                                data.target.getDataByRow( data.row ).id
                            )
                        );
                    }.bind( this ));
                }.bind( this )
            });

            GridCon.setStyles({
                margin: 0
            });

            this.$UserGrid.refresh();
        },

        /**
         * Refresh the user grid
         *
         * @return {this}
         */
        refreshUser : function()
        {
            if ( typeof this.$UserGrid === 'undefined' ) {
                return this;
            }

            this.Loader.show();

            var Grid = this.$UserGrid;

            this.setAttribute( 'user-field', Grid.getAttribute('sortOn') );
            this.setAttribute( 'user-order', Grid.getAttribute('sortBy') );
            this.setAttribute( 'user-limit', Grid.getAttribute( 'perPage' ) );
            this.setAttribute( 'user-page', Grid.getAttribute( 'page' ) );

            this.getGroup().getUsers(this.$onGroupGetUser, {
                limit : this.getAttribute( 'user-limit' ),
                page  : this.getAttribute( 'user-page' ),
                field : this.getAttribute( 'user-field' ),
                order : this.getAttribute( 'user-order' )
            });

            return this;
        },

        /**
         * if users return for the user grid
         *
         * @param {Array} result - user list
         */
        $onGroupGetUser : function(result)
        {
            if ( typeof this.$UserGrid === 'undefined' ) {
                return;
            }

            for ( var i = 0, len = result.data.length; i < len; i++ )
            {
                if ( result.data[ i ].active )
                {
                    result.data[ i ].status = new Element('img', {
                        src    : URL_BIN_DIR +'16x16/apply.png',
                        styles : {
                            margin : '5px 0 5px 12px'
                        }
                    });

                } else
                {
                    result.data[ i ].status = new Element('img', {
                        src    : URL_BIN_DIR +'16x16/cancel.png',
                        styles : {
                            margin : '5px 0 5px 12px'
                        }
                    });
                }
            }

            this.$UserGrid.setData( result );
            this.Loader.hide();
        }
    });


    return QUI.controls.groups.Group;
});