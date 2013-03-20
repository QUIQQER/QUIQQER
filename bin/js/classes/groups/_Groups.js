/**
 * The Group Manager
 *
 * List, edit, create groups
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires lib/groups/Controls
 * @requires controls/grid/Grid
 *
 * @module classes/groups/Groups
 * @package com.pcsg.qui.js.classes.groups
 * @namespace QUI.classes.groups
 */

define('classes/groups/Groups', [

    'classes/DOM',
    'lib/groups/Controls',
    'controls/grid/Grid'

], function(DOM, Controls, Grid)
{
    "use strict";

    QUI.namespace( 'classes.groups' );
    QUI.css( QUI.config('dir') +'classes/groups/Groups.css' );

    /**
     * @class QUI.classes.groups.Groups
     *
     * @fires onGroupRefresh - [groupid, data]
     */
    QUI.classes.groups.Groups = new Class({

        Implements: [ DOM ],

        options : {
            field  : 'name',
            order  : 'ASC',
            limit  : 20,
            page   : 1,
            view   : 'table',

            search       : false,
            searchfields : ['name']
        },

        initialize : function(container, Panel)
        {
            this.Container = $(container);
            this.Panel     = Panel;
            this.Panel.Loader.show();

            this.addEvent('groupRefresh', function(gid, data)
            {
                var i, len, gdata, rowdata, Btn, func_click;
                var Grid = this.Grid;

                if (!Grid) {
                    return;
                }

                gdata = Grid.getData();

                func_click = function(Btn) {
                    Btn.getAttribute('data').Groups.switchBtnStatus( Btn );
                };

                for (i = 0, len = gdata.length; i < len; i++)
                {
                    if (gdata[i].id != gid) {
                        continue;
                    }

                    // update data
                    Object.append(gdata[i], data);
                    Btn = gdata[i].status;

                    // button refresh
                    Btn.active = gdata[i].active;
                    Btn.onclick = func_click;

                    if (gdata[i].active)
                    {
                        Btn.alt   = Btn.aalt;
                        Btn.title = Btn.atitle;
                        Btn.image = Btn.aimage;
                    } else
                    {
                        Btn.alt   = Btn.dalt;
                        Btn.title = Btn.dtitle;
                        Btn.image = Btn.dimage;
                    }

                    Grid.setDataByRow(i, gdata[i]);
                    return;
                }

            }.bind(this));

            this.draw();
        },

        /**
         * Draw the panel
         *
         * @method QUI.classes.groups.Groups#draw
         */
        draw : function()
        {
            // Buttons
            if (this.Panel)
            {
                this.Panel.addButton({
                    name    : 'viewList',
                    Groups  : this,
                    title   : 'Tabellen Ansicht',
                    alt     : 'Tabellen Ansicht',
                    events  :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute('Groups').viewList();
                        }
                    },
                    image: URL_BIN_DIR +'16x16/view_detailed.png'
                });

                this.Panel.addButton({
                    name    : 'viewTree',
                    Groups  : this,
                    title   : 'Baum Ansicht',
                    alt     : 'Baum Ansicht',
                    events  :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute('Groups').viewTree();
                        }
                    },
                    image: URL_BIN_DIR +'16x16/view_tree.png'
                });

                this.Panel.addButton({
                    type: 'seperator'
                });

                this.Panel.addButton({
                    name: 'groupSearch',
                    Groups: this,
                    events :
                    {
                        onClick: function(Btn) {
                            Btn.getAttribute('Groups').search();
                        }
                    },
                    alt   : 'Gruppe suchen',
                    title : 'Gruppe suchen',
                    image : URL_BIN_DIR +'16x16/search.png'
                });

                this.Panel.addButton({
                    name : 'gsep',
                    type : 'seperator'
                });

                this.Panel.addButton({
                    name: 'groupNew',
                    Groups: this,
                    events :
                    {
                        onClick: function(Btn) {
                            QUI.lib.Groups.Windows.createNewGroup();
                        }
                    },
                    text: 'Neue Gruppe anlegen',
                    textimage: URL_BIN_DIR +'16x16/new.png'
                });

                this.Panel.addButton({
                    name: 'groupEdit',
                    Groups: this,
                    onClick: function(Btn) {
                        Btn.getAttribute('Groups').edit();
                    },
                    text: 'Gruppe bearbeiten',
                    disabled: true,
                    textimage: URL_BIN_DIR +'16x16/edit.png'
                });

                this.Panel.addButton({
                    name: 'groupDel',
                    Groups: this,
                    events :
                    {
                        onClick: function(Btn) {
                            Btn.getAttribute('Groups').deleteGroups();
                        }
                    },
                    text: 'Gruppe löschen',
                    disabled: true,
                    textimage:  URL_BIN_DIR +'16x16/trashcan_full.png'
                });

                this.Panel.addEvent('resize', function()
                {
                    this.resize();
                }.bind(this));
            }

            if (this.getAttribute('view') === 'tree')
            {
                this.viewTree();
                return;
            }

            this.viewList();
        },

        /**
         * Change the view to list
         *
         * @method QUI.classes.groups.Groups#viewList
         */
        viewList : function()
        {
            var Bar = this.Panel.getButtonBar(),
                TableContainer = new Element('div');

    // this.Container.id

            Bar.getElement('viewList').setActive();
            Bar.getElement('viewTree').setNormal();

            this.Container.set('html', '');
            this.Container.appendChild( TableContainer );

            this.setAttribute('view', 'table');

            this.Grid = new QUI.controls.grid.Grid(TableContainer, {
                columnModel : [
                    {header : 'Status',            dataIndex : 'status', dataType : 'button',  width : 50},
                    {header : 'Gruppen-ID',        dataIndex : 'id',     dataType : 'integer', width : 150},
                    {header : 'Gruppenname',       dataIndex : 'name',   dataType : 'integer', width : 150},
                    {header : 'Darf in den Admin', dataIndex : 'admin',  dataType : 'node',    width : 150}
                ],
                pagination : true,
                filterInput: true,
                perPage    : this.options.limit,
                page       : this.options.page,
                sortOn     : this.options.field,
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : 200,
                height     : 200,
                   onrefresh  : function(me)
                   {
                       var options = me.options;

                       this.setAttribute('field', options.sortOn);
                    this.setAttribute('order', options.sortBy);
                    this.setAttribute('limit', options.perPage);
                    this.setAttribute('page', options.page);

                    this.refresh();

                }.bind(this),
                alternaterows : true,
                resizeColumns : true,
                selectable    : true,
                multipleSelection : true,
                resizeHeaderOnly  : true
            });

            // Events
            this.Grid.addEvents({
                onClick : function(data)
                {
                    var len    = data.target.selected.length,
                        Panel  = this.Panel,
                        Edit   = Panel.Buttons.getElement('groupEdit'),
                        Delete = Panel.Buttons.getElement('groupDel');

                    if (len === 0)
                    {
                        Edit.setDisable();
                        Delete.setDisable();
                    }

                    if (len === 1)
                    {
                        Edit.setEnable();
                        Delete.setEnable();
                    }

                    if (len > 1)
                    {
                        Edit.setDisable();
                        Delete.setEnable();
                    }

                    data.evt.stop();

                }.bind( this ),

                onDblClick : function(data)
                {
                    this.edit(
                        data.target.getDataByRow( data.row ).id
                    );
                }.bind( this ),

                onBlur : function(Grid)
                {
                    Grid.unselectAll();
                    Grid.removeSections();
                },

                onContextMenu : function(event)
                {
                    console.log(2222);
                }
            });

            // starten
            this.resize();
            this.refresh();
        },

        /**
         * Change the view to a tree
         *
         * @method QUI.classes.groups.Groups#viewTree
         */
        viewTree : function()
        {
            var Bar = this.Panel.getButtonBar();

            Bar.getElementByName('viewList').setNormal();
            Bar.getElementByName('viewTree').setActive();

            this.Container.set('html', '');
            this.setAttribute('view', 'tree');

            if (this.Grid)
            {
                this.Grid.destroy();

                delete this.Grid;
                this.Grid = null;
            }

            QUI.lib.groups.Controls.Sitemap(
                this.Container,
                {
                    events :
                    {
                        onItemDblClick : function(Itm)
                        {
                            QUI.lib.Groups.openGroupInPanel(
                                Itm.getAttribute('value')
                            );
                        }
                    }
                },
                function(Map) {

                }
            );

            // starten
            this.refresh();
        },

        /**
         * Resize the panel
         *
         * @method QUI.classes.groups.Groups#resize
         */
        resize : function()
        {
            var Parent = this.Container.getParent();

            if (this.Grid && Parent)
            {
                if (this.getAttribute('search'))
                {
                    this.Grid.setHeight( Parent.getSize().y - 100 );
                } else
                {
                    this.Grid.setHeight( Parent.getSize().y - 25 );
                }

                this.Grid.setWidth( Parent.getSize().x - 20 );
            }
        },

        /**
         * Refresh the panel
         *
         * @method QUI.classes.groups.Groups#refresh
         */
        refresh : function()
        {
            this.Panel.Loader.show();
            this.Panel.setAttribute('title', 'Gruppenverwaltung');
            this.Panel.setAttribute('icon', URL_BIN_DIR +'images/loader.gif');
            this.Panel.refresh();

            this.resize();

            if (this.getAttribute('search') && !this.Container.getElement('.message'))
            {
                new QUI.classes.Messages.Attention({
                    Groups  : this,
                    message : 'Sucheparameter sind aktiviert. Klicken Sie hier um die Suche zu beenden und alle Benutzer wieder anzeigen zu lassen.',
                    events  :
                    {
                        onClick : function(event)
                        {
                            var Groups = this.getAttribute('Groups');

                            Groups.setAttribute('search', false);
                            Groups.setAttribute('searchSettings', {});

                            this.destroy();
                            Groups.refresh();
                        }
                    },
                    styles  : {
                        margin : 10,
                        width  : this.Container.getParent().getSize().x - 20,
                        'border-width' : 1
                    }
                }).create().inject(this.Container, 'top');
            }

            if (this.getAttribute('view') === 'table')
            {
                this.refreshGrid();
                return;
            }

            this.refreshTree();
        },

        /**
         * Refresh the group grid
         *
         * @method QUI.classes.groups.Groups#refreshGrid
         */
        refreshGrid : function()
        {
            QUI.Ajax.get('ajax_groups_search', function(result, Ajax)
            {
                var i, len, btn, data, Panel, func_click;
                var Groups = Ajax.getAttribute('Groups');

                if ( !Groups ) {
                    return;
                }

                if ( !Groups.Panel ) {
                    return;
                }

                Panel = Groups.Panel;

                if ( !Groups.Grid )
                {
                    Panel.Loader.hide();
                    return;
                }

                var Apply = new Element('img', {
                    src    : URL_BIN_DIR +'16x16/apply.png',
                    styles : {
                        margin : '5px 0'
                    }
                });

                var Cancel = new Element('img', {
                    src : URL_BIN_DIR +'16x16/cancel.png',
                    styles : {
                        margin : '5px 0'
                    }
                });

                data = result.data;

                func_click = function(Btn) {
                    Btn.getAttribute('data').Groups.switchBtnStatus( Btn );
                };

                for ( i = 0, len = data.length; i < len; i++ )
                {
                    data[i].Groups = Groups;
                    data[i].admin  = data[i].admin == 1 ? Apply.clone() : Cancel.clone();

                    btn = {
                        name   : 'statusBtn'+ data[i].id,
                        image  : URL_BIN_DIR +'16x16/cancel.png',
                        alt    : 'Gruppen ist deaktiviert',
                        title  : 'Gruppen ist deaktiviert',

                        aalt   : 'Gruppe ist aktiviert',
                        atitle : 'Gruppe ist aktiviert',
                        aimage : URL_BIN_DIR +'16x16/apply.png',

                        dalt   : 'Gruppen ist deaktiviert',
                        dtitle : 'Gruppen ist deaktiviert',
                        dimage : URL_BIN_DIR +'16x16/cancel.png',

                        Groups : this,
                        gid    : data[i].id,

                        events : {
                            onClick : func_click
                        }
                    };

                    if (data[i].active == 1)
                    {
                        btn.image = btn.aimage;
                        btn.alt   = btn.aalt;
                        btn.title = btn.atitle;
                    }

                    data[i].status = btn;
                }

                Groups.Grid.setData( result );

                Panel.setAttribute('icon', URL_BIN_DIR +'16x16/groups.png');
                Panel.refresh();
                Panel.Loader.hide();

            }, {
                params : JSON.encode(Object.clone(this.options)),
                Groups : this
            });
        },

        /**
         * Refresh the group tree if the view is to tree
         *
         * @method QUI.classes.groups.Groups#refreshGrid
         */
        refreshTree : function()
        {
            this.Panel.setAttribute('icon', URL_BIN_DIR +'16x16/groups.png');
            this.Panel.refresh();
            this.Panel.Loader.hide();
        },

        /**
         * Opens the group search in the panel
         *
         * @method QUI.classes.groups.Groups#search
         */
        search : function()
        {
            QUI.MVC.require(['lib/Controls'], function()
            {
                this.Panel.openSheet(function(Sheet, Content, Buttons)
                {
                    this.Panel.Loader.show();

                    QUI.Ajax.get('ajax_groups_searchtpl', function(result, Ajax)
                    {
                        var i, len, inputs, new_id, Frm, Search, Label;

                        var Sheet    = Ajax.getAttribute('Sheet'),
                            Content  = Ajax.getAttribute('Content'),
                            Groups   = Ajax.getAttribute('Groups'),
                            values   = {},
                            searchfields = Groups.getAttribute('searchfields');

                        Content.set('html', result);
                        Groups.setAttribute('SearchSheet', Sheet);

                        Frm    = Content.getElement('form');
                        Search = Frm.elements.search;

                        Search.addEvent('keyup', function(event)
                        {
                            if (event.key === 'enter') {
                                this.execSearch( this.getAttribute('SearchSheet') );
                            }
                        }.bind( Groups ));

                        Search.value = Groups.getAttribute('search') || '';
                        Search.focus();

                        // values aufbereiten
                        for ( i = 0, len = searchfields.length; i < len; i++ ) {
                            values[ searchfields[i] ] = true;
                        }

                        // elements
                        inputs = Frm.elements;

                        for (i = 0, len = inputs.length; i < len; i++)
                        {
                            new_id = inputs[i].name + Groups.getId();

                            inputs[i].set('id', new_id);

                            if (typeof values[ inputs[i].name ] !== 'undefined')
                            {
                                if (inputs[i].type == 'checkbox')
                                {
                                    inputs[i].checked = true;
                                } else
                                {
                                    inputs[i].value = values[ inputs[i].name ];
                                }
                            }

                            if (inputs[i].hasClass('date')) {
                                QUI.lib.Controls.Calendar( inputs[i] );
                            }

                            Label = Frm.getElement('label[for="'+ inputs[i].name +'"]');

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
                            Groups : Groups,
                            events :
                            {
                                onClick : function(Btn)
                                {
                                    Btn.getAttribute('Groups').execSearch(
                                        Btn.getAttribute('Sheet')
                                    );
                                }
                            }
                        }).create().inject(
                            Search.getParent()
                        );

                        Frm.addEvent('submit', function(event) {
                            event.stop();
                        });

                        Ajax.getAttribute('Groups').Panel.Loader.hide();
                    }, {
                        Groups  : this,
                        Sheet   : Sheet,
                        Content : Content,
                        Buttons : Buttons
                    });

                }.bind(this));
            }.bind( this ));
        },

        /**
         * Excute the group search
         *
         * @method QUI.classes.groups.Groups#execSearch
         *
         * @param {DOMNode} Sheet - paretn node from the search
         */
        execSearch : function(Sheet)
        {
            var Frm = Sheet.getElement('form'),
                searchfields = [];

            if ( Frm.elements.gid.checked ) {
                searchfields.push('id');
            }

            if ( Frm.elements.name.checked ) {
                searchfields.push('name');
            }

            this.setAttribute('search', Frm.elements.search.value);
            this.setAttribute('searchfields', searchfields);

            Sheet.fireEvent('close');
            this.refresh();
        },

        /**
         * Activate a group
         *
         * @method QUI.classes.groups.Groups#activate
         *
         * @param {Integer} gid - group id
         * @param {Function} onfinish - callback function after activasion
         * @param {Object} params - callback parameter
         */
        activate : function(gid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                Groups   : this,
                gid      : gid,
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_groups_activate', function(result, Ajax)
            {
                Ajax.getAttribute('Groups').fireEvent('onGroupRefresh', [
                    Ajax.getAttribute('gid'),
                    {active : result}
                ]);

                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        /**
         * Dectivate a group
         *
         * @method QUI.classes.groups.Groups#deactivate
         *
         * @param {Integer} gid - group id
         * @param {Function} onfinish - callback function after activasion
         * @param {Object} params - callback parameter
         */
        deactivate : function(gid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                Groups   : this,
                gid      : gid,
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_groups_deactivate', function(result, Ajax)
            {
                Ajax.getAttribute('Groups').fireEvent('onGroupRefresh', [
                    Ajax.getAttribute('gid'),
                    {active : result}
                ]);

                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        /**
         * Open the delete window, all selected group would be deleted
         *
         * @method QUI.classes.groups.Groups#deleteGroups
         */
        deleteGroups : function()
        {
            var i, len;
            var gids = [],
                data = this.Grid.getSelectedData();

            for (i = 0, len = data.length; i < len; i++) {
                gids.push( data[i].id );
            }

            if (!gids.length) {
                return;
            }

            QUI.lib.Groups.Windows.deleteGroups(gids, function(result, Ajax)
            {
                Ajax.getAttribute('Groups').Grid.refresh();
            }, {
                Groups : this
            });
        },

        /**
         * Open the edit panel for the selected group
         *
         * @method QUI.classes.groups.Groups#edit
         */
        edit : function()
        {
            QUI.lib.Groups.openGroupInPanel(
                this.Grid.getSelectedData()[0].id
            );
        },

        /**
         * Switch the acitivasion button and de- or activate the group
         *
         * @method QUI.classes.groups.Groups#switchBtnStatus
         * @ignore
         */
        switchBtnStatus : function(Btn)
        {
            var data   = Btn.getAttribute('data'),
                Groups = data.Groups,
                gid    = data.id,
                active = data.active;

            Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

            if (active == 1)
            {
                this.deactivate( gid );
                return;
            }

            this.activate( gid );
        }
    });

    return QUI.classes.groups.Groups;
});