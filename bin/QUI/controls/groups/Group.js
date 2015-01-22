
/**
 * A group panel
 *
 * @module controls/groups/Group
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/desktop/Panel
 * @require controls/grid/Grid
 * @require Groups
 */

define('controls/groups/Group', [

    'qui/controls/desktop/Panel',
    'controls/grid/Grid',
    'Groups',
    'Ajax',
    'Editors',
    'Locale',
    'qui/controls/buttons/Button',
    'qui/utils/Form',
    'utils/Controls',

    'css!controls/groups/Group.css'

], function(Panel, Grid, Groups, Ajax, Editors, Locale, QUIButton, FormUtils, ControlUtils)
{
    "use strict";

    var lg = 'quiqqer/system';

    /**
     * @class controls/groups/Group
     *
     * @param {Number} gid - Group-ID
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : Panel,
        Type    : 'controls/groups/Group',

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
            this.$Group    = Groups.get( gid );
            this.$UserGrid = null;

            this.addEvents({
                'onCreate'  : this.$onCreate,
                'onDestroy' : this.$onDestroy,
                'onResize'  : this.$onResize
            });

            this.parent();
        },

        /**
         * Return the assigned group
         *
         * @return {Object} classes/groups/Group
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
            var self = this;

            require(['qui/controls/windows/Confirm'], function(Submit)
            {
                new Submit({
                    name     : 'DeleteUser'+ self.getGroup().getId(),
                    title    : Locale.get( lg, 'groups.group.delete.title' ),
                    icon     : 'icon-trash',
                    texticon : 'icon-trash',
                    text : Locale.get( lg, 'groups.group.delete.text', {
                        group : self.getGroup().getAttribute('name')
                    }),
                    information : Locale.get( lg, 'groups.group.delete.information' ),
                    width  : 500,
                    height : 150,
                    events :
                    {
                        onSubmit : function()
                        {
                            Groups.deleteGroups([
                                self.getGroup().getId()
                            ]);
                        }
                    }
                }).open();
            });
        },

        /**
         * Opens the group permissions
         */
        openPermissions : function()
        {
            var Parent = this.getParent(),
                Group  = this.getGroup();

            require(['controls/permissions/Panel'], function(PermPanel)
            {
                Parent.appendChild(
                    new PermPanel( null, Group )
                );
            });
        },

        /**
         * event : on create
         * Group panel content creation
         */
        $onCreate : function()
        {
            var self = this;

            this.$drawButtons();

            this.$drawCategories(function()
            {
                var Group = self.getGroup();

                Group.addEvents({
                    'onRefresh' : self.$onGroupRefresh
                });

                Groups.addEvents({
                    'onSwitchStatus' : self.$onGroupStatusChange,
                    'onActivate'     : self.$onGroupStatusChange,
                    'onDeactivate'   : self.$onGroupStatusChange,
                    'onDelete'       : self.$onGroupDelete
                });

                self.setAttribute( 'icon', 'icon-group' );

                if ( Group.getAttribute( 'title' ) === false )
                {
                    Group.load();
                    return;
                }

                self.$onGroupRefresh();

            });
        },

        /**
         * event: on panel destroying
         */
        $onDestroy : function()
        {
            this.getGroup().removeEvents({
                'refresh' : this.$onGroupRefresh
            });

            Groups.removeEvents({
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

                Locale.get( lg, 'groups.group.title', {
                    group : this.getGroup().getAttribute( 'name' )
                })
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
         * @param {classes/groups/Manager} Groups
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
         * @param {classes/groups/Manager} Groups
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
         * @method controls/groups/Group#$drawButtons
         */
        $drawButtons : function()
        {
            this.addButton({
                name      : 'groupSave',
                text      : Locale.get( lg, 'groups.group.btn.save' ),
                textimage : 'icon-save',
                events    : {
                    onClick : this.save
                }
            });

            this.addButton({
                name      : 'groupDelete',
                text      : Locale.get( lg, 'groups.group.btn.delete' ),
                textimage : 'icon-trash',
                events    : {
                    onClick : this.del
                }
            });

            // permissions
            new QUIButton({
                image  : 'icon-gears',
                alt    : Locale.get( lg, 'groups.group.btn.permissions.alt' ),
                title  : Locale.get( lg, 'groups.group.btn.permissions.title' ),
                styles : {
                    'float' : 'right'
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
         * @method controls/groups/Group#drawCategories
         *
         * @param {Function} onfinish - Callback function
         * @ignore
         */
        $drawCategories : function(onfinish)
        {
            this.Loader.show();

            Ajax.get('ajax_groups_panel_categories', function(result, Request)
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
         * @param {Object} Category - qui/controls/buttons/Button
         */
        $onCategoryLoad : function(Category)
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_groups_panel_category', function(result, Request)
            {
                var Form;

                var Category = Request.getAttribute( 'Category' ),
                    Group    = self.getGroup(),
                    Body     = self.getBody();

                Body.set(
                    'html',
                    '<form name="group-panel-'+ Group.getId() +'">'+ result +'</form>'
                );

                Form = Body.getElement( 'form' );

                ControlUtils.parse( Body );
                FormUtils.setDataToForm( Group.getAttributes(), Form );

                switch ( Category.getAttribute( 'name' ) )
                {
                    case 'settings':
                        self.$onCategorySettingsLoad();
                    break;

                    case 'users':
                        self.$onCategoryUsersLoad();
                    break;

                    default:
                        Category.fireEvent( 'onLoad', [ Category, self ] );
                }

                self.Loader.hide();

            }, {
                plugin   : Category.getAttribute('plugin'),
                tab      : Category.getAttribute('name'),
                gid      : this.getGroup().getId(),
                Category : Category
            });
        },

        /**
         * event: on set normal a category = unload a category
         */
        $onCategoryUnload : function()
        {
            var Content = this.getBody(),
                Frm     = Content.getElement( 'form' ),
                data    = FormUtils.getFormData( Frm );

            this.getGroup().setAttributes( data );
        },

        /**
         * event: on category click (settings)
         */
        $onCategorySettingsLoad : function()
        {
            // load the wysiwyg toolbars
            Editors.getToolbars(function(toolbars)
            {
                var i, len, Sel;

                var Content = this.getBody(),
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
            var Content = this.getBody(),
                GridCon = new Element('div');

            Content.set( 'html', '' );
            GridCon.inject( Content );

            this.$UserGrid = new Grid(GridCon, {
                columnModel : [{
                    header    : Locale.get( lg, 'status' ),
                    dataIndex : 'status',
                    dataType  : 'node',
                    width     : 50
                }, {
                    header    : Locale.get( lg, 'user_id' ),
                    dataIndex : 'id',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'username' ),
                    dataIndex : 'username',
                    dataType  : 'integer',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'email' ),
                    dataIndex : 'email',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'firstname' ),
                    dataIndex : 'firstname',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'lastname' ),
                    dataIndex : 'lastname',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'c_date' ),
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
         * @return {Object} this (controls/groups/Group)
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

            if ( typeof result.data === 'undefined' ) {
                return;
            }

            for ( var i = 0, len = result.data.length; i < len; i++ )
            {
                if ( result.data[ i ].active )
                {
                    result.data[ i ].status = new Element('div', {
                        'class' : 'icon-ok',
                        styles : {
                            margin : '5px 0 5px 12px'
                        }
                    });

                } else
                {
                    result.data[ i ].status = new Element('div', {
                        'class' : 'icon-remove',
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
});