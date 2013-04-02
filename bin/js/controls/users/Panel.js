/**
 * User Manager (View)
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/users/Panel
 * @package com.pcsg.qui.js.controls.users
 * @namespace QUI.controls.users
 */

define('controls/users/Panel', [

    'controls/desktop/Panel',
    'classes/users/Users',
    'controls/grid/Grid',
    'Users',
    'controls/Utils',
    'classes/messages',

    'css!controls/users/Panel.css'

], function(Panel)
{
    "use strict";

    QUI.namespace( 'controls.users' );

    /**
     * @class QUI.controls.users.Panel
     *
     * @memberof! <global>
     */
    QUI.controls.users.Panel = new Class({

        Extends : Panel,
        Type    : 'QUI.controls.users.Panel',

        Binds : [
            '$onCreate',
            '$onResize',
            '$onSwitchStatus',
            '$onDeleteUser',
            '$onUserRefresh',
            '$onButtonnEditClick',
            '$onButtonnDelClick',
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

            QUI.Users.addEvents({
                onSwitchStatus : this.$onSwitchStatus,
                onDelete       : this.$onDeleteUser,
                onRefresh      : this.$onUserRefresh,
                onSave         : this.$onUserRefresh
            });


            this.addEvent('onDestroy', function()
            {
                QUI.Users.removeEvents({
                    onSwitchStatus : this.$onSwitchStatus,
                    onDelete       : this.$onDeleteUser,
                    onRefresh      : this.$onUserRefresh,
                    onSave         : this.$onUserRefresh
                });
            }.bind( this ));
        },

        /**
         * Return the user grid
         *
         * @return {QUI.controls.grid.Grid|null}
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
                alt    : 'Benutzer suchen',
                title  : 'Benutzer suchen',
                image  : URL_BIN_DIR +'16x16/search.png',
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
                text      : 'Neuen Benutzer anlegen',
                textimage : URL_BIN_DIR +'16x16/new.png'
            });

            this.addButton({
                name      : 'userEdit',
                Users     : this,
                text      : 'Benutzer bearbeiten',
                disabled  : true,
                textimage : URL_BIN_DIR +'16x16/edit.png',
                events    : {
                    onMousedown : this.$onButtonnEditClick
                }
            });

            this.addButton({
                name      : 'userDel',
                Users     : this,
                text      : 'Benutzer löschen',
                disabled  : true,
                textimage :  URL_BIN_DIR +'16x16/trashcan_full.png',
                events    : {
                    onMousedown : this.$onButtonnDelClick
                }
            });

            // create grid
            var Body = this.getBody();

            this.$Container = new Element('div');
            this.$Container.inject( Body );

            this.$Grid = new QUI.controls.grid.Grid(this.$Container, {
                columnModel : [{
                    header    : 'Status',
                    dataIndex : 'activebtn',
                    dataType  : 'button',
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
                    header    : 'Gruppe',
                    dataIndex : 'usergroup',
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
            require([ 'controls/users/User' ], function(QUI_User)
            {
                this.getParent().appendChild( new QUI_User( uid ) );
            }.bind( this ));

            return this;
        },

        /**
         * Opens the users search settings
         */
        search : function()
        {
            this.Loader.show();

            var Sheet = this.createSheet();

            Sheet.addEvent('onOpen', function(Sheet)
            {
                QUI.Template.get('users_searchtpl', function(result, Request)
                {
                    var i, len, inputs, new_id, Frm, Search, Label;

                    var Sheet    = Request.getAttribute('Sheet'),
                        Users    = Request.getAttribute('Users'),
                        Body     = Sheet.getBody(),
                        settings = Users.getAttribute('searchSettings'),

                        values   = Object.merge(
                            {},
                            settings.filter,
                            settings.fields
                        );

                    Body.set( 'html', result );
                    Users.setAttribute( 'SearchSheet', Sheet );

                    // parse controls
                    QUI.controls.Utils.parse( Body );

                    Frm    = Body.getElement('form');
                    Search = Frm.elements.search;

                    Search.addEvent('keyup', function(event)
                    {
                        if ( event.key === 'enter' ) {
                            this.execSearch( this.getAttribute('SearchSheet') );
                        }
                    }.bind( Users ));

                    Search.value = settings.userSearchString || '';
                    Search.focus();

                    // elements
                    inputs = Frm.elements;

                    for ( i = 0, len = inputs.length; i < len; i++ )
                    {
                        new_id = inputs[i].name + Users.getId();

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
                        }

                        //if ( inputs[ i ].hasClass( 'date' ) ) {
                            //QUI.lib.Controls.Calendar( inputs[ i ] );
                        //}

                        Label = Frm.getElement( 'label[for="'+ inputs[ i ].name +'"]' );

                        if ( Label ) {
                            Label.set('for', new_id);
                        }
                    }

                    // search button
                    new QUI.controls.buttons.Button({
                        image  : URL_BIN_DIR +'16x16/search.png',
                        alt    : 'Suche starten ...',
                        title  : 'Suche starten ...',
                        Sheet  : Sheet,
                        Users  : Users,
                        events :
                        {
                            onClick : function(Btn)
                            {
                                Btn.getAttribute('Users').execSearch(
                                    Btn.getAttribute('Sheet')
                                );
                            }
                        }
                    });

                    Users.Loader.hide();
                }, {
                    Users : this,
                    Sheet : Sheet
                });

            }.bind( this ));

            Sheet.show();
        },

        /**
         * Execute the search
         *
         * @param {QUI.desktop.panels.Sheet}
         */
        execSearch : function(Sheet)
        {
            var Frm = Sheet.getBody().getElement('form');

            this.setAttribute( 'search', true );
            this.setAttribute( 'searchSettings', {

                userSearchString : Frm.elements.search.value,

                fields : {
                    uid       : Frm.elements.uid.checked,
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
            QUI.Windows.create('prompt', {
                name        : 'CreateUser',
                title       : 'Neuen Benutzer anlegen',
                icon        : URL_BIN_DIR +'16x16/new.png',
                text        : 'Neuer Benutzername:',
                information : 'Geben Sie einen neuen Benutzernamen an. Der Benutzer wird inaktiv angelegt.',

                width  : 500,
                height : 150,
                Panel  : this,

                check  : function(Win)
                {
                    Win.Loader.show();

                    QUI.Users.existsUsername(
                        Win.getValue(),
                        function(result, Request)
                        {
                            var Win = Request.getAttribute('Win');

                            // Benutzer existiert schon
                            if ( result === true )
                            {
                                QUI.MH.addAttention(
                                    'Der Benutzername existiert schon.' +
                                    'Bitte geben Sie einen anderen Benutzernamen an.'
                                );

                                Win.Loader.hide();
                                return;
                            }

                            Win.fireEvent( 'onsubmit', [ Win.getValue(), Win ] );
                            Win.close();
                        }, {
                            Win : Win
                        }
                    );

                    return false;
                },

                events :
                {
                    onsubmit : function(value, Win)
                    {
                        QUI.Users.createUser(value, function(result, Request)
                        {
                            this.openUser( result );

                        }.bind( Win.getAttribute( 'Panel' ) ) );
                    }
                }
            });
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

            this.getButtons( 'userEdit' ).disable(),
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
         * Load the users to the grid
         */
        $loadUsers : function()
        {
            this.Loader.show();

            this.setAttribute( 'title', 'Benutzerverwaltung' );
            this.setAttribute( 'icon', URL_BIN_DIR +'images/loader.gif' );
            this.refresh();

            if ( this.getAttribute( 'search' ) &&
                 !this.getBody().getElement( '.message' ) )
            {
                var Msg = new QUI.classes.messages.Attention({
                    Users   : this,
                    message : 'Sucheparameter sind aktiviert. '+
                              'Klicken Sie hier um die Suche zu beenden und alle Benutzer '+
                              'wieder anzeigen zu lassen.',
                    events  :
                    {
                        onClick : function(Message, event)
                        {
                            var Users = Message.getAttribute( 'Users' );

                            Users.setAttribute( 'search', false );
                            Users.setAttribute( 'searchSettings', {} );

                            Message.destroy();
                            Users.load();
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


            QUI.Users.getList({
                field : this.getAttribute( 'field' ),
                order : this.getAttribute( 'order' ),
                limit : this.getAttribute( 'limit' ),
                page  : this.getAttribute( 'page' ),
                search         : this.getAttribute( 'search' ),
                searchSettings : this.getAttribute( 'searchSettings' )

            }, function(result, Request)
            {
                var i, len, data, user;

                var Panel = Request.getAttribute( 'Panel' ),
                    Grid  = Panel.getGrid();

                if ( !Grid )
                {
                    Panel.Loader.hide();
                    return;
                }

                data = result.data;

                var active_image   = URL_BIN_DIR +'16x16/apply.png',
                    active_text    = 'Benutzer aktivieren',

                    deactive_image = URL_BIN_DIR +'16x16/cancel.png',
                    deactive_text  = 'Benutzer deaktivieren';

                for ( i = 0, len = data.length; i < len; i++ )
                {
                    data[i].active = ( data[i].active ).toInt();

                    if ( data[i].active == -1 ) {
                        continue;
                    }

                    data[i].activebtn = {
                        status   : data[i].active,
                        value    : data[i].id,
                        uid      : data[i].id,
                        username : data[i].username,
                        image    : data[i].active ? active_image  : deactive_image,
                        alt      : data[i].active ? deactive_text : active_text,
                        events : {
                            onClick : Panel.$btnSwitchStatus
                        }
                    };
                }


                Grid.setData( result );

                Panel.setAttribute( 'title', 'Benutzerverwaltung' );
                Panel.setAttribute( 'icon', URL_BIN_DIR +'16x16/user.png' );
                Panel.refresh();

                Panel.Loader.hide();
            }, {
                Panel : this
            });
        },

        /**
         * execute a user user status switch
         */
        $btnSwitchStatus : function(Btn)
        {
            QUI.Users.switchStatus(
                Btn.getAttribute( 'uid' )
            );
        },

        /**
         * if a user status is changed
         *
         * @param {QUI.classes.users.Users} Users
         * @param {Object} ids - User-IDs
         */
        $onSwitchStatus : function(Users, ids)
        {
            var i, id, len, Btn, entry, status;

            var Grid = this.getGrid(),
                data = Grid.getData(),

                active_image   = URL_BIN_DIR +'16x16/apply.png',
                active_text    = 'Benutzer aktivieren',

                deactive_image = URL_BIN_DIR +'16x16/cancel.png',
                deactive_text  = 'Benutzer deaktivieren';


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
                    Btn.setAttribute( 'alt', deactive_text );
                    Btn.setAttribute( 'image', active_image );
                    continue;
                }

                // user is deactive
                Btn.setAttribute( 'alt', active_text );
                Btn.setAttribute( 'image', deactive_image );
            }
        },

        /**
         * if a user status is changed
         *
         * @param {QUI.classes.users.Users} Users
         * @param {QUI.classes.users.User} User
         */
        $onUserRefresh : function(Users, User)
        {
            var i, len;

            var Grid = this.getGrid(),
                data = Grid.getData(),
                id   = User.getId();

            for ( i = 0, len = data.length; i < len; i++ )
            {
                if ( data[ i ].id != id ) {
                    continue;
                }

                Grid.setDataByRow( i, {
                    status    : '',
                    id        : id,
                    username  : User.getAttribute( 'username' ),
                    usergroup : User.getAttribute( 'usergroup' ),
                    email     : User.getAttribute( 'email' ),
                    firstname : User.getAttribute( 'firstname' ),
                    lastname  : User.getAttribute( 'lastname' ),
                    regdate   : User.getAttribute( 'regdate' )
                });
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
        $onButtonnEditClick : function()
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

            if ( Parent.getType() === 'QUI.controls.desktop.Tasks' )
            {
                require([ 'controls/users/User' ], function(QUI_User_Control)
                {
                    var User, Task, TaskGroup;

                    TaskGroup = new QUI.controls.taskbar.Group();
                    Parent.appendTask( TaskGroup );

                    for ( i = 0, len = seldata.length; i < len; i++ )
                    {
                        User = new QUI.controls.users.User( seldata[ i ].id );
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
        $onButtonnDelClick : function()
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

            QUI.Windows.create('submit', {
                name        : 'DeleteUsers',
                title       : 'Benutzer löschen',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                text        : 'Sie möchten folgende Benutzer löschen:<br /><br />'+ uids.join(', '),
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                information : 'Die Benutzer werden komplett aus dem System entfernt und können nicht wieder hergestellt werden',

                width    : 500,
                height   : 150,
                uids     : uids,
                events   :
                {
                    onSubmit : function(Win)
                    {
                        QUI.Users.deleteUsers(
                            Win.getAttribute( 'uids' )
                        );
                    }
                }
            });
        }
    });

    return QUI.controls.users.Panel;
});