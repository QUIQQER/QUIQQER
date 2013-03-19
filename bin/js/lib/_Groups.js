/**
 * Usergroup Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module lib/Groups
 * @package QUI.lib.Groups
 * @namespace QUI.lib
 */
/*
define('lib/Groups', function()
{
    QUI.namespace('lib');

    QUI.lib.Groups =
    {
        $count : 0,

        /**
         * Create an Apppanel and open the group listing
         *
         * @method QUI.lib.Groups#openGroupsInPanel
         *
         * @param {DOMNode} Parent - Parent Node (optional)
         * @return {String} Group-Panel ID
         */
/*
        openGroupsInPanel : function(Parent)
        {
            var id = 'group-panel';

            Parent = Parent || MUI.get('content-panel');

            MUI.Apppanels.create({
                control   :'MUI.Apppanel',
                id        : id,
                container : Parent.id,
                title     : '<img src="'+ URL_BIN_DIR +'images/loader.gif" />',
                tabbar    : false,
                onDrawEnd : function()
                {
                    this.Loader.show();
                    this.show(function(Panel)
                    {
                        QUI.lib.Groups.loadGroupsInPanel( Panel );
                    });
                }
            });

            return id;
        },

        loadGroupsInPanel : function(Panel)
        {
            require(['classes/groups/Groups'], function(Groups)
            {
                this.el.content.set('html', '<div id="'+ Panel.id +'-c"><div>');
                this.Loader.hide();

                new Groups(Panel.id +'-c', Panel);

            }.bind( Panel ));
        },

        /**
         * Einzelner Gruppe
         */
/*
        getGroup : function(gid, Panel)
        {
            return new QUI.classes.groups.Group(gid, Panel);
        },

        openGroupInPanel : function(gid, Parent)
        {
            Parent = Parent || MUI.get('content-panel');

            require([
                 'controls/Settings',
                 'classes/groups/Group'
            ], function(Settings, Group)
            {
                new Settings({
                    name  : 'group-panel-'+ Parent.id,
                    title : '<img src="'+ URL_BIN_DIR +'images/loader.gif" />',
                    gid   : gid,
                    container : Parent.id,
                    submit : false,
                    events :
                    {
                        onInit : function()
                        {
                            QUI.lib.Groups.getGroup(
                                this.getAttribute('gid'),
                                this
                            );
                        }
                    }
                });
            });

            return gid;
        },

        loadGroupInPanel : function(Panel, gid)
        {
            require(['classes/groups/Group'], function(Group)
            {
                this.el.content.set('html', '<div id="'+ Panel.id +'-group-c"><div>');

                new Group(gid, Panel.id +'-group-c', Panel);
            });
        },

        /**
         * Load the Content of the GroupTab
         *
         * @method QUI.lib.Groups#tabOnLoad
         * @param {QUI.control.toolbar.Tab} Tab
         */
/*
        tabOnLoad : function(Tab)
        {
            var Toolbar = Tab.getParent(),
                Group   = Tab.getAttribute('Group'),
                Panel   = Group.Panel;

            Panel.Loader.show();

            QUI.Ajax.get('ajax_groups_gettab', function(result, Ajax)
            {
                var i, len, dates, groups;

                var Tab     = Ajax.getAttribute('Tab'),
                    Toolbar = Tab.getParent(),
                    Group   = Tab.getAttribute('Group'),
                    Panel   = Group.Panel,
                    Content = Panel.getBody();

                result = result || '';

                Panel.setBody(
                    '<form name="group-data-'+ Ajax.getAttribute('gid') +'" action="">'+ result +'</form>'
                );

                Content.getElement('form').addEvent('submit', function(event) {
                    event.stop();
                });


                // Button / Tab in Toolbar setzen
                Toolbar.Active = Tab;

                if (Tab.getAttribute('onGroupLoad'))
                {
                    if (typeOf(Tab.getAttribute('onGroupLoad')) === 'function') {
                        Tab.getAttribute('onGroupLoad')( Tab );
                    }

                    if (typeOf(Tab.getAttribute('onGroupLoad')) === 'string') {
                        eval(Tab.getAttribute('onGroupLoad') +'(Tab)');
                    }
                }

                if (Tab.getAttribute('plugin'))
                {
                    QUI.lib.Plugins.get('plugin/'+ Tab.getAttribute('plugin'), function(Plgn)
                    {
                        if (Plgn) {
                            Plgn.fireEvent('onGroupTabLoad', [this]);
                        }
                    }.bind( Tab ));
                }

                Panel.Loader.hide();

            }, {
                Panel   : Panel,
                Toolbar : Toolbar,
                Tab     : Tab,
                plugin  : Tab.getAttribute('plugin'),
                tab     : Tab.getAttribute('name'),
                gid     : Group.getId()
            });
        },

        tabOnLoadGroup : function(Tab)
        {
            var i, len, elements, Toolbar;
            var id      = Slick.uidOf( Tab ),
                Group   = Tab.getAttribute('Group'),
                Panel   = Tab.getAttribute('Panel'),
                Content = Panel.getBody(),
                Frm     = Content.getElement('form');

            // set for and id of all Form Elements
            elements = Content.getElements('label');

            for (i = 0, len = elements.length; i < len; i++) {
                elements[i].set('for', elements[i].get('for') +'-'+ id);
            }

            elements = Frm.elements;

            for (i = 0, len = elements.length; i < len; i++)
            {
                if (elements[i].type === 'radio') {
                    continue;
                }

                if (elements[i].id)
                {
                    elements[i].set('id', elements[i].get('id') +'-'+ id);
                    continue;
                }

                elements[i].set('id', elements[i].get('name') +'-'+ id);
            }

            QUI.Utils.setDataToForm(
                Group.getAttributes(),
                Content.getElement('form')
            );

            QUI.Utils.setDataToForm(
                Group.getRights(),
                Content.getElement('form')
            );

            if ( (Toolbar = Frm.getElement('.toolbar-listing')) )
            {
                QUI.lib.Editor.getToolbars(function(toolbars)
                {
                    var i, len, Sel;
                    var Group   = Tab.getAttribute('Group'),
                        Panel   = Tab.getAttribute('Panel'),
                        Content = Panel.getBody(),
                        Toolbar = Content.getElement('.toolbar-listing');

                    if (!Toolbar) {
                        return;
                    }

                    Toolbar.set('html', '');

                    Sel = new Element('select', {
                        name : 'toolbar'
                    });

                    for (i = 0, len = toolbars.length; i < len; i++)
                    {
                        new Element('option', {
                            value : toolbars[i],
                            html  : toolbars[i].replace('.xml', '')
                        }).inject( Sel );
                    }

                    Sel.inject( Toolbar );
                    Sel.value = this.getAttribute('toolbar');

                }.bind( Group ));
            }
        },

        tabOnUnLoad : function(Tab)
        {
            if (Tab.getAttribute('onGroupUnLoad'))
            {
                if (typeOf(Tab.getAttribute('onGroupUnLoad')) === 'function') {
                    return Tab.getAttribute('onGroupUnLoad')( Tab );
                }

                if (typeOf(Tab.getAttribute('onGroupUnLoad')) === 'string')
                {
                    eval('var result = '+ Tab.getAttribute('onGroupUnLoad') +'(Tab);');
                    return result;
                }
            }

            if (Tab.getAttribute('plugin'))
            {
                QUI.lib.Plugins.get('plugin/'+ Tab.getAttribute('plugin'), function(Plgn)
                {
                    if (Plgn) {
                        Plgn.fireEvent('onGroupTabUnload', [this]);
                    }
                }.bind( Tab ));
            }

            return true;
        },

        tabOnUnLoadGroup : function(Tab)
        {
            var i, len;
            var Group   = Tab.getAttribute('Group'),
                Panel   = Tab.getAttribute('Panel'),
                Content = Panel.getBody(),
                Frm     = Content.getElement('form'),
                data    = QUI.Utils.getFormData( Frm );

            for (i in data)
            {
                // Are Rights?
                if (Frm.elements[i])
                {
                    if (!Frm.elements[i].length && Frm.elements[i].hasClass('right')) {
                        Group.setRight(i, data[i]);
                    }

                    if (Frm.elements[i].length && Frm.elements[i][0].hasClass('right')) {
                        Group.setRight(i, data[i]);
                    }

                    continue;
                }

                Group.setAttribute(i, data[i]);
            }

            return true;
        },

        /**
         * Global Methods
         */
