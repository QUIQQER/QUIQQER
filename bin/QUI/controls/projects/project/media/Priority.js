/**
 * Set priority of the folder children
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/projects/project/media/Priority
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require controls/grid/Grid
 * @require Locale
 * @require Ajax
 */

define('controls/projects/project/media/Priority', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'controls/grid/Grid',
    'Locale',
    'Ajax'

], function (QUI, QUIControl, QUILoader, Grid, QUILocale, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/media/Priority',

        Binds: [
            '$onResize',
            '$onInject',
            'save'
        ],

        options: {
            project : '',
            folderId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();

            this.addEvents({
                onResize: this.$onResize,
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                styles: {
                    height: '100%'
                }
            });

            this.$Grid = new Grid(this.$Elm, {
                buttons    : [{
                    text  : QUILocale.get(
                        'quiqqer/system',
                        'projects.project.site.media.priority.btn.save'
                    ),
                    events: {
                        onClick: this.save
                    }
                }],
                columnModel: [{
                    header   : '&nbsp;',
                    dataIndex: 'order',
                    dataType : 'node',
                    width    : 100
                }, {
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'integer',
                    width    : 50
                }, {
                    header   : QUILocale.get('quiqqer/system', 'name'),
                    dataIndex: 'name',
                    dataType : 'string',
                    width    : 300
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 300
                }, {
                    header   : '&nbsp;',
                    dataIndex: 'preview',
                    dataType : 'node',
                    width    : 60,
                    className: 'project-media-priority-image'
                }]
            });

            this.Loader.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            return new Promise(function (resolve, reject) {
                this.Loader.show();

                if (!this.$Grid || !this.$Elm) {
                    resolve();
                    return;
                }

                var self = this;

                require(['Projects'], function (Projects) {
                    var project = self.getAttribute('project');
                    var Media   = Projects.get(project).getMedia();

                    Media.get(self.getAttribute('folderId')).then(function (Item) {
                        if (Item.getType() !== 'classes/projects/project/media/Folder') {
                            return;
                        }

                        Item.getChildren().then(function (children) {
                            var data = [];

                            for (var i = 0, len = children.length; i < len; i++) {
                                data.push({
                                    order  : new Element('input', {
                                        value : children[i].priority,
                                        type  : 'number',
                                        styles: {
                                            lineHeight: 22,
                                            padding   : '0 4px',
                                            width     : '95%'
                                        }
                                    }),
                                    id     : children[i].id,
                                    name   : children[i].name,
                                    title  : children[i].title,
                                    preview: new Element('img', {
                                        src: URL_DIR + 'image.php?' + Object.toQueryString({
                                            id       : children[i].id,
                                            project  : project,
                                            quiadmin : 1,
                                            maxheight: 60,
                                            maxwidth : 60,
                                            hash     : String.uniqueID()
                                        })
                                    })
                                });
                            }

                            self.$Grid.setData({
                                data: data
                            });

                            self.Loader.hide();

                            resolve();
                        });
                    });

                }, reject);
            }.bind(this));
        },

        /**
         * Save the priorities
         *
         * @return Promise
         */
        save: function () {
            return new Promise(function (resolve, reject) {
                this.Loader.show();

                var priorities = [],
                    data       = this.$Grid.getData();

                for (var i = 0, len = data.length; i < len; i++) {
                    priorities.push({
                        id      : data[i].id,
                        priority: (data[i].order.value).toInt()
                    });
                }

                QUIAjax.post('ajax_media_folder_setPriorities', function () {
                    this.refresh().then(resolve);
                }.bind(this), {
                    project   : this.getAttribute('project'),
                    folderId  : this.getAttribute('folderId'),
                    priorities: JSON.encode(priorities),
                    onError   : reject
                });
            }.bind(this));
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid || !this.$Elm) {
                return;
            }

            var size = this.$Elm.getSize();

            this.$Grid.setHeight(size.y);
            this.$Grid.setWidth(size.x);
            this.$Grid.resize();
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$onResize();
            this.refresh();
        }
    });
});
