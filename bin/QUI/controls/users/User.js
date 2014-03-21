/**
 * A User Panel
 * Here you can change / edit the user
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/users/User
 */

define('controls/users/User', [

    'qui/controls/desktop/Panel',
    'Users',
    'Ajax',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'utils/Controls',

    'css!controls/users/User.css'

], function(Panel, Users, Ajax, QUIButton, QUIConfirm, FormUtils, ControlUtils)
{
    "use strict";

    /**
     * @class controls/users/User
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : Panel,
        Type    : 'controls/users/User',

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

            Users.addEvent( 'onDelete', this.$onUserDelete );
        },

        /**
         * Return the user of the panel
         *
         * @return {classes/users/User}
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
                    new PermPanel( null, User )
                );
            });
        },

        /**
         * Create the user panel
         */
        $onCreate : function()
        {
            var self = this;

            this.Loader.show();

            this.addButton({
                name      : 'userSave',
                events    : {
                    onClick : this.$onClickSave
                },
                text      : 'Änderungen speichern',
                textimage : 'icon-save'
            });

            this.addButton({
                name      : 'userDelete',
                events    : {
                    onClick : this.$onClickDel
                },
                text      : 'Benutzer löschen',
                textimage : 'icon-trash'
            });

            // permissions
            new QUIButton({
                image  : 'icon-gears',
                alt    : 'Gruppen Zugriffsrechte einstellen',
                title  : 'Gruppen Zugriffsrechte einstellen',
                styles : {
                    'float' : 'right'
                },
                events : {
                    onClick : this.openPermissions
                }
            }).inject( this.getHeader() );

            Users.addEvent( 'onRefresh', this.$onUserRefresh );
            Users.addEvent( 'onSave', this.$onUserRefresh );


            Ajax.get('ajax_users_gettoolbar', function(result, Request)
            {
                var i, len;
                var User = self.getUser();

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    result[ i ].events = {
                        onActive : self.$onButtonActive,
                        onNormal : self.$onButtonNormal
                    };

                    self.addCategory( result[ i ] );
                }

                self.setAttribute( 'icon', 'icon-user' );


                if ( User.getAttribute( 'title' ) === false )
                {
                    User.load();
                    return;
                }

                self.setAttribute(
                    'title',
                    'Benutzer: '+ User.getAttribute( 'username' )
                );

            }, {
                uid : this.getUser().getId()
            });
        },

        /**
         * the panel on destroy event
         * remove the binded events
         */
        $onDestroy : function()
        {
            Users.removeEvent( 'refresh', this.$onUserRefresh );
            Users.removeEvent( 'save', this.$onUserRefresh );
            Users.removeEvent( 'delete', this.$onUserDelete );
        },

        /**
         * the button active event
         * load the template of the category, parse the controls and insert the values
         *
         * @param {QUI.controls.buttons.Button} Btn
         */
        $onButtonActive : function(Btn)
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_users_gettab', function(result, Request)
            {
                var Body = self.getBody(),
                    User = self.getUser();

                Body.set( 'html', '<form>'+ result +'</form>' );

                // parse all the controls
                ControlUtils.parse( Body );

                // insert the values
                var attributes = User.getAttributes();

                FormUtils.setDataToForm(
                    attributes,
                    Body.getElement( 'form' )
                );

                // password save
                var PasswordField  = Body.getElement( 'input[name="password2"]' ),
                    PasswordExpire = Body.getElements( 'input[name="expire"]' );

                if ( PasswordField )
                {
                    PasswordField.setStyle( 'float', 'left' );

                    new QUIButton({
                        text   : 'Passwort speichern',
                        events : {
                            onClick : self.savePassword
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
                        PasswordExpire[1].checked = true;

                        Body.getElement( 'input[name="expire_date"]' ).value = expire;
                    }
                }

                self.Loader.hide();
            }, {
                Tab    : Btn,
                plugin : Btn.getAttribute( 'plugin' ),
                tab    : Btn.getAttribute( 'name' ),
                uid    : this.getUser().getId()
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
                data    = FormUtils.getFormData( Frm );

            if ( data.expire_date ) {
                data.expire = data.expire_date;
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
            this.setAttribute( 'icon', 'icon-user' );

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
            new QUIConfirm({
                name        : 'DeleteUser',
                title       : 'Benutzer löschen',
                icon        : 'icon-trash',
                text        : 'Sie möchten folgenden Benutzer löschen:<br /><br />'+ this.getUser().getId(),
                texticon    : 'icon-trash',
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
            }).open();
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

            this.Loader.show();

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
});