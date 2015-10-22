/**
 * The main trash panel
 *
 * @module controls/trash/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Select
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require controls/projects/Popup
 * @require controls/projects/project/media/Popup
 * @require Projects
 * @require Ajax
 * @require Locale
 */
define('controls/trash/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Select',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'controls/projects/Popup',
    'controls/projects/project/media/Popup',
    'Projects',
    'Ajax',
    'Locale'

], function () {
    "use strict";

    var lg = 'quiqqer/system';

    var QUI          = arguments[0],
        QUIPanel     = arguments[1],
        QUISelect    = arguments[2],
        QUIConfirm   = arguments[3],
        Grid         = arguments[4],
        ProjectPopup = arguments[5],
        MediaPopup   = arguments[6],
        Projects     = arguments[7],
        Ajax         = arguments[8],
        Locale       = arguments[9];

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/trash/Panel',

        Binds: [
            'openDestroyWindow',
            'openClearWindow',
            'openRestoreWindow',
            '$onCreate',
            '$onSelectChange',
            '$onResize',
            '$gridClick'
        ],

        options: {
            icon : 'fa fa-trash-o icon-trash',
            title: Locale.get(lg, 'trash.panel.title')
        },

        initialize: function (options) {
            this.parent(options);

            this.$MediaGrid   = null;
            this.$ProjectGrid = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            var self = this;

            this.Loader.show();

            this.$Select = new QUISelect({
                name  : 'trash-select',
                events: {
                    onChange: this.$onSelectChange
                }
            });

            this.addButton(this.$Select);

            this.addButton({
                type: 'seperator'
            });

            this.addButton({
                name     : 'removeAll',
                text     : Locale.get(lg, 'trash.panel.btn.delete.all'),
                textimage: 'icon-remove',
                events   : {
                    onClick: this.openClearWindow
                }
            });

            this.addButton({
                name     : 'remove',
                text     : Locale.get(lg, 'trash.panel.btn.delete'),
                textimage: 'icon-remove',
                disabled : true,
                events   : {
                    onClick: this.openDestroyWindow
                }
            });

            this.addButton({
                name     : 'restore',
                text     : Locale.get(lg, 'trash.panel.btn.restore'),
                textimage: 'icon-reply-all',
                disabled : true,
                events   : {
                    onClick: this.openRestoreWindow
                }
            });

            Projects.getList(function (result) {
                var i, len, langs, project;

                for (project in result) {
                    if (!result.hasOwnProperty(project)) {
                        continue;
                    }

                    langs = result[project].langs.split(',');

                    for (i = 0, len = langs.length; i < len; i++) {
                        self.$Select.appendChild(
                            project + ' ( ' + langs[i] + ' )',
                            project + ',' + langs[i],
                            'icon-home'
                        );
                    }

                    self.$Select.appendChild(
                        project + ' ( Media )',
                        project + ',media',
                        'fa fa-picture-o icon-picture'
                    );
                }

                self.$Select.setValue(
                    self.$Select.firstChild().getAttribute('value')
                );

                self.Loader.hide();
            });
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            var Body = this.getContent();

            if (!Body) {
                return;
            }

            var size = Body.getSize();

            if (this.$MediaGrid) {
                this.$MediaGrid.setHeight(size.y - 40);
                this.$MediaGrid.setWidth(size.x - 40);
            }

            if (this.$ProjectGrid) {
                this.$ProjectGrid.setHeight(size.y - 40);
                this.$ProjectGrid.setWidth(size.x - 40);
            }
        },

        /**
         * event : select on change
         *
         * @param {String} value - value of the select control
         */
        $onSelectChange: function (value) {
            value = value.split(',');

            if (value[1] == 'media') {
                this.$displayProjectMediaTrash(value[0]);
                return;
            }

            this.$displayProjectTrash(value[0], value[1]);
        },

        /**
         * Destroy the grids
         */
        $clear: function () {
            if (this.$MediaGrid) {
                this.$MediaGrid.destroy();
                this.$MediaGrid = null;
            }

            if (this.$ProjectGrid) {
                this.$ProjectGrid.destroy();
                this.$ProjectGrid = null;
            }

            this.getButtons('remove').disable();
            this.getButtons('restore').disable();
        },

        openClearWindow: function () {
            var self   = this,
                type   = 'project',
                params = this.$Select.getValue().split(',');

            if (this.$Select.getValue().match('media')) {
                type = 'media';
            }

            new QUIConfirm({
                title      : Locale.get(lg, 'trash.panel.window.delete.all.title'),
                icon       : 'icon-remove',
                text       : Locale.get(lg, 'trash.panel.window.delete.all.text'),
                information: Locale.get(lg, 'trash.panel.window.delete.all.information'),
                maxHeight  : 300,
                maxWidth   : 450,
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        if (type == 'project') {
                            self.clearProjectItems(params[0], params[1]).then(function () {
                                Win.close();

                                self.$ProjectGrid.refresh();
                                self.getButtons('remove').disable();
                                self.getButtons('restore').disable();
                            });
                        } else {
                            self.clearMediaItems(params[0], params[1]).then(function () {
                                Win.close();

                                self.$MediaGrid.refresh();
                                self.getButtons('remove').disable();
                                self.getButtons('restore').disable();
                            });
                        }

                        //
                        //if (type == 'project') {
                        //    self.destroyProjectItems(params[0], params[1], ids, function () {
                        //        Win.close();
                        //
                        //        self.$ProjectGrid.refresh();
                        //        self.getButtons('remove').disable();
                        //        self.getButtons('restore').disable();
                        //    });
                        //
                        //} else {
                        //    self.destroyMediaItems(params[0], ids, function () {
                        //        Win.close();
                        //
                        //        self.$MediaGrid.refresh();
                        //        self.getButtons('remove').disable();
                        //        self.getButtons('restore').disable();
                        //    });
                        //}
                    }
                }
            }).open();
        },

        /**
         * Opens the deletion window
         */
        openDestroyWindow: function () {
            if (!this.$Select.getValue() || this.$Select.getValue() === '') {
                return;
            }

            var i, len, selectedData, information;

            var self   = this,
                type   = 'project',
                ids    = [],
                params = this.$Select.getValue().split(',');

            if (this.$Select.getValue().match('media')) {
                type = 'media';
            }

            if (this.$MediaGrid) {
                selectedData = this.$MediaGrid.getSelectedData();
            }

            if (this.$ProjectGrid) {
                selectedData = this.$ProjectGrid.getSelectedData();
            }

            information = '<ul>';

            for (i = 0, len = selectedData.length; i < len; i++) {
                information = information + '<li>' +
                              selectedData[i].id + ' ' + selectedData[i].name +
                              '</li>';

                ids.push(selectedData[i].id);
            }

            information = information + '<ul>';

            new QUIConfirm({
                title      : Locale.get(lg, 'trash.panel.window.delete.title'),
                icon       : 'icon-remove',
                text       : Locale.get(lg, 'trash.panel.window.delete.text'),
                information: information,
                maxHeight  : 300,
                maxWidth   : 450,
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        if (type == 'project') {
                            self.destroyProjectItems(params[0], params[1], ids, function () {
                                Win.close();

                                self.$ProjectGrid.refresh();
                                self.getButtons('remove').disable();
                                self.getButtons('restore').disable();
                            });

                        } else {
                            self.destroyMediaItems(params[0], ids, function () {
                                Win.close();

                                self.$MediaGrid.refresh();
                                self.getButtons('remove').disable();
                                self.getButtons('restore').disable();
                            });
                        }
                    }
                }
            }).open();
        },

        /**
         * Opens the restore window
         */
        openRestoreWindow: function () {
            if (!this.$Select.getValue() || this.$Select.getValue() === '') {
                return;
            }

            var i, len, selectedData;

            var self   = this,
                type   = 'project',
                ids    = [],
                params = this.$Select.getValue().split(',');

            if (this.$Select.getValue().match('media')) {
                type = 'media';
            }

            if (this.$MediaGrid) {
                selectedData = this.$MediaGrid.getSelectedData();
            }

            if (this.$ProjectGrid) {
                selectedData = this.$ProjectGrid.getSelectedData();
            }


            for (i = 0, len = selectedData.length; i < len; i++) {
                ids.push(selectedData[i].id);
            }

            if (type === 'project') {
                new ProjectPopup({
                    project             : params[0],
                    lang                : params[1],
                    disableProjectSelect: true,
                    information         : Locale.get(lg, 'trash.panel.window.restore.site.question'),
                    events              : {
                        onSubmit: function (Popup, params) {
                            var project  = params.project,
                                lang     = params.lang,
                                parentId = params.ids[0];

                            self.restoreProjectItems(project, lang, parentId, ids, function () {
                                Popup.close();

                                self.$ProjectGrid.refresh();
                                self.getButtons('remove').disable();
                                self.getButtons('restore').disable();
                            });
                        }
                    }
                }).open();

                return;
            }

            // media file restore
            new MediaPopup({
                project         : params[0],
                selectable_types: ['folder'],
                events          : {
                    onSubmit: function (Popup, data) {
                        var project  = params[0],
                            parentId = data.id;

                        self.restoreProjectMediaItems(project, parentId, ids, function () {
                            self.$MediaGrid.refresh();
                            self.getButtons('remove').disable();
                            self.getButtons('restore').disable();
                        });
                    }
                }
            }).open();
        },


        /**
         * project methods
         */

        /**
         * display the trash of a project
         *
         * @param {String} project - name of the project
         * @param {String} lang . lang of the project
         */
        $displayProjectTrash: function (project, lang) {
            this.Loader.show();
            this.$clear();

            var self    = this,
                Content = this.getContent();

            Content.set('html', '');

            var Container = new Element('div').inject(Content);

            this.$ProjectGrid = new Grid(Container, {
                columnModel      : [{
                    header   : Locale.get(lg, 'id'),
                    dataIndex: 'id',
                    dataType : 'string',
                    width    : 50
                }, {
                    header   : Locale.get(lg, 'name'),
                    dataIndex: 'name',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : Locale.get(lg, 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : Locale.get(lg, 'type'),
                    dataIndex: 'type',
                    dataType : 'string',
                    width    : 100
                }, {
                    header   : Locale.get(lg, 'e_date'),
                    dataIndex: 'e_date',
                    dataType : 'date',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'user_id'),
                    dataIndex: 'e_user',
                    dataType : 'integer',
                    width    : 100
                }],
                pagination       : true,
                selectable       : true,
                multipleSelection: true,
                onrefresh        : function () {
                    self.$loadProjectTrash(project, lang);
                }
            });

            this.$ProjectGrid.addEvents({
                onClick: this.$gridClick
            });

            this.$onResize();
            this.$ProjectGrid.refresh();
        },

        /**
         * load the project trash data into the grid
         *
         * @param {String} project - name of the project
         * @param {String} lang - lang of the project
         */
        $loadProjectTrash: function (project, lang) {
            var self    = this,
                options = this.$ProjectGrid.options;

            this.Loader.show();

            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_trash_sites', function (data) {
                    self.$ProjectGrid.setData(data);
                    self.Loader.hide();

                    resolve();
                }, {
                    project: JSON.encode({
                        name: project,
                        lang: lang
                    }),
                    params : JSON.encode({
                        page   : options.page,
                        perPage: options.perPage
                    }),
                    onError: reject
                });
            });
        },

        /**
         * Destroy the ids in an project trash
         *
         * @param {String} project - Name of the project
         * @param {String} lang - Lang of the project
         * @param {Array} ids - Array of the site ids
         * @param {Function} [callback] - callback function on finish
         * @return Promise
         */
        destroyProjectItems: function (project, lang, ids, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_trash_destroy', function () {
                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }
                }, {
                    project: JSON.encode({
                        name: project,
                        lang: lang
                    }),
                    ids    : JSON.encode(ids),
                    onError: reject
                });
            });
        },

        /**
         * Restore the ids into the parentId
         *
         * @param {String} project
         * @param {String} lang
         * @param {Number} parentId - ID of the parent id
         * @param {Array} restoreIds - IDs to the restored ids
         * @param {Function} [callback] - callback function on finish
         * @return Promise
         */
        restoreProjectItems: function (project, lang, parentId, restoreIds, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_trash_restore', function () {

                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }

                }, {
                    project : JSON.encode({
                        name: project,
                        lang: lang
                    }),
                    parentid: parentId,
                    ids     : JSON.encode(restoreIds),
                    onError : reject
                });
            });
        },

        /**
         * Destroy all deleted project items
         *
         * @param {String} project
         * @param {String} lang
         * @param {Function} [callback] - callback function on finish
         */
        clearProjectItems: function (project, lang, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_trash_clear', function () {

                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }
                }, {
                    project: JSON.encode({
                        name: project,
                        lang: lang
                    }),
                    onError: reject
                });
            });
        },

        /**
         * Media methods
         */

        /**
         * display the trash of a media
         *
         * @param {String} project - name of the project
         */
        $displayProjectMediaTrash: function (project) {
            this.Loader.show();
            this.$clear();

            var self    = this,
                Content = this.getContent();

            Content.set('html', '');

            var Container = new Element('div').inject(Content);

            this.$MediaGrid = new Grid(Container, {
                columnModel      : [{
                    header   : Locale.get(lg, 'id'),
                    dataIndex: 'id',
                    dataType : 'string',
                    width    : 50
                }, {
                    header   : Locale.get(lg, 'name'),
                    dataIndex: 'name',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : Locale.get(lg, 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : Locale.get(lg, 'type'),
                    dataIndex: 'type',
                    dataType : 'string',
                    width    : 100
                }, {
                    header   : Locale.get(lg, 'e_date'),
                    dataIndex: 'e_date',
                    dataType : 'date',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'user_id'),
                    dataIndex: 'e_user',
                    dataType : 'integer',
                    width    : 100
                }],
                pagination       : true,
                selectable       : true,
                multipleSelection: true,
                onrefresh        : function () {
                    self.$loadProjectMediaTrash(project);
                }
            });

            this.$MediaGrid.addEvents({
                onClick: this.$gridClick
            });

            this.$onResize();
            this.$MediaGrid.refresh();
        },

        /**
         * load the media trash data into the grid
         *
         * @param {String} project - name of the project
         * @return Promise
         */
        $loadProjectMediaTrash: function (project) {
            return new Promise(function (resolve, reject) {
                var self    = this,
                    options = this.$MediaGrid.options;

                this.Loader.show();

                Ajax.get('ajax_trash_media', function (data) {
                    self.$MediaGrid.setData(data);
                    self.Loader.hide();

                    resolve();
                }, {
                    project: JSON.encode({
                        name: project
                    }),
                    params : JSON.encode({
                        page   : options.page,
                        perPage: options.perPage
                    }),
                    onError: reject
                });
            }.bind(this));
        },

        /**
         * Destroy the ids in an project media trash
         *
         * @param {String} project      - Name of the project
         * @param {Array} ids           - Array of the site ids
         * @param {Function} [callback] - (optional), callback function on finish
         * @return Promise
         */
        destroyMediaItems: function (project, ids, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_trash_media_destroy', function () {
                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }
                }, {
                    project: JSON.encode({
                        name: project
                    }),
                    ids    : JSON.encode(ids),
                    onError: reject
                });
            });
        },

        /**
         * Restore the ids into the parentId
         *
         * @param {String} project
         * @param {Number} parentId     - ID of the parent id
         * @param {Array} restoreIds    - IDs to the restored ids
         * @param {Function} [callback] - (optional), callback function on finish
         * @return Promise
         */
        restoreProjectMediaItems: function (project, parentId, restoreIds, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_trash_media_restore', function () {
                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }
                }, {
                    project : JSON.encode({
                        name: project
                    }),
                    parentid: parentId,
                    ids     : JSON.encode(restoreIds),
                    onError : reject
                });
            });
        },

        /**
         * Destroy all deleted media items
         *
         * @param {String} project
         * @param {Function} [callback] - callback function on finish
         * @return Promise
         */
        clearMediaItems: function (project, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.post('ajax_trash_media_clear', function () {
                    resolve();

                    if (typeof callback === 'function') {
                        callback();
                    }
                }, {
                    project: JSON.encode({
                        name: project
                    }),
                    onError: reject
                });
            });
        },

        /**
         * grid methods
         */

        /**
         * event : on grid click
         */
        $gridClick: function (data) {
            var len     = data.target.selected.length,
                Remove  = this.getButtons('remove'),
                Restore = this.getButtons('restore');

            if (len === 0) {
                Remove.disable();
                Restore.disable();
                return;
            }

            Remove.enable();
            Restore.enable();

            data.evt.stop();
        }
    });
});
