/**
 * VHost Panel
 *
 * @module controls/system/VHosts
 */
define('controls/system/VHosts', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Prompt',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'controls/system/VHost',
    'controls/system/VHostServerCode',
    'Ajax',
    'Locale'

], function (QUI, QUIPanel, QUIPrompt, QUIConfirm, Grid, Vhost, VhostServerCode, Ajax, Locale) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIPanel,
        Type: 'controls/system/VHosts',

        Binds: [
            '$onCreate',
            '$onResize',

            '$gridClick',
            '$gridDblClick'
        ],

        options: {
            title: Locale.get(lg, 'system.vhosts.title'),
            icon: 'fa fa-location-arrow'
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            // buttons
            this.addButton({
                text: Locale.get(lg, 'system.vhosts.btn.add'),
                textimage: 'fa fa-plus',
                events: {
                    onClick: () => {
                        this.openAddVhost();
                    }
                }
            });

            this.addButton({
                type: 'separator'
            });

            this.addButton({
                name: 'editVhost',
                text: Locale.get(lg, 'system.vhosts.btn.edit.marked'),
                textimage: 'fa fa-edit',
                disabled: true,
                events: {
                    onClick: () => {
                        this.openEditVhost();
                    }
                }
            });

            this.addButton({
                name: 'delVhost',
                text: Locale.get(lg, 'system.vhosts.btn.del.marked'),
                textimage: 'fa fa-trash-o',
                disabled: true,
                events: {
                    onClick: () => {
                        this.openRemoveVhost();
                    }
                }
            });


            // Grid
            const Container = new Element('div').inject(
                this.getContent()
            );

            this.$Grid = new Grid(Container, {
                columnModel: [{
                    header: Locale.get(lg, 'system.vhosts.table.domain'),
                    dataIndex: 'host',
                    dataType: 'string',
                    width: 200
                }, {
                    header: Locale.get(lg, 'project'),
                    dataIndex: 'project',
                    dataType: 'string',
                    width: 200
                }, {
                    header: Locale.get(lg, 'language'),
                    dataIndex: 'lang',
                    dataType: 'string',
                    width: 200
                }, {
                    header: Locale.get(lg, 'template'),
                    dataIndex: 'template',
                    dataType: 'string',
                    width: 200
                }],
                onrefresh: () => {
                    this.load();
                }
            });

            // Events
            this.$Grid.addEvents({
                onClick: this.$gridClick,
                onDblClick: this.$gridDblClick
            });

            this.load();
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid) {
                return;
            }

            const Body = this.getContent();

            if (!Body) {
                return;
            }

            const size = Body.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        },

        /**
         * Load the users with the settings
         */
        load: function () {
            this.Loader.show();

            Ajax.get('ajax_vhosts_getList', (result) => {
                let host, entry;
                const data = [];

                if (Object.getLength(result)) {
                    for (host in result) {
                        if (!result.hasOwnProperty(host)) {
                            continue;
                        }

                        entry = result[host];

                        data.push({
                            host: host,
                            project: entry.project,
                            lang: entry.lang,
                            template: entry.template
                        });
                    }
                }

                this.$Grid.setData({
                    data: data
                });

                this.Loader.hide();
            });
        },

        /**
         * add a vhost
         *
         * @param {String} host - name of the host
         * @param {Function} [callback] - (optional), callback function
         */
        addVhost: function (host, callback) {
            Ajax.get('ajax_vhosts_add', (newHost) => {
                this.load();

                if (typeOf(callback) === 'function') {
                    callback(newHost);
                }
            }, {
                vhost: host
            });
        },

        /**
         * Edit a vhost
         *
         * @param {String} host - virtual host eq: www.something.com
         * @param {Array} data - virtual host data
         * @param {Function} [callback] - (optional), callback function
         */
        editVhost: function (host, data, callback) {
            this.Loader.show();

            Ajax.get('ajax_vhosts_edit', () => {
                this.load();

                if (typeOf(callback) === 'function') {
                    callback(host, data);
                }
            }, {
                vhost: host,
                data: JSON.encode(data)
            });
        },

        /**
         * Delete a vhost
         *
         * @param {String} host - virtual host eq: www.something.com
         * @param {Function} [callback] - (optional), callback function
         */
        removeVhost: function (host, callback) {
            this.Loader.show();

            Ajax.get('ajax_vhosts_remove', () => {
                this.load();

                if (typeOf(callback) === 'function') {
                    callback(host);
                }
            }, {
                vhost: host
            });
        },

        /**
         * window & sheet methods
         */

        /**
         * opens a add vhost window
         */
        openAddVhost: function () {
            new QUIPrompt({
                icon: 'fa fa-plus',
                titleicon: 'fa fa-location-arrow',
                title: Locale.get(lg, 'system.vhosts.add.window.title'),
                information: Locale.get(lg, 'system.vhosts.add.window.information'),
                maxWidth: 450,
                maxHeight: 300,
                events: {
                    onSubmit: (value, Win) => {
                        this.addVhost(value, (host) => {
                            Win.close();
                            this.openEditVhost(host);
                        });
                    }
                }
            }).open();
        },

        /**
         * Open the edit sheet
         *
         * @param {String} [vhost] - (optional), host name
         */
        openEditVhost: function (vhost) {
            if (typeof vhost === 'undefined') {
                const data = this.$Grid.getSelectedData();

                if (data[0] && data[0].host) {
                    vhost = data[0].host;
                }
            }

            if (typeof vhost === 'undefined') {
                return;
            }

            const Sheet = this.createSheet({
                title: Locale.get(lg, 'system.vhosts.edit.sheet.title', {
                    vhost: vhost
                }),
                icon: 'fa fa-location-arrow',
                events: {
                    onOpen: (Sheet) => {
                        this.Loader.show();

                        let Host = null;

                        // only numbers -> server error codes
                        if (/^\d+$/.test(vhost)) {
                            Host = new VhostServerCode({
                                host: vhost
                            }).inject(Sheet.getContent());

                        } else {
                            Host = new Vhost({
                                host: vhost
                            }).inject(Sheet.getContent());
                        }


                        Sheet.addButton({
                            text: Locale.get(lg, 'system.vhosts.edit.sheet.btn.save'),
                            textimage: 'fa fa-save',
                            events: {
                                onClick: function () {
                                    Host.save(function () {
                                        Sheet.hide();
                                    });
                                }
                            }
                        });

                        this.Loader.hide();
                    },

                    onClose: () => {
                        this.load();
                    }
                }
            });

            Sheet.show();
        },

        /**
         * Open the remove window
         *
         * @param {String} [vhost] - (optional), host name
         */
        openRemoveVhost: function (vhost) {
            if (typeof vhost === 'undefined') {
                const data = this.$Grid.getSelectedData();

                if (data[0] && data[0].host) {
                    vhost = data[0].host;
                }
            }

            if (typeof vhost === 'undefined') {
                return;
            }


            new QUIConfirm({
                title: Locale.get(lg, 'system.vhosts.del.window.title'),
                icon: 'fa fa-trash-o',
                text: Locale.get(lg, 'system.vhosts.del.window.text', {
                    vhost: vhost
                }),
                texticon: 'fa fa-trash-o',
                maxWidth: 450,
                maxHeight: 300,
                information: Locale.get(lg, 'system.vhosts.del.window.information'),

                closeButtonText: Locale.get(lg, 'cancel'),

                ok_button: {
                    text: Locale.get(lg, 'delete'),
                    textimage: 'fa fa-trash-o'
                },

                events: {
                    onSubmit: () => {
                        this.removeVhost(vhost);
                    }
                }
            }).open();
        },

        /**
         * grid events
         */

        /**
         * event : click at the grid
         *
         * @param {Object} data - grid event data
         */
        $gridClick: function (data) {
            const len = data.target.selected.length,
                Edit = this.getButtons('editVhost'),
                Delete = this.getButtons('delVhost');

            if (len === 0) {
                Edit.disable();
                Delete.disable();

                return;
            }

            Edit.enable();
            Delete.enable();

            data.evt.stop();
        },

        /**
         * event : double-click at the grid
         *
         * @param {Object} data - grid event data
         */
        $gridDblClick: function (data) {
            this.openEditVhost(
                data.target.getDataByRow(data.row).host
            );
        }
    });

});
