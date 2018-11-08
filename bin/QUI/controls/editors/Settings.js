/**
 * WYWIWYG Editor Setting Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/editors/Settings', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Confirm',
    'qui/controls/windows/Prompt',
    'controls/grid/Grid',
    'Ajax',
    'Locale'

], function (QUI, QUIPanel, QUIConfirm, QUIPrompt, Grid, Ajax, Locale) {
    "use strict";

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/editors/Settings',

        Binds: [
            '$onCreate',
            '$onResize',
            '$onRefresh',

            '$gridClick',
            '$gridDblClick',

            'editToolbar',
            'openAddToolbarWindow',
            'openDeleteToolbarWindow'
        ],

        options: {
            title: Locale.get('quiqqer/system', 'editors.settings.title'),
            icon : 'fa fa-font'
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid = null;

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize,
                onRefresh: this.$onRefresh
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            var self = this;

            this.setAttribute('title', Locale.get('quiqqer/system', 'editors.settings.title'));

            this.addButton({
                name     : 'toolbarAdd',
                text     : Locale.get(
                    'quiqqer/system',
                    'editors.settings.btn.add.toolbar'
                ),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.openAddToolbarWindow
                }
            });

            this.addButton({
                type: 'separator'
            });

            this.addButton({
                name     : 'toolbarEdit',
                text     : Locale.get(
                    'quiqqer/system',
                    'editors.settings.btn.edit.toolbar'
                ),
                textimage: 'fa fa-edit',
                disabled : true,
                events   : {
                    onClick: this.editToolbar
                }
            });

            this.addButton({
                name     : 'toolbarDelete',
                text     : Locale.get(
                    'quiqqer/system',
                    'editors.settings.btn.delete.toolbar'
                ),
                textimage: 'fa fa-trash-o',
                disabled : true,
                events   : {
                    onClick: function () {
                        var sel = self.getGrid().getSelectedData();

                        self.openDeleteToolbarWindow(sel[0].toolbar);
                    }
                }
            });
            //
            // this.addCategory({
            //     text: 'Toolbars'
            // });
            //
            // this.addCategory({
            //     text: 'Plugins'
            // });

            var Container = new Element('div').inject(
                this.getContent()
            );

            this.$Grid = new Grid(Container, {
                columnModel: [{
                    header   : Locale.get(
                        'quiqqer/system',
                        'editors.settings.table.toolbar.name'
                    ),
                    dataIndex: 'toolbar',
                    dataType : 'string',
                    width    : 200
                }],
                onrefresh  : function () {
                    self.loadToolbars();
                }
            });

            this.$Grid.addEvents({
                onClick   : this.$gridClick,
                onDblClick: this.$gridDblClick
            });

            this.refresh();
        },

        /**
         * event : on refresh
         */
        $onRefresh: function () {
            this.getGrid().refresh();
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid) {
                return;
            }

            var Body = this.getContent();

            if (!Body) {
                return;
            }


            var size = Body.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        },

        /**
         * get the toolbars
         *
         * @param {Function} [callback] - callback( list )
         * @return {Promise}
         */
        getToolbars: function (callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_editor_get_toolbars', function (list) {
                    resolve(list);

                    if (typeof callback === 'function') {
                        callback(list);
                    }
                }, {
                    onError: reject
                });
            });
        },

        /**
         * Delete a toolbar
         *
         * @param {String} toolbar - name of the toolbar
         * @param {Function} [callback]
         */
        deleteToolbar: function (toolbar, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_editor_toolbar_delete', function (list) {
                    resolve(list);

                    if (typeof callback === 'function') {
                        callback(list);
                    }
                }, {
                    toolbar: toolbar,
                    onError: reject
                });
            });
        },

        /**
         * Add a toolbar
         *
         * @param {String} toolbar - name of the toolbar (myNewToolbar)
         * @param {Function} [callback]
         */
        addToolbar: function (toolbar, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_editor_toolbar_add', function () {
                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }
                }, {
                    toolbar: toolbar,
                    onError: reject
                });
            });
        },

        /**
         * Open the edit sheet for a toolbar
         *
         * @param {String} toolbar
         */
        editToolbar: function (toolbar) {
            this.createSheet({
                title : Locale.get(
                    'quiqqer/system',
                    'editors.settings.editsheet.title',
                    {toolbar: toolbar}
                ),
                icon  : 'fa fa-edit',
                events: {
                    onOpen: function (Sheet) {
                        var Content = Sheet.getContent();

                        require([
                            'controls/editors/ToolbarConfigurator'
                        ], function (Configurator) {
                            var C = new Configurator({
                                toolbar: toolbar
                            });

                            Sheet.addButton({
                                text     : Locale.get(
                                    'quiqqer/system',
                                    'editors.settings.editsheet.btn.save'
                                ),
                                textimage: 'fa fa-save',
                                events   : {
                                    onClick: function () {
                                        C.save();
                                    }
                                }
                            });

                            C.inject(Content);
                        });
                    }
                }
            }).show();
        },

        /**
         * load the toolbars into the grid
         */
        loadToolbars: function () {
            var self = this;

            this.Loader.show();

            this.getToolbars(function (list) {
                if (!list) {
                    self.Loader.hide();
                    return;
                }

                var i, len;
                var data = [];

                for (i = 0, len = list.length; i < len; i++) {
                    data.push({
                        toolbar: list[i]
                    });
                }

                self.getGrid().setData({
                    data: data
                });

                self.Loader.hide();
            });
        },


        /**
         * Windows
         */

        /**
         * Opens the dialog for delete a toolbar
         *
         * @param {String} toolbar - Name of the toolbar
         */
        openDeleteToolbarWindow: function (toolbar) {
            var self = this;

            new QUIConfirm({
                title    : Locale.get(
                    'quiqqer/system',
                    'editors.settings.delete.window.title'
                ),
                text     : Locale.get(
                    'quiqqer/system',
                    'editors.settings.delete.window.text',
                    {toolbar: toolbar}
                ),
                icon     : 'fa fa-trash-o',
                maxHeight: 300,
                maxWidth : 450,
                events   : {
                    onSubmit: function (Win) {
                        self.deleteToolbar(toolbar, function () {
                            Win.close();
                            self.refresh();
                        });
                    }
                }
            }).open();
        },

        /**
         * Opens the dialog for add a toolbar
         */
        openAddToolbarWindow: function () {
            var self = this;

            new QUIPrompt({
                maxHeight  : 300,
                maxWidth   : 450,
                icon       : 'fa fa-plus',
                titleicon  : 'fa fa-plus',
                title      : Locale.get(
                    'quiqqer/system',
                    'editors.settings.add.window.title'
                ),
                information: Locale.get(
                    'quiqqer/system',
                    'editors.settings.add.window.text'
                ),
                events     : {
                    onSubmit: function (toolbar) {
                        self.addToolbar(toolbar, function () {
                            self.refresh();
                            self.editToolbar(toolbar);
                        });
                    }
                }
            }).open();
        },

        /**
         * Grid methods
         */

        /**
         * Return the grid
         *
         * @return {controls/grid/Grid}
         */
        getGrid: function () {
            return this.$Grid;
        },

        /**
         * onclick on the grid
         *
         * @param {option} data - grid data
         */
        $gridClick: function (data) {
            var len    = data.target.selected.length,
                Edit   = this.getButtons('toolbarEdit'),
                Delete = this.getButtons('toolbarDelete');

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
         * dblclick on the grid
         *
         * @param {option} data - grid data
         */
        $gridDblClick: function (data) {
            this.editToolbar(
                data.target.getDataByRow(data.row).toolbar
            );
        }
    });

});
