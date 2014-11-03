
/**
 * User Manager (View)
 *
 * @module controls/users/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require controls/grid/Grid
 * @require Users
 * @require qui/controls/messages/Attention
 * @require qui/controls/windows/Confirm
 * @require qui/controls/windows/Prompt
 * @require qui/controls/buttons/Button
 * @require utils/Template
 * @require utils/Controls
 * @require Locale
 * @require css!controls/users/Panel.css
 */

define([

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'controls/grid/Grid',
    'Users',
    'qui/controls/messages/Attention',
    'qui/controls/windows/Confirm',
    'qui/controls/windows/Prompt',
    'qui/controls/buttons/Button',
    'utils/Template',
    'utils/Controls',
    'Locale',

    'css!controls/users/Panel.css'

], function()
{
    "use strict";

    var lg = 'quiqqer/system';

    var QUI          = arguments[ 0 ],
        Panel        = arguments[ 1 ],
        Grid         = arguments[ 2 ],
        Users        = arguments[ 3 ],
        Attention    = arguments[ 4 ],
        QUIConfirm   = arguments[ 5 ],
        QUIPrompt    = arguments[ 6 ],
        QUIButton    = arguments[ 7 ],
        Template     = arguments[ 8 ],
        ControlUtils = arguments[ 9 ],
        Locale       = arguments[ 10 ];

    /**
     * @class controls/users/Panel
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : Panel,
        Type    : 'controls/users/Panel',

        Binds : [
            '$onCreate',
            '$onResize',
            '$onSwitchStatus',
            '$onDeleteUser',
            '$onUserRefresh',
            '$onButtonEditClick',
            '$onButtonDelClick',
            '$gridClick',
            '$gridDblClick',
            '$gridBlur',

            'search',
            'createUser'
        ],

        initialize : function(options)
        {
            this.$uid = String.uniqueID();

            // defaults
            this.setAttributes({
                field  : 'username',
                order  : 'ASC',
                limit  : 20,
                page   : 1,
                search : false,
                searchSettings : {},
                tabbar : false
            });

            this.parent( options );

            this.$Grid      = null;
            this.$Container = null;

            this.addEvent( 'onCreate', this.$onCreate );
            this.addEvent( 'onResize', this.$onResize );

            Users.addEvents({
                onSwitchStatus : this.$onSwitchStatus,
                onDelete       : this.$onDeleteUser,
                onRefresh      : this.$onUserRefresh,
                onSave         : this.$onUserRefresh
            });

            this.addEvent('onDestroy', function()
            {
                Users.removeEvents({
                    onSwitchStatus : this.$onSwitchStatus,
                    onDelete       : this.$onDeleteUser,
                    onRefresh      : this.$onUserRefresh,
                    onSave         : this.$onUserRefresh
                });
            }.bind( this ));


            this.active_image = 'icon-ok';
            this.active_text  = Locale.get( lg, 'users.panel.user.is.active' );

            this.deactive_image = 'icon-remove';
            this.deactive_text  = Locale.get( lg, 'users.panel.user.is.deactive' );
        },

        /**
         * Return the user grid
         *
         * @return {controls/grid/Grid|null}
         */
        getGrid : function()
        {
            return this.$Grid;
        },

        /**
         * create the user panel
         */
        $onCreate : function()
        {
            this.addButton({
                name   : 'userSearch',
                Users  : this,
                alt    : Locale.get( lg, 'users.panel.btn.search.alt' ),
                title  : Locale.get( lg, 'users.panel.btn.search.title' ),
                image  : 'icon-search',
                events : {
                    onClick: this.search
                }
            });

            this.addButton({
                name : 'usep',
                type : 'seperator'
            });

            this.addButton({
                name   : 'userNew',
                Users  : this,
                events : {
                    onClick : this.createUser
                },
                text : Locale.get( lg, 'users.panel.btn.create' )
            });

            this.addButton({
                name      : 'userEdit',
                Users     : this,
                text      : Locale.get( lg, 'users.panel.btn.edit' ),
                disabled  : true,
                textimage : 'icon-pencil',
                events    : {
                    onMousedown : this.$onButtonEditClick
                }
            });

            this.addButton({
                name      : 'userDel',
                Users     : this,
                text      : Locale.get( lg, 'users.panel.btn.delete' ),
                disabled  : true,
                textimage : 'icon-trash',
                events    : {
                    onMousedown : this.$onButtonDelClick
                }
            });

            // create grid
            var Body = this.getBody();

            this.$Container = new Element('div');
            this.$Container.inject( Body );

            this.$Grid = new Grid(this.$Container, {
                columnModel : [{
                    header    : Locale.get( lg, 'status' ),
                    dataIndex : 'activebtn',
                    dataType  : 'button',
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
                    header    : Locale.get( lg, 'group' ),
                    dataIndex : 'usergroup',
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
                perPage    : this.getAttribute('limit'),
                page       : this.getAttribute('page'),
                sortOn     : this.getAttribute('field'),
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : Body.getSize().x - 40,
                height     : Body.getSize().y - 40,
                onrefresh  : function(me)
                {
                    var options = me.options;

                    this.setAttribute( 'field', options.sortOn );
                    this.setAttribute( 'order', options.sortBy );
                    this.setAttribute( 'limit', options.perPage );
                    this.setAttribute( 'page', options.page );

                    this.load();

                }.bind( this ),

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
                this.getButtonBar().setAttribute( 'width', '98%' );
                this.getButtonBar().resize();
            }).delay( 200, this );

            // start and list the users
            this.load();
        },

        /**
         * Load the users with the settings
         */
        load : function()
        {
            this.Loader.show();
            this.$loadUsers();
        },

        /**
         * create a user panel
         *
         * @param {Integer} uid - User-ID
         * @return {this}
         */
        openUser : function(uid)
        {
            var self = this;

            require(['controls/users/User'], function(User) {
                self.getParent().appendChild( new User( uid ) );
            });

            return this;
        },

        /**
         * Opens the users search settings
         */
        search : function()
        {
            this.Loader.show();

            var self  = this,
                Sheet = this.createSheet({
                    title : Locale.get( lg, 'users.panel.search.title' ),
                    icon  : 'icon-search'
                });

            Sheet.addEvent('onOpen', function(Sheet)
            {
                Template.get('users_searchtpl', function(result, Request)
                {
                    var i, len, inputs, new_id, Frm, Search, Label;

                    var Body     = Sheet.getBody(),
                        settings = self.getAttribute('searchSettings'),

                        values = Object.merge(
                            {},
                            settings.filter,
                            settings.fields
                        );

                    Body.set( 'html', result );


                    Frm    = Body.getElement('form');
                    Search = Frm.elements.search;

                    Search.addEvent('keyup', function(event)
                    {
                        if ( event.key === 'enter' ) {
                            self.execSearch( Sheet );
                        }
                    });

                    if ( values.id ) {
                        values.uid = values.id;
                    }

                    // elements
                    inputs = Frm.elements;

                    for ( i = 0, len = inputs.length; i < len; i++ )
                    {
                        new_id = inputs[i].name + self.getId();

                        inputs[ i ].set('id', new_id);

                        if ( values[ inputs[ i ].name ] )
                        {
                            if ( inputs[ i ].type == 'checkbox' )
                            {
                                inputs[ i ].checked = true;
                            } else
                            {
                                inputs[ i ].value = values[ inputs[ i ].name ];
                            }

                        } else
                        {
                            if ( inputs[ i ].type == 'checkbox' )
                            {
                                inputs[ i ].checked = false;
                            } else
                            {
                                inputs[ i ].value = '';
                            }
                        }

                        Label = Frm.getElement( 'label[for="'+ inputs[ i ].name +'"]' );

                        if ( Label ) {
                            Label.set('for', new_id);
                        }
                    }

                    Search.value = settings.userSearchString || '';
                    Search.focus();


                    ControlUtils.parse( Body );

                    // search button
                    new QUIButton({
                        textimage : 'icon-search',
                        text : Locale.get( lg, 'users.panel.search.btn.start' ),
                        events :
                        {
                            onClick : function(Btn) {
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
         * @param {qui/desktop/panels/Sheet}
         */
        execSearch : function(Sheet)
        {
            var Frm = Sheet.getBody().getElement('form');

            this.setAttribute( 'search', true );

            // check if one checkbox is active
            if ( !Frm.elements.uid.checked &&
                 !Frm.elements.username.checked &&
                 !Frm.elements.email.checked &&
                 !Frm.elements.firstname.checked &&
                 !Frm.elements.lastname.checked )
            {
                Frm.elements.uid.checked      = true;
                Frm.elements.username.checked = true;
            }


            this.setAttribute( 'searchSettings', {
                userSearchString : Frm.elements.search.value,
                fields : {
                    id        : Frm.elements.uid.checked,
                    username  : Frm.elements.username.checked,
                    email     : Frm.elements.email.checked,
                    firstname : Frm.elements.firstname.checked,
                    lastname  : Frm.elements.lastname.checked
                },
                filter : {
                    filter_status        : Frm.elements.filter_status.value,
                    filter_group         : Frm.elements.filter_group.value,
                    filter_regdate_first : Frm.elements.filter_regdate_first.value,
                    filter_regdate_last  : Frm.elements.filter_regdate_last.value
                }
            });

            Sheet.hide();

            this.$loadUsers();
        },

        /**
         * Open the user create dialog
         */
        createUser : function()
        {
            var self = this;

            new QUIPrompt({
                name        : 'CreateUser',
                title       : Locale.get( lg, 'users.panel.create.window.title' ),
                icon        : 'icon-user',
                text        : Locale.get( lg, 'users.panel.create.window.text' ),
                information : Locale.get( lg, 'users.panel.create.window.information' ),

                width  : 500,
                height : 150,
                Panel  : this,

                check  : function(Win)
                {
                    Win.Loader.show();

                    Users.existsUsername(
                        Win.getValue(),
                        function(result, Request)
                        {
                            // Benutzer existiert schon
                            if ( result === true )
                            {
                                QUI.getMessageHandler(function(MH)
                                {
                                    MH.addAttention(
                                        Locale.get( lg, 'exception.create.user.exists' )
                                    );
                                });

                                Win.Loader.hide();
                                return;
                            }

                            Win.fireEvent( 'onsubmit', [ Win.getValue(), Win ] );
                            Win.close();
                        }
                    );

                    return false;
                },

                events :
                {
                    onsubmit : function(value, Win)
                    {
                        Users.createUser(value, function(result, Request) {
                            self.openUser( result );
                        });
                    }
                }
            }).open();
        },


        /**
         * onclick on the grid
         */
        $gridClick : function(data)
        {
            var len    = data.target.selected.length,
                Edit   = this.getButtons( 'userEdit' ),
                Delete = this.getButtons( 'userDel' );

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
         */
        $gridDblClick : function(data)
        {
            this.openUser(
                data.target.getDataByRow( data.row ).id
            );
        },

        /**
         * onblur on the grid
         */
        $gridBlur : function(data)
        {
            this.getGrid().unselectAll();
            this.getGrid().removeSections();

            this.getButtons( 'userEdit' ).disable();
            this.getButtons( 'userDel' ).disable();
        },

        /**
         * Resize the users panel
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
                this.getGrid().setHeight( Body.getSize().y - 120 );

            } else
            {
                this.getGrid().setHeight( Body.getSize().y - 40 );
            }

            var Message = Body.getElement( '.messages-message' );

            if ( Message ) {
                Message.setStyle( 'width', this.getBody().getSize().x - 40 );
            }

            this.getGrid().setWidth( Body.getSize().x - 40 );
        },

        /**
         * Load the users to the grid
         */
        $loadUsers : function()
        {
            var self = this;

            this.Loader.show();

            this.setAttribute( 'title', Locale.get( lg, 'users.panel.title' ) );
            this.setAttribute( 'icon', 'icon-refresh icon-spin' );
            this.refresh();

            if ( this.getAttribute( 'search' ) &&
                 !this.getBody().getElement( '.messages-message' ) )
            {
                var Msg = new Attention({
                    Users   : this,
                    message : Locale.get( lg, 'users.panel.search.info' ),
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
                }).inject( this.getBody(), 'top' );
            }

            this.resize();


            Users.getList({
                field : this.getAttribute( 'field' ),
                order : this.getAttribute( 'order' ),
                limit : this.getAttribute( 'limit' ),
                page  : this.getAttribute( 'page' ),
                search         : this.getAttribute( 'search' ),
                searchSettings : this.getAttribute( 'searchSettings' )

            }, function(result, Request)
            {
                var Grid = self.getGrid();

                if ( !Grid )
                {
                    self.Loader.hide();
                    return;
                }

                self.$parseDataForGrid( result.data );

                Grid.setData( result );

                self.setAttribute( 'title', Locale.get( lg, 'users.panel.title' ) );
                self.setAttribute( 'icon', 'icon-user' );
                self.refresh();

                self.Loader.hide();
            });
        },

        /**
         * execute a user user status switch
         */
        $btnSwitchStatus : function(Btn)
        {
            Users.switchStatus(
                Btn.getAttribute( 'uid' )
            );
        },

        /**
         * if a user status is changed
         *
         * @param {classes/users/Users} Users
         * @param {Object} ids - User-IDs
         */
        $onSwitchStatus : function(Users, ids)
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

                status = ( entry.active ).toInt();
                Btn    = QUI.Controls.getById( entry.activebtn.data.quiid );

                // user is active
                if ( ids[ data[ i ].id ] === 1 )
                {
                    Btn.setAttribute( 'alt', this.active_text );
                    Btn.setAttribute( 'image', this.active_image );
                    continue;
                }

                // user is deactive
                Btn.setAttribute( 'alt', this.deactive_text );
                Btn.setAttribute( 'image', this.deactive_image );
            }
        },

        /**
         * if a user status is changed
         *
         * @param {classes/users/Users} Users
         * @param {classes/users/User} User
         */
        $onUserRefresh : function(Users, User)
        {
            var Grid = this.getGrid(),
                data = Grid.getData(),
                id   = User.getId();

            for ( var i = 0, len = data.length; i < len; i++ )
            {
                if ( data[ i ].id != id ) {
                    continue;
                }

                Grid.setDataByRow( i, this.userToGridData( User ) );
            }
        },

        /**
         * if a user is deleted
         */
        $onDeleteUser : function(Users, ids)
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
         * Open all marked users
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
                this.openUser( seldata[ 0 ].id );
                return;
            }

            var i, len;

            if ( Parent.getType() === 'qui/controls/desktop/Tasks' )
            {
                require([
                    'controls/users/User',
                    'qui/controls/taskbar/Group'
                ], function(UserPanel, QUITaskGroup)
                {
                    var User, Task, TaskGroup;

                    TaskGroup = new QUITaskGroup();
                    Parent.appendTask( TaskGroup );

                    for ( i = 0, len = seldata.length; i < len; i++ )
                    {
                        User = new UserPanel( seldata[ i ].id );
                        Task = Parent.instanceToTask( User );

                        TaskGroup.appendChild( Task );
                    }

                    // TaskGroup.refresh( Task );
                    TaskGroup.click();
                });

                return;
            }

            for ( i = 0, len = seldata.length; i < len; i++ ) {
                this.openUser( seldata[ i ].id );
            }
        },

        /**
         * Open deletion popup
         */
        $onButtonDelClick : function()
        {
            var i, len;

            var uids = [],
                data = this.getGrid().getSelectedData();

            for ( i = 0, len = data.length; i < len; i++ ) {
                uids.push( data[ i ].id );
            }

            if ( !uids.length ) {
                return;
            }

            new QUIConfirm({
                name  : 'DeleteUsers',
                title : Locale.get( lg, 'users.panel.delete.window.title' ),
                icon  : 'icon-trashcan',
                text  : Locale.get( lg, 'users.panel.delete.window.text', {
                    userids : uids.join(', ')
                }),
                texticon    : 'icon-trashcan',
                information : Locale.get( lg, 'users.panel.delete.window.information' ),

                width  : 500,
                height : 150,
                uids   : uids,
                events :
                {
                    onSubmit : function(Win)
                    {
                        require(['Users'], function(Users)
                        {
                            Users.deleteUsers(
                                Win.getAttribute( 'uids' ),
                                function() {
                                    Win.close();
                                }
                            );
                        });
                    }
                }
            }).open();
        },

        /**
         * Parse the Ajax data for the grid
         *
         * @param {Array} data
         * @return {Array}
         */
        $parseDataForGrid : function(data)
        {
            for ( var i = 0, len = data.length; i < len; i++ )
            {
                data[i].active    = ( data[i].active ).toInt();
                data[i].usergroup = data[i].usergroup || '';

                if ( data[i].active == -1 ) {
                    continue;
                }

                data[i].activebtn = {
                    status   : data[i].active,
                    value    : data[i].id,
                    uid      : data[i].id,
                    username : data[i].username,
                    image    : data[i].active ? this.active_image  : this.deactive_image,
                    alt      : data[i].active ? this.active_text : this.deactive_text,
                    events : {
                        onClick : this.$btnSwitchStatus
                    }
                };
            }

            return data;
        },

        /**
         * Parse the attributes to grid data entry
         *
         * @param {classes/users/User} User
         * @return {Object}
         */
        userToGridData : function(User)
        {
            var active = ( User.isActive() ).toInt(),
                id     = User.getId(),
                result = User.getAttributes();

            result.usergroup = result.usergroup || '';

            if ( active != -1 )
            {
                result.activebtn = {
                    status   : active,
                    value    : id,
                    uid      : id,
                    username : User.getName(),
                    image    : active ? this.active_image  : this.deactive_image,
                    alt      : active ? this.active_text : this.deactive_text,
                    events : {
                        onClick : this.$btnSwitchStatus
                    }
                };
            }

            return result;
        }
    });
});
