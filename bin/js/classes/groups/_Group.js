/**
 * A QUIQQER User Group
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/groups/Group
 * @package com.pcsg.qui.js.classes.groups
 * @namespace QUI.classes.groups
 */

define('classes/groups/Group', [

    'classes/DOM'

], function(DOM)
{
    QUI.namespace('classes.groups');

    /**
     * @class QUI.classes.groups.Group
     *
     * @param {Integer} gid - The Group ID
     * @param {MUI.Apppanel} Panel
     */
    QUI.classes.groups.Group = new Class({

        Implements: [ DOM ],

        options : {
            gid        : 0,
            attributes : {},
            rights     : {},

            'user-limit' : 20,
            'user-page'  : 1,
            'user-sort'  : 'username',
            'user-order' : ''
        },

        initialize : function(gid, Panel)
        {
            Panel.Loader.show();

            this.Container = $(Panel.id);
            this.Panel     = Panel;

            this.options.gid = (gid).toInt();
            this.load();
        },

        /**
         * Returns the group id
         *
         * @method QUI.classes.groups.Group#getId
         *
         * @return {Integer}
         */
        getId : function()
        {
            return this.options.gid;
        },

        /**
         * Set an group attribute
         *
         * @method QUI.classes.groups.Group#setAttribute
         *
         * @param {String} k        - Name of the Attribute
         * @param {unknown_type} v - Value of the Attribute
         */
        setAttribute : function(k, v)
        {
            this.options.attributes[ k ] = v;

            return this;
        },

        /**
         * If you want set more than one attribute
         *
         * @method QUI.classes.groups.Group#setAttributes
         *
         * @param {Object} attributes - Object with attributes
         * @return {this}
         *
         * @example
         * Group.setAttributes({
         *   attr1 : '1',
         *   attr2 : []
         * })
         */
        setAttributes : function(attributes)
        {
            attributes = attributes || {};

            for (var k in attributes) {
                this.options.attributes[ k ] = attributes[ k ];
            }

            return this;
        },

        /**
         * Get an group attribute
         *
         * @method QUI.classes.groups.Group#getAttribute
         *
         * @param {String} k - Attribute name
         * @return {unknown_type}
         */
        getAttribute : function(k)
        {
            if (typeof this.options.attributes[ k ] !== 'undefined') {
                return this.options.attributes[ k ];
            }

            return false;
        },

        /**
         * Get all attributes from the group
         *
         * @method QUI.classes.groups.Group#getAttributes
         *
         * @return {Object}
         */
        getAttributes : function()
        {
            return this.options.attributes;
        },

        /**
         * Set an group right
         *
         * @method QUI.classes.groups.Group#setRight
         *
         * @param {String} k        - Name of the right
         * @param {unknown_type} v - Value of the right
         * @return {this}
         */
        setRight : function(k, v)
        {
            this.options.rights[k] = v;

            return this;
        },

        /**
         * Has the group the right?
         * Returns the right if it exist
         *
         * @method QUI.classes.groups.Group#hasRight
         *
         * @param {String} right  - Name of the right
         * @return {unknown_type|Bool}
         */
        hasRight : function(right)
        {
            if (this.options.rights[rights] !== 'undefined') {
                return this.options.rights[rights];
            }

            return false;
        },

        /**
         * Get all rights from the group
         *
         * @method QUI.classes.groups.Group#getRights
         *
         * @return {Object}
         */
        getRights : function()
        {
            return this.options.rights;
        },

        /**
         * Exist a right in the group?
         *
         * @method QUI.classes.groups.Group#existsRight
         *
         * @param  {String} $right
         * @return {Bool}
         */
        existsRight : function(right)
        {
            if (this.options.rights[rights] !== 'undefined') {
                return true;
            }

            return false;
        },

        /**
         * Load the group, load the attributes and rights from the database
         *
         * @method QUI.classes.groups.Group#load
         *
         * @return {this}
         */
        load: function()
        {
            QUI.Ajax.get('ajax_groups_get', function(result, Ajax)
            {
                var Group = Ajax.getAttribute('Group');

                if (result.rights)
                {
                    Group.options.rights = result.rights;
                    delete result.rights;
                }

                Group.setAttributes( result );
                Group.draw();
            }, {
                gid   : this.getId(),
                Group : this
            });

            return this;
        },

        /**
         * Saves the group
         * All attributes will be send to the database
         *
         * @method QUI.classes.groups.Group#save
         *
         * @return {this}
         */
        save : function()
        {
            if (this.Panel.getTabbar().Active)
            {
                QUI.lib.Groups.tabOnUnLoad(
                    this.Panel.getTabbar().Active
                );
            }

            var attributes = this.getAttributes();
                attributes.rights = this.getRights();

            QUI.lib.Groups.saveGroup(
                this.getId(),
                attributes
            );

            return this;
        },

        /**
         * Delete the Group
         *
         * @method QUI.classes.groups.Group#del
         */
        del : function()
        {
            QUI.Windows.create('submit', {
                name        : 'DeleteUser'+ this.getId(),
                title       : 'Möchten Sie die Gruppe wirklich löschen?',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                text        : 'Die Gruppe '+ this.getAttribute('name') +' wirklich löschen?',
                information : 'Die Gruppe wird komplett aus dem System entfernt und kann nicht wieder hergestellt werden',

                width  : 500,
                height : 150,
                events :
                {
                    onsubmit : function(Win)
                    {
                        QUI.Ajax.post('ajax_groups_delete', function(result, Ajax)
                        {
                            Ajax.getAttribute('Group').Panel.close();
                        }, {
                            gid   : this.getId(),
                            Group : this
                        });
                    }.bind( this )
                }
            });
        },

        /**
         * Get all users that are in inside the group
         *
         * @method QUI.classes.groups.Group#getUsers
         *
         * @param {Function} onfinish - Callback function
         *         the return of the function: {Array}
         * @return {this}
         */
        getUsers : function(onfinish)
        {
            QUI.Ajax.get('ajax_groups_users', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result);
            }, {
                gid    : this.getId(),
                params : JSON.encode({
                    'limit' : this.options['user-limit'],
                    'page'  : this.options['user-page'],
                    'field' : this.options['user-sort'],
                    'order' : this.options['user-order']
                }),
                Group    : this,
                onfinish : onfinish
            });

            return this;
        },

        /**
         * Show the users in the panel
         *
         * @method QUI.classes.groups.Group#showUsers
         *
         * @return {this}
         */
        showUsers : function()
        {
            var Content = this.Panel.getBody(),
                GridCon = new Element('div');

            this.Panel.setBody('');
            GridCon.inject( Content );

            this.UserGrid = new QUI.classes.Grid(GridCon, {
                columnModel : [
                    {header : 'Status',           dataIndex : 'status',    dataType : 'node',   width : 50},
                    {header : 'Benutzer-ID',      dataIndex : 'id',        dataType : 'integer', width : 150},
                    {header : 'Benutzername',     dataIndex : 'username',  dataType : 'integer', width : 150},
                    {header : 'E-Mail',           dataIndex : 'email',     dataType : 'string',  width : 150},
                    {header : 'Vorname',          dataIndex : 'firstname', dataType : 'string',  width : 150},
                    {header : 'Nachname',         dataIndex : 'lastname',  dataType : 'string',  width : 150},
                    {header : 'Erstellungsdatum', dataIndex : 'regdate',   dataType : 'date',    width : 150}
                ],
                pagination : true,
                filterInput: true,
                perPage    : this.options['user-limit'],
                page       : this.options['user-page'],
                sortOn     : this.options['user-sort'],
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : Content.getSize().x,
                height     : Content.getSize().y - 45,
                   onrefresh  : function(me)
                   {
                    var options = me.options;

                       this.options['user-sort']  = options.sortOn;
                    this.options['user-order'] = options.sortBy;
                    this.options['user-limit'] = options.perPage;
                    this.options['user-page']  = options.page;

                    this.refreshUser();

                }.bind(this),
                alternaterows : true,
                resizeColumns : true,
                selectable    : true,
                multipleSelection : true,
                resizeHeaderOnly  : true
            });

            this.UserGrid.addEvents({

                onDblClick : function(data)
                {
                    QUI.lib.Users.openUserInPanel(
                        data.target.getDataByRow( data.row ).id
                    );

                }.bind( this )

            });

            GridCon.setStyles({
                margin: 0
            });

            this.UserGrid.refresh();

            return this;
        },

        /**
         * Refresh the users grid in the panel
         *
         * @method QUI.classes.groups.Group#refreshUser
         *
         * @return {this}
         */
        refreshUser : function()
        {
            if (typeof this.UserGrid === 'undefined') {
                return this;
            }

            this.Panel.Loader.show();

            this.getUsers(function(result, Ajax)
            {
                if (typeof this.UserGrid === 'undefined') {
                    return;
                }

                for (var i = 0, len = result.data.length; i < len; i++)
                {
                    if (result.data[i].active)
                    {
                        result.data[i].status = new Element('img', {
                            src : URL_BIN_DIR +'16x16/apply.png',
                            styles : {
                                margin : '5px 0 5px 12px'
                            }
                        });

                    } else
                    {
                        result.data[i].status = new Element('img', {
                            src : URL_BIN_DIR +'16x16/cancel.png',
                            styles : {
                                margin : '5px 0 5px 12px'
                            }
                        });
                    }
                }

                this.UserGrid.setData( result );
                this.Panel.Loader.hide();
            }.bind( this ));

            return this;
        },

           /**
         * Draw the left buttons and all in the panel
         *
         * @method QUI.classes.groups.Group#refreshUser
         *
         * @return {this}
         */
        draw : function()
        {
            this.Panel.Loader.show();

            this.$getButtons(function(result, Ajax)
            {
                var Group  = Ajax.getAttribute('Group'),
                    Panel = Group.Panel;

                Panel.setAttribute('title', 'Gruppe: '+ Group.getAttribute('name'));
                Panel.setAttribute('icon', URL_BIN_DIR +'16x16/groups.png');
                Panel.refresh();

                Panel.Loader.hide();
            });

            this.Panel.addButton({
                name: 'groupSave',
                Group: this,
                text: 'Änderungen speichern',
                textimage: URL_BIN_DIR +'16x16/save.png',
                events:
                {
                    onClick: function(Btn) {
                        Btn.getAttribute('Group').save();
                    }
                }
            });

            this.Panel.addButton({
                name: 'groupDelete',
                Group: this,
                text: 'Gruppe löschen',
                textimage: URL_BIN_DIR +'16x16/trashcan_empty.png',
                events:
                {
                    onClick: function(Btn) {
                        Btn.getAttribute('Group').del();
                    }
                }
            });

            return this;
        },

        /**
         * Resize the panel
         *
         * @method QUI.classes.groups.Group#refreshUser
         * @return {this}
         *
         * @todo Resize the group panel
         */
        resize : function()
        {

            return this;
        },

        /**
         * Get the buttons for the pannel
         *
         * @method QUI.classes.groups.Group#refreshUser
         *
         * @param {Function} onfinish - Callback function
         * @ignore
         */
        $getButtons : function(onfinish)
        {
            QUI.Ajax.get('ajax_editor_get_toolbars', function(result, Ajax)
            {
                var i, len, Btn;

                var Group = Ajax.getAttribute('Group'),
                    Panel = Group.Panel,

                    func_on_active = function(Btn)
                    {
                        var Active;

                        // unload auf aktiven Button
                        if (Btn.getParent() && (Active = Btn.getParent().getActive()))
                        {
                            if (Btn.getAttribute('name') == Active.getAttribute('name')) {
                                return;
                            }

                            if (QUI.lib.Groups.tabOnUnLoad( Active ) === false)
                            {
                                Btn.setNormal();
                                Active.setActive();
                                return;
                            }
                        }

                        QUI.lib.Groups.tabOnLoad( Btn );
                    };

                for (i = 0, len = result.length; i < len; i++)
                {
                    Btn = new QUI.controls.buttons.Button( result[i] );
                    Btn.setAttribute('Panel', Panel);
                    Btn.setAttribute('Group', Group);

                    if (Btn.getAttribute('onload'))
                    {
                        Btn.setAttribute('onGroupLoad', Btn.getAttribute('onload'));
                        Btn.setAttribute('onload', false);
                    }

                    if (Btn.getAttribute('onunload'))
                    {
                        Btn.setAttribute('onGroupUnLoad', Btn.getAttribute('onunload'));
                        Btn.setAttribute('onunload', false);
                    }

                    Btn.addEvents({
                        onSetActive : func_on_active
                    });

                    Group.Panel.appendChild( Btn );
                }

                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                gid      : this.getId(),
                Group    : this,
                onfinish : onfinish
            });
        }
    });

    return QUI.classes.groups.Group;
});