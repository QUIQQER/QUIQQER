/**
 * Adressenverwaltung eines Benutzers
 *
 * @author www.pcsg.de (Henning Leutz)
 * @todo translation
 * @todo documentation
 */

define('classes/users/Adresses', [

    'classes/DOM',
    'controls/grid/Grid',

    'css!classes/users/Adresses.css'

], function(DOM, Grid)
{
    "use strict";

    QUI.namespace( 'classes.users' );

    /**
     * Adressenverwaltung eines Benutzers
     *
     * @class QUI.classes.users.Adresses
     * @memberof! <global>
     */
    QUI.classes.users.Adresses = new Class({

        Extends : DOM,
        Type    : 'QUI.classes.users.Adresses',

        initialize : function(User, Container)
        {
            this.$User      = User;
            this.$Container = Container;
            this.$Grid      = null;
            this.$Loader    = new MUI.Loader();

            this.$Parent = new Element('div');
            this.$Parent.inject( this.$Container );

            this.$Parent.setStyles({
                position : 'relative',
                overflow : 'hidden',
                width    : this.$Container.getSize().x,
                height   : this.$Container.getSize().y,
                'float'  : 'left',
                clear    : 'both',
                border   : '1px solid transparent'
            });

            this.draw();
        },

        /**
         * Draw the Adress list
         *
         * @method QUI.classes.users.Adresses#draw
         */
        draw : function()
        {
            this.$Loader.create().inject( this.$Parent );

            var GridContainer = new Element('div');
                GridContainer.inject( this.$Parent );

            this.$Grid = new QUI.controls.grid.Grid(GridContainer, {
                columnModel : [
                    {header : '',             dataIndex : 'id',         dataType : 'string', hidden : true},
                    {header : '',             dataIndex : 'edit',       dataType : 'button', width : 40},
                    {header : '',             dataIndex : 'del',        dataType : 'button', width : 40},
                    {header : 'Standard',     dataIndex : 'status',     dataType : 'button', width : 40},
                    {header : 'Anrede',       dataIndex : 'salutation', dataType : 'string', width : 150},
                    {header : 'Nachname',     dataIndex : 'lastname',   dataType : 'string', width : 150},
                    {header : 'Vorname',      dataIndex : 'firstname',  dataType : 'string', width : 150},
                    {header : 'Strasse / Nr', dataIndex : 'street_no',  dataType : 'string', width : 150},
                    {header : 'PLZ',          dataIndex : 'zip',        dataType : 'string', width : 150},
                    {header : 'Land',         dataIndex : 'country',    dataType : 'string', width : 150},
                    {header : 'Stadt',        dataIndex : 'city',       dataType : 'string', width : 150}
                ],
                buttons : [{
                    name      : 'addAdress',
                    text      : 'Adresse hinzufügen',
                    textimage : URL_BIN_DIR +'16x16/add.png',
                    Adresses  : this,
                    events    :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute('Adresses').drawAddAdress();
                        }
                    }
                }],
                pagination : false,
                filterInput: true,
                serverSort : false,
                showHeader : true,
                sortHeader : true,
                width      : this.$Parent.getSize().x,
                height     : this.$Parent.getSize().y - 20,
                   onrefresh  : function(Grid)
                   {
                       this.refresh();
                }.bind( this ),
                alternaterows     : true,
                resizeColumns     : true,
                selectable        : true,
                multipleSelection : false,
                resizeHeaderOnly  : true
            });

            this.$Grid.addEvents({

                onCblClick : function(data)
                {
                    this.drawEditAdress(
                        data.target.getDataByRow( data.row ).id
                    );
                }.bind( this )

            });

            this.refresh();
        },

        refresh : function()
        {
            this.$Loader.show();

            QUI.Ajax.get('ajax_users_adress_list', function(result, Ajax)
            {
                var i, len, entry,
                    on_create, on_edit, on_del, on_status;

                var data   = [],
                    User   = Ajax.getAttribute('User'),
                    Adress = Ajax.getAttribute('Adress'),
                    List   = Adress.$Grid;

                // click events
                on_create = function(Btn)
                {
                    var User = Btn.getAttribute('User');

                    if (User.getAttribute('adress') == Btn.getAttribute('aid')) {
                        Btn.setAttribute('image', URL_BIN_DIR +'16x16/apply.png');
                    }
                };

                on_del = function(Btn)
                {
                    Btn.getAttribute('Adress').delAdress(
                        Btn.getAttribute('aid')
                    );
                };

                on_edit = function(Btn)
                {
                    Btn.getAttribute('Adress').drawEditAdress(
                        Btn.getAttribute('aid')
                    );
                };

                on_status = function(Btn)
                {
                    var Adress = Btn.getAttribute('Adress'),
                        aid    = Btn.getAttribute('aid');

                    Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

                    Adress.setStandardAdress(aid, function(result, Ajax)
                    {
                        if (Ajax.getAttribute('Adress')) {
                            Ajax.getAttribute('Adress').refresh();
                        }
                    });
                };

                for (i = 0, len = result.length; i < len; i++)
                {
                    entry = result[i];

                    entry.status = {
                        name    : 'status',
                        image   : URL_BIN_DIR +'16x16/cancel.png',
                        title   : 'Als standard Adresse markieren',
                        alt     : 'Als standard Adresse markieren',

                        aimage : URL_BIN_DIR +'16x16/cancel.png',
                        dimage : URL_BIN_DIR +'16x16/cancel.png',

                        User   : User,
                        Adress : Adress,
                        aid    : entry.id,
                        events :
                        {
                            onClick  : on_status,
                            onCreate : on_create
                        }
                    };

                    entry.del = {
                        name   : 'del',
                        image  : URL_BIN_DIR +'16x16/trashcan_empty.png',
                        title  : 'Adresse löschen',
                        alt    : 'Adresse löschen',
                        User   : User,
                        Adress : Adress,
                        aid    : entry.id,
                        events : {
                            onClick : on_del
                        }
                    };

                    entry.edit = {
                        name   : 'edit',
                        image  : URL_BIN_DIR +'16x16/edit.png',
                        title  : 'Adresse bearbeiten',
                        alt    : 'Adresse bearbeiten',
                        User   : User,
                        Adress : Adress,
                        aid    : entry.id,
                        events : {
                            onClick : on_edit
                        }
                    };

                    data.push( entry );
                }

                List.setData({
                    data : data
                });

                Adress.$Loader.hide();
            }, {
                User   : this.$User,
                uid    : this.$User.getId(),
                Adress : this
            });
        },

        delAdress : function(aid)
        {
            QUI.Windows.create('submit', {
                name        : 'DeleteUser'+ this.$User.getId(),
                title       : 'Möchten Sie die Adresse wirklich löschen?',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                text        : 'Die Adresse wirklich löschen?',
                information : 'Die Adresse wird komplett aus dem System entfernt und kann nicht wieder hergestellt werden',

                width  : 500,
                height : 150,
                aid    : aid,
                events :
                {
                    onSubmit : function(Win)
                    {
                        QUI.Ajax.post('ajax_users_adress_delete', function(result, Ajax)
                        {
                            Ajax.getAttribute('Adress').refresh();
                        }, {
                            aid    : Win.getAttribute('aid'),
                            uid    : this.$User.getId(),
                            User   : this.$User,
                            Adress : this
                        });
                    }.bind( this )
                }
            });
        },

        setStandardAdress : function(aid, onfinish)
        {
            QUI.Ajax.post('ajax_users_adress_setstandard', function(result, Ajax)
            {
                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, {
                aid    : aid,
                uid    : this.$User.getId(),
                User   : this.$User,
                Adress : this,
                onfinish : onfinish
            });
        },

        drawEditAdress : function(aid)
        {
            this.drawAddAdress(function(aid)
            {
                QUI.Ajax.get('ajax_users_adress_get', function(result, Ajax)
                {
                    var i, len, Tel;

                    var Adress = Ajax.getAttribute('Adress'),
                        Elm    = Adress.$Parent.getElement('.draw-adress'),
                        Frm    = Elm.getElement('form'),
                        elms   = Frm.elements,

                        on_destroy = function(Contact) {
                            Contact.getAttribute('Adress').$phones.erase( Contact );
                        };

                    elms['adress-id'].value = Ajax.getAttribute('aid');

                    for ( i in result )
                    {
                        if (elms['adress-'+ i]) {
                            elms['adress-'+ i].value = result[i];
                        }
                    }

                    // Tel Mail Fax
                    var phones = eval( result.phone ),
                        mails  = eval( result.mail );

                    if ( !phones.length && !mails.length )
                    {
                        Adress.$Loader.hide();
                        return;
                    }

                    Tel = Elm.getElement('.user-adress-edit-telfaxmail');

                    if (phones)
                    {
                        for (i = 0, len = phones.length; i < len; i++)
                        {
                            Adress.$phones.push(
                                new QUI.classes.users.AdressesContact(Tel, {
                                    type   : phones[i].type,
                                    value  : phones[i].no,
                                    Adress : Adress,
                                    events : {
                                        onDestroy : on_destroy
                                    }
                                })
                            );
                        }
                    }

                    if (mails)
                    {
                        for (i = 0, len = mails.length; i < len; i++)
                        {
                            Adress.$phones.push(
                                new QUI.classes.users.AdressesContact(Tel, {
                                    type   : 'email',
                                    value  : mails[i],
                                    Adress : Adress,
                                    events : {
                                        onDestroy : on_destroy
                                    }
                                })
                            );
                        }
                    }

                }, {
                    aid    : aid,
                    uid    : this.$User.getId(),
                    Adress : this,
                    User   : this.$User
                });

            }.bind(this, [aid]));
        },

        drawAddAdress : function(onfinish)
        {
            this.$Loader.show();
            this.$phones = [];

            QUI.Ajax.get('ajax_users_adress_template', function(result, Ajax)
            {
                var InnerBtns, Tel;

                var Adress  = Ajax.getAttribute('Adress'),
                    Buttons = new Element('div.draw-adress-edit-buttons', {
                            html : '<div class="draw-adress-edit-inner-btns"></div>'
                        }),
                    Draw = new Element('div.draw-adress', {
                            html   : result,
                            styles : {
                                position : 'absolute',
                                top      : 0,
                                left     : Adress.$Parent.getSize().x * -1,
                                width    : Adress.$Parent.getSize().x,
                                height   : Adress.$Parent.getSize().y,
                                zIndex   : 1000,
                                background : '#fff'
                            }
                        }),
                    onfinish = Ajax.getAttribute('onfinish');


                Adress.$Parent.setStyle('border', '1px solid #bebebe');

                Draw.inject( Adress.$Parent );
                Buttons.inject( Draw );

                // form height setzen
                Draw.getElement('form').setStyles({
                    height   : Draw.getSize().y - 60,
                    overflow : 'auto'
                });

                InnerBtns = Buttons.getElement('.draw-adress-edit-inner-btns');
                InnerBtns.setStyles({
                    width  : 250,
                    margin : '0 auto'
                });

                new QUI.controls.buttons.Button({
                    text      : 'abbrechen',
                    textimage : URL_BIN_DIR +'16x16/cancel.png',
                    Adress    : Adress,
                    width     : 120,
                    events    :
                    {
                        onClick   : function(Btn)
                        {
                            Btn.getAttribute('Adress').closeAddAdress();
                        }
                    }
                }).create().inject( InnerBtns );

                new QUI.controls.buttons.Button({
                    text      : 'speichern',
                    textimage : URL_BIN_DIR +'16x16/apply.png',
                    Adress    : Adress,
                    width     : 120,
                    events    :
                    {
                        onClick   : function(Btn)
                        {
                            Btn.getAttribute('Adress').saveAdress();
                        }
                    }
                }).create().inject( InnerBtns );

                // Telefon / Fax / E-Mail
                if ( (Tel = Draw.getElement('.user-adress-edit-telfaxmail')) )
                {
                    new QUI.controls.buttons.Button({
                        name   : 'add-contact',
                        image  : URL_BIN_DIR +'16x16/add.png',
                        Adress : Adress,
                        events :
                        {
                            onClick : function(Btn)
                            {
                                Btn.getAttribute('Adress').$phones.push(
                                    new QUI.classes.users.AdressesContact(Btn.getElm().getParent(), {
                                        Adress : Adress,
                                        events :
                                        {
                                            ondestroy : function(Contact)
                                            {
                                                Contact.getAttribute('Adress').$phones.erase( Contact );
                                            }
                                        }
                                    })
                                );
                            }
                        }
                    }).create().inject( Tel );
                }

                // anzeige
                new Fx.Morph(Draw, {
                    onComplete : function(Elm)
                    {
                        document.forms['add-adress'].elements['adress-company'].focus();

                        if (typeOf(onfinish) === 'function')
                        {
                            onfinish();
                            return;
                        }

                        this.$Loader.hide();
                    }.bind( Adress )
                }).start({
                    left : 0
                });

            }, {
                User     : this.$User,
                Adress   : this,
                onfinish : onfinish
            });
        },

        closeAddAdress : function()
        {
            var Elm, i, len;

            if (!(Elm = this.$Parent.getElement('.draw-adress'))) {
                return;
            }

            if (this.$phones.length)
            {
                for (i = 0, len = this.$phones.length; i < len; i++)
                {
                    if (this.$phones[i]) {
                        this.$phones[i].destroy();
                    }
                }
            }

            new Fx.Morph(Elm, {
                onComplete : function(Elm)
                {
                    Elm.getParent().setStyle('border', '1px solid transparent');
                    Elm.destroy();

                    this.$Loader.hide();
                }.bind( this )
            }).start({
                left : Elm.getSize().x * -1
            });
        },

        saveAdress : function()
        {
            this.$Loader.show();

            var Frm, i, len, elms;

            var data = {},
                Elm  = this.$Parent.getElement('.draw-adress');

            if ( !Elm )
            {
                this.$Loader.hide();
                return;
            }

            Frm  = Elm.getElement('form');
            elms = Frm.elements;

            for ( i = 0, len = elms.length; i < len; i++ ) {
                data[ elms[i].name.replace('adress-', '') ] = elms[i].value;
            }

            // phones
            if ( this.$phones.length )
            {
                var phones = [],
                    mails  = [];

                for ( i = 0, len = this.$phones.length; i < len; i++ )
                {
                    if ( this.$phones[i].getAttribute('type') == 'email' )
                    {
                        mails.push( this.$phones[i].getValue() );
                        continue;
                    }

                    phones.push( this.$phones[i].getData() );
                }

                data.phone = phones;
                data.mails = mails;
            }

            QUI.Ajax.post('ajax_users_adress_save', function(result, Ajax)
            {
                var Adress = Ajax.getAttribute('Adress'),
                    User   = Adress.$User,
                    Elm    = Ajax.getAttribute('Elm');

                new Fx.Morph(Elm, {
                    onComplete : function(Elm)
                    {
                        Elm.getParent().setStyle('border', '1px solid transparent');
                        Elm.destroy();

                        this.refresh();
                    }.bind( Adress )
                }).start({
                    left : Elm.getSize().x * -1
                });

            }, {
                Elm    : Elm,
                aid    : elms['adress-id'].value,
                uid    : this.$User.getId(),
                Adress : this,
                data   : JSON.encode( data )
            });
        }
    });

    return QUI.classes.users.Adresses;
});