/**
 * Benutzer Verwaltung
 * Hilfsobjekt
 *
 * @author Henning Leutz (PCSG)
 */

define('Users', [

    'classes/users/Users'

], function(QUI_Users)
{
    if ( typeof QUI.Users !== 'undefined' ) {
        return QUI.Users;
    }

    QUI.Users = new QUI_Users();

    return QUI.Users;

    /*

    QUI.lib.Users =
    {
        $count : 0,

        getUser : function(uid, Panel)
        {
            return new QUI.classes.users.User(Panel, uid);
        },

        openUserInPanel : function(uid, Parent)
        {
            Parent = Parent || MUI.get('content-panel');

            require(['controls/Settings', 'classes/users/User'], function(Settings, User)
            {
                new Settings({
                    name  : 'user-'+ Parent.id,
                    title : '<img src="'+ URL_BIN_DIR +'images/loader.gif" />',
                    uid   : uid,
                    container : Parent.id,
                    submit : false,
                    events :
                    {
                        onInit : function()
                        {
                            QUI.lib.Users.getUser(
                                this.getAttribute('uid'),
                                this
                            );
                        }
                    }
                });
            });

            return uid;
        },

        loadUserInPanel : function(Panel, uid)
        {
            require(['classes/users/User'], function(User)
            {
                Panel.el.content.set('html', '<div id="'+ Panel.id +'-user-c"><div>');

                new User(uid, Panel.id +'-user-c', Panel);
            });
        },

        tabOnLoad : function(Tab)
        {
            var Toolbar = Tab.getParent(),
                User    = Tab.getAttribute('User'),
                Panel   = User.Panel;

            Panel.Loader.show();

            QUI.Ajax.get('ajax_users_gettab', function(result, Ajax)
            {
                var i, len, dates, groups;

                var Tab     = Ajax.getAttribute('Tab'),
                    Toolbar = Tab.getParent(),
                    User    = Tab.getAttribute('User'),
                    Panel   = User.Panel,
                    Content = Panel.getBody();

                result = result || '';

                Ajax.getAttribute('Panel').setBody(
                    '<form name="user-data-'+ Ajax.getAttribute('uid') +'" action="">'+ result +'</form>'
                );

                Content.getElement('form').addEvent('submit', function(event) {
                    event.stop();
                });

                // Date Buttons
                dates = Content.getElements('[type="date"]');

                for (i = 0, len = dates.length; i < len; i++) {
                    QUI.lib.Controls.Calendar( dates[i] );
                }

                // Group Buttons
                groups = Content.getElements('input[class="groups"]');

                for (i = 0, len = groups.length; i < len; i++) {
                    QUI.lib.groups.Controls.Input( groups[i] );
                }


                // buttons
                Content.getElements('.btn-button').each(function(Elm)
                {
                    new QUI.controls.buttons.Button({
                        text   : Elm.get('data-text'),
                        image  : Elm.get('data-image'),
                        click  : Elm.get('data-click'),
                        uid    : this.getAttribute('uid'),
                        Win    : this.getAttribute('Panel'),
                        events :
                        {
                            onClick : function(Btn)
                            {
                                eval( Btn.getAttribute('click') +'(Btn.getAttribute("uid"), Btn.getAttribute("Win"))' );
                            }
                        }
                    }).inject( Elm );
                }.bind(Ajax));

                // Button / Tab in Toolbar setzen
                Toolbar.Active = Tab;

                if (Tab.getAttribute('onUserLoad'))
                {
                    if (typeOf(Tab.getAttribute('onUserLoad')) === 'function') {
                        Tab.getAttribute('onUserLoad')( Tab );
                    }

                    if (typeOf(Tab.getAttribute('onUserLoad')) === 'string') {
                        eval(Tab.getAttribute('onUserLoad') +'(Tab)');
                    }
                }

                if (Tab.getAttribute('plugin'))
                {
                    QUI.lib.Plugins.get('plugin/'+ Tab.getAttribute('plugin'), function(Plgn)
                    {
                        if (Plgn) {
                            Plgn.fireEvent('onUserTabLoad', [this]);
                        }
                    }.bind( Tab ));
                }

                Ajax.getAttribute('Panel').Loader.hide();

            }, {
                Panel   : Panel,
                Toolbar : Toolbar,
                Tab     : Tab,
                plugin  : Tab.getAttribute('plugin'),
                tab     : Tab.getAttribute('name'),
                uid     : User.getId()
            });
        },

        tabOnLoadUser : function(Tab)
        {
            var User    = Tab.getAttribute('User'),
                Panel   = Tab.getAttribute('Panel'),
                Content = Panel.getBody();

            QUI.Utils.setDataToForm(
                User.getAttributes(),
                Content.getElement('form')
            );
        },

        tabOnUnLoad : function(Tab)
        {
            if (Tab.getAttribute('onUserUnLoad'))
            {
                if (typeOf(Tab.getAttribute('onUserUnLoad')) === 'function') {
                    return Tab.getAttribute('onUserUnLoad')( Tab );
                }

                if (typeOf(Tab.getAttribute('onUserUnLoad')) === 'string')
                {
                    eval('var result = '+ Tab.getAttribute('onUserUnLoad') +'(Tab);');
                    return result;
                }
            }

            if (Tab.getAttribute('plugin'))
            {
                QUI.lib.Plugins.get('plugin/'+ Tab.getAttribute('plugin'), function(Plgn)
                {
                    if (Plgn) {
                        Plgn.fireEvent('onUserTabUnload', [this]);
                    }
                }.bind( Tab ));
            }

            return true;
        },

        tabOnUnLoadUser : function(Tab)
        {
            var i, len;
            var User    = Tab.getAttribute('User'),
                Panel   = Tab.getAttribute('Panel'),
                Content = Panel.getBody();

            User.setAttributes(
                QUI.Utils.getFormData(
                    Content.getElement('form')
                )
            );

            return true;
        },

        tabOnLoadUserAvatar : function(Tab)
        {
            var User    = Tab.getAttribute('User'),
                Panel   = Tab.getAttribute('Panel'),
                Content = Panel.getBody(),
                Upload  = Content.getElement('.user-avatar-upload-frame'),
                Avatar  = Content.getElement('.user-avatar');

            if ( Avatar )
            {
                new Element('div', {
                    html   : '<img src="'+ User.getAvatar() +'" />',
                    styles : {
                        height   : 200,
                        width    : '100%',
                        overflow : 'auto'
                    }
                }).inject( Avatar );
            }

            if ( Upload )
            {
                new Element('iframe', {
                    src : URL_SYS_DIR +'bin/upload.php?func=user&user_id='+ User.getId(),
                    frameborder : 0,
                    border      : 0,
                    styles : {
                        width  : 450,
                        height : 60
                    }
                }).inject( Upload );
            }
        },

        checkUsername : function(username, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                username : username,
                onfinish : onfinish
            });

            QUI.Ajax.get('ajax_users_checkname', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);
        },

        createUser : function(username, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                username : username,
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_users_create', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);
        },

        saveUser : function(uid, attributes, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                uid        : uid,
                onfinish   : onfinish,
                attributes : JSON.encode( attributes )
            });

            QUI.Ajax.post('ajax_users_save', function(result, Ajax)
            {
                QUI.MH.addSuccess(
                    result.message
                );

                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        deleteUsers : function(uids, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                uid      : uids.join(','),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_users_delete', function(result, Ajax)
            {
                QUI.MH.addSuccess(
                    result.message
                );

                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        Btns :
        {
            switchUserStatus : function(Btn)
            {
                var Users = Btn.getAttribute('Users'),
                    uid   = Btn.getAttribute('uid');

                Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

                if (Btn.getAttribute('data'))
                {
                    if (Btn.getAttribute('data').Users) {
                        Users = Btn.getAttribute('data').Users;
                    }
                }

                if (Btn.getAttribute('status') == 1)
                {
                    // Switch Status
                    Users.deactivate(uid, function(result, Ajax)
                    {
                        var Btn = Ajax.getAttribute('Btn');

                        Btn.setAttribute('status', 0);
                        Btn.setAttribute('image', URL_BIN_DIR +'16x16/cancel.png');
                    }, {
                        Btn : Btn
                    });
                } else
                {
                    // Switch Status
                    Users.activate(uid, function(result, Ajax)
                    {
                        var Btn = Ajax.getAttribute('Btn');

                        Btn.setAttribute('status', 1);
                        Btn.setAttribute('image', URL_BIN_DIR +'16x16/apply.png');
                    }, {
                        Btn : Btn
                    });
                }
            }
        },

        Windows :
        {
            createNewUser : function()
            {
                QUI.Windows.create('prompt', {
                    name        : 'CreateUser',
                    title       : 'Neuen Benutzer anlegen',
                    icon        : URL_BIN_DIR +'16x16/new.png',
                    text        : 'Neuer Benutzername:',
                    information : 'Geben Sie einen neuen Benutzernamen an. Der Benutzer wird inaktiv angelegt.',

                    width  : 500,
                    height : 150,
                    check  : function(Win)
                    {
                        Win.Loader.show();

                        QUI.lib.Users.checkUsername(Win.Input.value, function(result, Ajax)
                        {
                            var Win = Ajax.getAttribute('Win');

                            // Benutzer existiert schon
                            if (result === true)
                            {
                                QUI.MH.addAttention(
                                    'Der Benutzername existiert schon. Bitte geben Sie einen anderen Benutzernamen an.'
                                );

                                Win.Loader.hide();
                                return;
                            }

                            Win.fireEvent('onsubmit', [Win.Input.value, Win]);
                            Win.close();
                        }, {
                            Win : Win
                        });

                        return false;
                    },
                    events :
                    {
                        onsubmit : function(value)
                        {
                            QUI.lib.Users.createUser(value, function(result, Ajax) {
                                QUI.lib.Users.openUserInPanel( result );
                            });
                        }
                    }
                });
            },

            deleteUsers : function(uids, onfinish, params)
            {
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
                    onfinish : onfinish,
                    params   : params,
                    events   :
                    {
                        onsubmit : function(Win)
                        {
                            QUI.lib.Users.deleteUsers(
                                Win.getAttribute('uids'),
                                Win.getAttribute('onfinish'),
                                Win.getAttribute('params')
                            );
                        }
                    }
                });
            }
        }
    };

    return QUI.lib.Users;
    */
});
