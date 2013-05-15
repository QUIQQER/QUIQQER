/**
 * A User Panel
 * Here you can change / edit the user
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/users/User
 * @package com.pcsg.qui.js.controls.users
 * @namespace QUI.controls.users
 */

define('controls/users/User', [

    'controls/desktop/Panel',
    'controls/Control',
    'controls/Utils',
    'Users',

    'css!controls/users/User.css'

], function(Panel, Control)
{
    "use strict";

    QUI.namespace( 'controls.users' );

    /**
     * @class QUI.controls.users.User
     *
     * @memberof! <global>
     */
    QUI.controls.users.User = new Class({

        Extends : Panel,
        Type    : 'QUI.controls.users.User',

        Binds : [
            'openPermissions',
            'savePassword',

            '$onCreate',
            '$onDestroy',
            '$onButtonActive',
            '$onButtonNormal',
            '$onUserRefresh',
            '$onUserDelete',
            '$onClickSave',
            '$onClickDel'
        ],

        initialize : function(uid, options)
        {
            this.$User = QUI.Users.get( uid );

            // defaults
            this.setAttribute( 'name', 'user-panel-'+ uid );
            this.parent( options );

            this.addEvents({
                onCreate  : this.$onCreate,
                onDestroy : this.$onDestroy
            });

            QUI.Users.addEvent( 'onDelete', this.$onUserDelete );
        },

        /**
         * Return the user of the panel
         *
         * @return {QUI.classes.users.User}
         */
        getUser : function()
        {
            return this.$User;
        },

        /**
         * Opens the user permissions
         */
        openPermissions : function()
        {
            var Parent = this.getParent(),
                User   = this.getUser();

            require([ 'controls/permissions/Panel' ], function(PermPanel)
            {
                Parent.appendChild(
                    new QUI.controls.permissions.Panel(
                        null,
                        User
                    )
                );
            });
        },

        /**
         * Create the user panel
         */
        $onCreate : function()
        {
            this.Loader.show();

            this.addButton({
                name      : 'userSave',
                User      : this,
                events    : {
                    onClick : this.$onClickSave
                },
                text      : 'Änderungen speichern',
                textimage : URL_BIN_DIR +'16x16/save.png'
            });

            this.addButton({
                name      : 'userDelete',
                User      : this,
                events    : {
                    onClick : this.$onClickDel
                },
                text      : 'Benutzer löschen',
                textimage : URL_BIN_DIR +'16x16/trashcan_empty.png'
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

            QUI.Users.addEvent( 'onRefresh', this.$onUserRefresh );
            QUI.Users.addEvent( 'onSave', this.$onUserRefresh );


            QUI.Ajax.get('ajax_users_gettoolbar', function(result, Request)
            {
                var i, len;

                var Panel = Request.getAttribute( 'Panel' ),
                    User  = Panel.getUser();

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    result[ i ].events = {
                        onActive : Panel.$onButtonActive,
                        onNormal : Panel.$onButtonNormal
                    };

                    Panel.addCategory( result[ i ] );
                }

                Panel.setAttribute( 'icon', URL_BIN_DIR +'16x16/user.png' );


                if ( User.getAttribute( 'title' ) === false )
                {
                    User.load();
                    return;
                }

                Panel.setAttribute(
                    'title',
                    'Benutzer: '+ User.getAttribute( 'username' )
                );

            }, {
                uid   : this.getUser().getId(),
                Panel : this
            });
        },

        /**
         * the panel on destroy event
         * remove the binded events
         */
        $onDestroy : function()
        {
            QUI.Users.removeEvent( 'refresh', this.$onUserRefresh );
            QUI.Users.removeEvent( 'save', this.$onUserRefresh );
            QUI.Users.removeEvent( 'delete', this.$onUserDelete );
        },

        /**
         * the button active event
         * load the template of the category, parse the controls and insert the values
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $onButtonActive : function(Btn)
        {
            this.Loader.show();

            QUI.Ajax.get('ajax_users_gettab', function(result, Request)
            {
                var Panel = Request.getAttribute( 'Panel' ),
                    Body  = Panel.getBody(),
                    User  = Panel.getUser();

                Body.set( 'html', '<form>'+ result +'</form>' );

                // parse all the controls
                QUI.controls.Utils.parse( Body );

                // insert the values
                var attributes = User.getAttributes();

                QUI.Utils.setDataToForm(
                    attributes,
                    Body.getElement( 'form' )
                );

                // password save
                var PasswordField  = Body.getElement( 'input[name="password2"]' ),
                    PasswordExpire = Body.getElements( 'input[name="expire"]' );

                if ( PasswordField )
                {
                    PasswordField.setStyle( 'float', 'left' );

                    new QUI.controls.buttons.Button({
                        text   : 'Passwort speichern',
                        events : {
                            onClick : Panel.savePassword
                        }
                    }).inject(
                        PasswordField, 'after'
                    );
                }

                // password expire
                if ( PasswordExpire.length )
                {
                    var expire = attributes.expire || false;

                    if ( !expire || expire == '0000-00-00 00:00:00' )
                    {
                        PasswordExpire[0].checked = true;
                    } else
                    {
                        expire = expire.split(' ');

                        PasswordExpire[1].checked = true;

                        Body.getElement( 'input[name="expire_date"]' ).value = expire[ 0 ];
                        Body.getElement( 'input[name="expire_time"]' ).value = expire[ 1 ];
                    }
                }

                Panel.Loader.hide();
            }, {
                Panel   : this,
                Tab     : Btn,
                plugin  : Btn.getAttribute( 'plugin' ),
                tab     : Btn.getAttribute( 'name' ),
                uid     : this.getUser().getId()
            });
        },

        /**
         * if the button was active and know normal
         * = unload event of the button
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $onButtonNormal : function(Btn)
        {
            var Content = this.getBody(),
                Frm     = Content.getElement( 'form' ),
                data    = QUI.Utils.getFormData( Frm );

            if ( typeof data.expire_date !== 'undefined' )
            {
                var time = data.expire_time || '00:00:00',
                    date = data.expire_date;

                if ( time === '' ) {
                    time = '00:00:00';
                }

                if ( date !== '' ) {
                    data.expire = date +' '+ time;
                }

                delete data.expire_time;
                delete data.expire_date;
            }

            this.getUser().setAttributes( data );
        },

        /**
         * Refresh the Panel if the user is refreshed
         *
         * @param {QUI.classes.users.User} User
         */
        $onUserRefresh : function(User)
        {
            this.setAttribute( 'title', 'Benutzer: '+ this.getUser().getAttribute( 'username' ) );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/user.png' );

            this.refresh();

            var Active = this.getCategoryBar().getActive();

            if ( !Active ) {
                Active = this.getCategoryBar().firstChild();
            }

            if ( Active ) {
                Active.click();
            }

            // button bar refresh
            (function()
            {
                this.getButtonBar().setAttribute( 'width', '98%' );
                this.getButtonBar().resize();
            }).delay( 200, this );
        },

        /**
         * event on user delete
         *
         * @param {QUI.classes.users.Users} Users
         * @param {Array} uids - user ids, which are deleted
         */
        $onUserDelete : function(Users, uids)
        {
            var uid = this.getUser().getId();

            for ( var i = 0, len = uids.length; i < len; i++ )
            {
                if ( uid == uids[i] )
                {
                    this.destroy();
                    break;
                }
            }
        },

        /**
         * Event: click on save
         *
         * @method QUI.controls.users.User#$onClickSave
         */
        $onClickSave : function()
        {
            var Active = this.getActiveCategory();

            if ( Active ) {
                this.$onButtonNormal( Active );
            }

            this.getUser().save();
        },

        /**
         * Event: click on delete
         *
         * @method QUI.controls.users.User#$onClickDel
         */
        $onClickDel : function()
        {
            QUI.Windows.create('submit', {
                name        : 'DeleteUser',
                title       : 'Benutzer löschen',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                text        : 'Sie möchten folgenden Benutzer löschen:<br /><br />'+ this.getUser().getId(),
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                information : 'Der Benutzer wird komplett aus dem System entfernt und kann nicht wieder hergestellt werden',

                width    : 500,
                height   : 150,
                uid      : this.getUser().getId(),
                events   :
                {
                    onSubmit : function(Win)
                    {
                        QUI.Users.deleteUsers(
                            [ Win.getAttribute( 'uid' ) ]
                        );
                    }
                }
            });
        },

        /**
         * Saves the password to the user
         * only triggerd if the password tab are open
         *
         * @method QUI.controls.users.User#savePassword
         */
        savePassword : function()
        {
            var Control = this,
                Body    = this.getBody(),
                Form    = Body.getElement( 'form' ),
                Pass1   = Form.elements.password,
                Pass2   = Form.elements.password2;

            if ( !Pass1 || !Pass2 ) {
                return;
            }

            Control.Loader.show();

            this.getUser().savePassword(
                Pass1.value,
                Pass2.value,
                {},
                function(result, Request)
                {
                    Control.Loader.hide();
                }
            );
        }
    });

    return QUI.controls.users.User;
});