/*
        getChildren : function(gid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                gid      : gid,
                onfinish : onfinish
            });

            QUI.Ajax.get('ajax_groups_children', function(result, Ajax)
            {
                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        createGroup : function(groupname, pid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                pid       : pid,
                groupname : groupname,
                onfinish  : onfinish
            });

            QUI.Ajax.post('ajax_groups_create', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);
        },

        saveGroup : function(gid, attributes, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                gid        : gid,
                onfinish   : onfinish,
                attributes : JSON.encode( attributes )
            });

            QUI.Ajax.post('ajax_groups_save', function(result, Ajax)
            {
                QUI.MH.addSuccess(
                    result.message
                );

                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        deleteGroups : function(gid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                gid      : gid.join(','),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_groups_delete', function(result, Ajax)
            {
                QUI.MH.addSuccess(
                    result.message
                );

                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        /**
         * Windows
         */
/*
        Windows :
        {
            deleteGroups : function(gids, onfinish, params)
            {
                QUI.Windows.create('submit', {
                    name        : 'DeleteGroups',
                    title       : 'Gruppen löschen',
                    icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                    text        : 'Sie möchten folgende Gruppen löschen:<br /><br />'+ gids.join(', '),
                    texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                    information : 'Die Gruppen werden komplett aus dem System entfernt und können nicht wieder hergestellt werden',

                    width    : 500,
                    height   : 150,
                    gids     : gids,
                    onfinish : onfinish,
                    params   : params,
                    events   :
                    {
                        onsubmit : function(Win)
                        {
                            QUI.lib.Groups.deleteGroups(
                                Win.getAttribute('gids'),
                                Win.getAttribute('onfinish'),
                                Win.getAttribute('params')
                            );
                        }
                    }
                });
            },

            createNewGroup : function()
            {
                QUI.lib.groups.Controls.SitemapWindow(function(result)
                {
                    if (!result.length) {
                        return;
                    }

                    new QUI.classes.Windows.Prompt({
                        title  : 'Neuer Gruppennamen',
                        icon   : URL_BIN_DIR +'16x16/group.png',
                        height : 220,
                        width  : 450,
                        text   : 'Bitte geben Sie den neuen Gruppennamen an',
                        pid    : result[0],
                        events :
                        {
                            onDrawEnd : function(Win, MuiWin)
                            {
                                var Body = Win.getBody();
                                    Body.getElement('input').focus();
                            },

                            onSubmit : function(result, Win)
                            {
                                QUI.lib.Groups.createGroup(
                                    result,
                                    Win.getAttribute('pid'),
                                    function(result, Ajax) {
                                        QUI.lib.Groups.openGroupInPanel( result );
                                    }
                                );
                            }
                        }
                    }).create();

                }, {
                    message : 'Unter welcher Gruppe soll die neue Gruppe angelegt werden?'
                });
            }
        }
    };

    return QUI.lib.Groups;
});
*/