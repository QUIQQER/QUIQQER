
/**
 * Permissions control - parent class
 *
 * @module controls/permissions/Permission
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require controls/permissions/Sitemap
 * @require utils/Controls
 * @require utils/permissions/Utils
 * @require Locale
 * @require css!controls/permissions/Permission.css
 *
 * @event onLoad
 * @event onLoadError
 */
define('controls/permissions/Permission', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/permissions/Sitemap',
    'utils/Controls',
    'utils/permissions/Utils',
    'Locale',

    'css!controls/permissions/Permission.css'

], function(QUI, QUIControl, QUIButton, PermissionMap, ControlUtils, PermissionUtils, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/system';


    return new Class({

        Extends: QUIControl,
        Types: 'controls/permissions/Permission',

        Binds: [
            '$onInject',
            '$onSitemapItemClick'
        ],

        initialize: function(Bind, options)
        {
            this.$Bind = null;

            this.parent(options);

            this.$Map = null;
            this.$MapContainer = null;
            this.$ContentContainer = null;

            this.addEvents({
                onInject : this.$onInject
            });
        },

        /**
         * Create the DOMNode ELement
         *
         * @return {HTMLDivElement}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'controls-permissions-permission',
                html : '<div class="controls-permissions-permission-map shadow"></div>'+
                       '<div class="controls-permissions-permission-content">'+
                           '<div class="controls-permissions-permission-content-sheet"></div>'+
                       '</div>'
            });

            this.$MapContainer     = this.$Elm.getElement('.controls-permissions-permission-map');
            this.$ContentContainer = this.$Elm.getElement('.controls-permissions-permission-content');
            this.$ContentSheet     = this.$Elm.getElement('.controls-permissions-permission-content-sheet');

            return this.$Elm;
        },

        /**
         * Close the permission control
         *
         * @returns {Promise}
         */
        close : function()
        {
            return new Promise(function(response) {

                var duration = 250;
                var SelectSheet = this.$Elm.getElement('.controls-permissions-select');

                if (SelectSheet) {
                    duration = 10;
                }

                moofx(this.$ContentContainer).style({
                    overflow : 'hidden'
                }).animate({
                    opacity : 0,
                    width   : 0
                }, {
                    duration : duration,
                    equation : 'cubic-bezier(.42,.4,.46,1.29)',
                    callback : function()
                    {
                        moofx(this.$MapContainer).animate({
                            opacity : 0,
                            left : '-100%'
                        }, {
                            duration : duration,
                            equation : 'cubic-bezier(.42,.4,.46,1.29)',
                            callback : function() {

                                if (!SelectSheet) {
                                    response();
                                    return;
                                }

                                moofx(SelectSheet).animate({
                                    opacity : 0,
                                    left    : '-100%'
                                }, {
                                    duration : 250,
                                    equation : 'ease-in-out',
                                    callback : function() {
                                        SelectSheet.destroy();
                                        response();
                                    }
                                });
                            }
                        });

                    }.bind(this)
                });

            }.bind(this));
        },

        /**
         * Opens the permission control
         *
         * @returns {Promise}
         */
        open : function()
        {
            return new Promise(function(response, reject) {

                if (!this.$Bind) {

                    this.$openBindSelect().then(function() {
                        return this.open();

                    }.bind(this)).catch(function() {
                        reject();
                    });

                    return;
                }

                this.$Map = new PermissionMap(this.$Bind, {
                    events : {
                        onItemClick : this.$onSitemapItemClick
                    }
                }).inject(this.$MapContainer);

                this.$MapContainer.setStyles({
                    opacity : 0,
                    width   : 0
                });

                moofx(this.$MapContainer).animate({
                    opacity : 1,
                    width   : 240
                }, {
                    duration : 250,
                    equation : 'cubic-bezier(.42,.4,.46,1.29)',
                    callback : function()
                    {
                        moofx(this.$ContentContainer).animate({
                            opacity : 1
                        }, {
                            duration : 250,
                            equation : 'cubic-bezier(.42,.4,.46,1.29)',
                            callback : function() {
                                response();
                            }
                        });

                    }.bind(this)
                });

            }.bind(this));
        },

        /**
         * can be overwritten
         */
        $openBindSelect : function()
        {

        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            var self = this;

            self.open().then(function() {
                self.fireEvent('load');
            }).catch(function() {
                self.fireEvent('loadError');
            });
        },

        /**
         * event : on sitemap item click
         *
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $onSitemapItemClick : function(Item)
        {
            moofx(this.$ContentSheet).animate({
                left : '-100%'
            }, {
                duration : 250,
                equation : 'cubic-bezier(.42,.4,.46,1.29)',
                callback : function() {

                    var Permissions = PermissionUtils.Permissions;

                    Promise.all([
                        Permissions.getPermissionsByObject(this.$Bind),
                        Permissions.getList()
                    ]).then(function(result) {

                        var permissions    = result[0];
                        var permissionData = result[1];

                        var list, right, Elm, len;

                        var i   = 0,
                            val = Item.getAttribute('value')+'.';

                        this.$ContentSheet.set('html', '');

                        var Table = new Element('table', {
                            'class' : 'data-table',
                            html    : '<tr><th>'+ Item.getAttribute('text') +'</th></tr>'
                        });


                        // maybe to php?
                        for (right in permissions)
                        {
                            if (!permissions.hasOwnProperty(right)) {
                                continue;
                            }

                            if (!(right in permissionData)) {
                                continue;
                            }

                            if (val == '.' && right.match(/\./)) {
                                continue;
                            }

                            if (val == '.' && !right.match(/\./)) {
                                this.$createPermissionRow(
                                    permissionData[right],
                                    i,
                                    Table
                                );

                                i++;
                            }

                            if (!right.match(val) || right.replace(val, '').match(/\./)) {
                                continue;
                            }

                            this.$createPermissionRow(
                                permissionData[right],
                                i,
                                Table
                            );

                            i++;
                        }

                        // no rights
                        if (i === 0)
                        {
                            new Element('tr', {
                                'class' : 'odd',
                                html    : '<td>'+ QUILocale.get(lg, 'permissions.panel.message.no.rights') + '</td>'
                            }).inject( Table );
                        }

                        Table.inject(this.$ContentSheet);

                        this.$ContentSheet.getElements('input').addEvent(
                            'change',
                            this.$onFormElementChange
                        );

                        // set values
                        // set form values
                        if (typeof permissions !== 'undefined' && permissions) {

                            list = this.$ContentSheet.getElements('input');

                            for (i = 0, len = list.length; i < len; i++) {

                                Elm = list[i];

                                if (!(Elm.name in permissions)) {
                                    continue;
                                }

                                if (Elm.type == 'checkbox') {

                                    if (permissions[Elm.name] == 1) {
                                        Elm.checked = true;
                                    }

                                    continue;
                                }

                                if (typeOf(permissions[Elm.name]) == 'boolean') {
                                    continue;
                                }

                                Elm.value = permissions[Elm.name];
                            }
                        }

                        // parse controls
                        if (this.$Bind)
                        {
                            ControlUtils.parse(Table);

                        } else
                        {
                            // if no bind exist, we would only edit the permissions
                            Table.getElements('input,textarea').setStyles({
                                display : 'none'
                            });
                        }

                        moofx(this.$ContentSheet).animate({
                            left : 0
                        }, {
                            duration : 250,
                            equation : 'cubic-bezier(.42,.4,.46,1.29)'
                        });

                    }.bind(this)).catch(function(Err) {
                        console.error(Err);
                    });

                }.bind(this)
            });
        },

        /**
         * Create the controls in the rows of the permission tables
         *
         * @param {String} right - right name
         * @param {Number} i - row counter
         * @param {HTMLTableElement} Table - <table> Node Element
         */
        $createPermissionRow : function(right, i, Table)
        {
            var Node, Row;

            Row = new Element('tr', {
                'class' : i % 2 ? 'even' : 'odd',
                html    : '<td></td>'
            });

            Node = PermissionUtils.parse(right);

            // first we disable all nodes if the node have a specific area type
            if (!Node.getElements('input[data-area=""]')) {
                Node.addClass('disabled');
            }

            // than, we enable only for the binded area
            if (this.$Bind)
            {
                switch (this.$Bind.getType())
                {
                    case 'classes/projects/project/Site':
                        Node.getElements('input[data-area="site"]')
                            .getParent()
                            .removeClass('disabled');
                        break;

                    case 'classes/projects/Project':
                        Node.getElements('input[data-area="project"]')
                            .getParent()
                            .removeClass('disabled');
                        break;
                }
            }

            // edit modus
            if (!this.$Bind)
            {
                // only user rights can be deleted
                if (right.src == 'user')
                {
                    new QUIButton({
                        icon  : 'icon-remove',
                        title : Locale.get(lg, 'permissions.panel.btn.delete.right.alt', {
                            right : right.name
                        }),
                        alt : Locale.get(lg, 'permissions.panel.btn.delete.right.title', {
                            right : right.name
                        }),
                        value  : right.name,
                        events : {
                            onClick : this.delPermission
                        }
                    }).inject( Node, 'top' );
                }
            }

            Node.inject( Row.getElement( 'td' ) );
            Row.inject( Table );
        },

        /**
         * event : form element change
         */
        $onFormElementChange : function(event)
        {
            var Target = event.target;

            if (Target.type == 'checkbox')
            {
                PermissionUtils.Permissions.setPermission(
                    this.$Bind,
                    Target.name,
                    Target.checked ? 1 : 0
                );

            } else {

                PermissionUtils.Permissions.setPermission(
                    this.$Bind,
                    Target.name,
                    Target.value
                );
            }
        }
    });
});
