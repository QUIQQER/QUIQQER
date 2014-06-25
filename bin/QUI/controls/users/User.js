/**
 * A User Panel
 * Here you can change / edit the user
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/users/User
 */

define('controls/users/User', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'qui/utils/Form',
    'utils/Controls',
    'Users',
    'Ajax',

    'css!controls/users/User.css'

], function(QUI, QUIPanel, QUIButton, QUIConfirm, Grid, FormUtils, ControlUtils, Users, Ajax)
{
    "use strict";

    /**
     * @class controls/users/User
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
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
            this.$User        = Users.get( uid );
            this.$AddressGrid = null;

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


            Ajax.get('ajax_users_getCategories', function(result, Request)
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
         * @param {qui/controls/buttons/Button} Btn
         */
        $onButtonActive : function(Btn)
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_users_getCategory', function(result)
            {
                var Body = self.getBody(),
                    User = self.getUser();

                if ( !result ) {
                    result = '';
                }

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
                    PasswordExpire = Body.getElements( 'input[name="expire"]' ),
                    AddressList    = Body.getElement( '.address-list' );

                if ( PasswordField )
                {
                    PasswordField.setStyle( 'float', 'left' );

                    new QUIButton({
                        text   : 'Passwort speichern',
                        events : {
                            onClick : self.savePassword
                        }
                    }).inject( PasswordField, 'after' );
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

                if ( AddressList ) {
                    self.$createAddressTable();
                }


                if ( !Btn.getAttribute( 'onload_require' ) &&
                     !Btn.getAttribute( 'onload' ) )
                {
                    self.Loader.hide();
                    return;
                }

                // require onload
                try
                {
                    var exec = Btn.getAttribute( 'onload' ),
                        req  = Btn.getAttribute( 'onload_require' );

                    if ( req )
                    {
                        require( [ req ], function(result)
                        {
                            self.Loader.hide();

                            if ( typeOf( result ) == 'class' ) {
                                new result( self );
                            }

                            if ( typeOf( result ) == 'function' ) {
                                result( self );
                            }

                            if ( exec ) {
                                eval( exec +'( self )' );
                            }
                        });

                        return;
                    }

                    eval( exec +'( self )' );

                    self.Loader.hide();

                } catch ( Exception )
                {
                    console.error( 'some error occurred '+ Exception.getMessage() );
                    self.Loader.hide();
                }

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
         * @param {qui/controls/buttons/Button} Btn
         */
        $onButtonNormal : function(Btn)
        {
            var Content = this.getBody(),
                Frm     = Content.getElement( 'form' ),
                data    = FormUtils.getFormData( Frm );

            console.log( data );

            if ( data.expire_date ) {
                data.expire = data.expire_date;
            }

            this.getUser().setAttributes( data );
        },

        /**
         * Refresh the Panel if the user is refreshed
         *
         * @param {qui/classes/users/User} User
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
         * @param {qui/classes/users/Manager} Users
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
         * @method controls/users/User#$onClickSave
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
         * @method controls/users/User#$onClickDel
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
                        Users.deleteUsers(
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
         * @method controls/users/User#savePassword
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
        },


        /**
         * Addresses
         */

        /**
         * Create the address table
         */
        $createAddressTable : function()
        {
            var self        = this,
                Content     = this.getContent(),
                size        = Content.getSize(),
                AddressList = Content.getElement( '.address-list' );

            if ( !AddressList ) {
                return;
            }

            this.$AddressGrid = new Grid( AddressList, {
                columnModel : [{
                    header : 'ID',
                    dataIndex : 'id',
                    dataType : 'string',
                    width : 60
                }, {
                    header : 'Anrede',
                    dataIndex : 'salutation',
                    dataType : 'string',
                    width : 60
                }, {
                    header : 'Vornamen',
                    dataIndex : 'firstname',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'Nachnamen',
                    dataIndex : 'lastname',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'Tel / Fax / Mobil',
                    dataIndex : 'phone',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'E-Mail',
                    dataIndex : 'mail',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'Firma',
                    dataIndex : 'company',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'Strasse',
                    dataIndex : 'street_no',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'PLZ',
                    dataIndex : 'zip',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'Stadt',
                    dataIndex : 'city',
                    dataType : 'string',
                    width : 100
                }, {
                    header : 'Land',
                    dataIndex : 'country',
                    dataType : 'string',
                    width : 100
                }],

                buttons : [{
                    name : 'add',
                    text : 'Adresse hinzufügen',
                    textimage : 'icon-plus',
                    events :
                    {
                        onClick : function() {
                            self.createAddress();
                        }
                    }
                }, {
                    type : 'seperator'
                }, {
                    name : 'edit',
                    text : 'Adresse editieren',
                    textimage : 'icon-edit',
                    disabled : true,
                    events :
                    {
                        onClick : function()
                        {
                            self.editAddress(
                                self.$AddressGrid.getSelectedData()[ 0 ].id
                            );
                        }
                    }
                }, {
                    name : 'delete',
                    text : 'Adresse löschen',
                    textimage : 'icon-remove',
                    disabled : true,
                    events :
                    {
                        onClick : function()
                        {
                            self.deleteAddress(
                                self.$AddressGrid.getSelectedData()[ 0 ].id
                            );
                        }
                    }
                }],

                height    : 300,
                onrefresh : function() {
                    self.$refreshAddresses();
                }
            });

            this.$AddressGrid.addEvents({
                onClick : function()
                {
                    var buttons = self.$AddressGrid.getButtons(),
                        sels    = self.$AddressGrid.getSelectedIndices();

                    if ( !sels )
                    {
                        buttons.each(function(Btn)
                        {
                            if ( Btn.getAttribute('name') != 'add' ) {
                                Btn.disable();
                            }
                        });

                        return;
                    }

                    buttons.each(function(Btn) {
                        Btn.enable();
                    });
                },

                onDblClick : function()
                {
                    self.editAddress(
                        self.$AddressGrid.getSelectedData()[ 0 ].id
                    );
                }
            });

            this.$AddressGrid.setWidth( size.x - 60 );
            this.$AddressGrid.refresh();
        },

        /**
         * Load / refresh the adresses
         */
        $refreshAddresses : function()
        {
            if ( !this.$AddressGrid ) {
                return;
            }

            var self = this;

            Ajax.get('ajax_users_address_list', function(result)
            {
                self.$AddressGrid.setData({
                    data : result
                });
            }, {
                uid : this.getUser().getId()
            });
        },

        /**
         * Creates a new address and opens the edit control
         */
        createAddress : function()
        {
            var self = this;

            Ajax.post('ajax_users_address_save', function(newId)
            {
                self.editAddress( newId );
                self.$AddressGrid.refresh();
            }, {
                uid  : this.getUser().getId(),
                aid  : 0,
                data : JSON.encode([])
            });
        },

        /**
         * Edit an address
         *
         * @param {Integer} addressId - ID of the address
         */
        editAddress : function(addressId)
        {
            var self  = this,
                Sheet = this.createSheet({
                    title : 'Adresse bearbeiten',
                    icon  : 'icon-edit'
                });

            Sheet.addEvents({
                onOpen : function(Sheet)
                {
                    require(['controls/users/Address'], function(Address)
                    {
                        var UserAddress = new Address({
                            addressId : addressId,
                            uid       : self.getUser().getId(),
                            events    :
                            {
                                onSaved : function()
                                {
                                    Sheet.hide();
                                    self.$AddressGrid.refresh();
                                }
                            }
                        }).inject( Sheet.getContent() );

                        Sheet.addButton({
                            textimage : 'icon-save',
                            text   : 'speichern',
                            events :
                            {
                                onClick : function() {
                                    UserAddress.save();
                                }
                            }
                        });
                    });
                }
            });

            Sheet.show();
        },

        /**
         * Delete an address
         *
         * @param {Integer} addressId - ID of the address
         */
        deleteAddress : function(addressId)
        {
            var self = this;

            new QUIConfirm({
                title : 'Adresse löschen',
                text  : 'Möchten Sie die Adresse wirklich löschen?',
                information : 'Die Adresse ist nicht wieder herstellbar',
                events :
                {
                    onSubmit : function()
                    {
                        Ajax.post('ajax_users_address_delete', function(result)
                        {
                            self.$AddressGrid.refresh();
                        }, {
                            aid : addressId,
                            uid : self.getUser().getId()
                        });
                    }
                }
            }).open();
        }

    });
});