/**
 * Set priority of the folder children
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/projects/project/media/Priority
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
                buttons    : [
                    {
                        text  : QUILocale.get(
                            'quiqqer/quiqqer',
                            'projects.project.site.media.priority.btn.save'
                        ),
                        events: {
                            onClick: this.save
                        }
                    }
                ],
                columnModel: [
                    {
                        header   : '&nbsp;',
                        dataIndex: 'order',
                        dataType : 'node',
                        width    : 100
                    },
                    {
                        header   : QUILocale.get('quiqqer/quiqqer', 'id'),
                        dataIndex: 'id',
                        dataType : 'integer',
                        width    : 50
                    },
                    {
                        header   : QUILocale.get('quiqqer/quiqqer', 'name'),
                        dataIndex: 'name',
                        dataType : 'string',
                        width    : 300
                    },
                    {
                        header   : QUILocale.get('quiqqer/quiqqer', 'title'),
                        dataIndex: 'title',
                        dataType : 'string',
                        width    : 300
                    },
                    {
                        header   : '&nbsp;',
                        dataIndex: 'preview',
                        dataType : 'node',
                        width    : 60,
                        className: 'project-media-priority-image'
                    }
                ]
            });

            this.Loader.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            return new Promise((resolve, reject) => {
                this.Loader.show();

                if (!this.$Grid || !this.$Elm) {
                    resolve();
                    return;
                }

                const self = this;

                require(['Projects'], function (Projects) {
                    const project = self.getAttribute('project');
                    const Media = Projects.get(project).getMedia();

                    Media.get(self.getAttribute('folderId')).then((Item) => {
                        if (Item.getType() !== 'classes/projects/project/media/Folder') {
                            return;
                        }

                        Item.getChildren().then((children) => {
                            let data = [];
                            let childData = children.data;

                            for (let i = 0, len = childData.length; i < len; i++) {
                                data.push({
                                    order  : new Element('input', {
                                        value : childData[i].priority,
                                        type  : 'number',
                                        styles: {
                                            lineHeight: 22,
                                            padding   : '0 4px',
                                            width     : '95%'
                                        }
                                    }),
                                    id     : childData[i].id,
                                    name   : childData[i].name,
                                    title  : childData[i].title,
                                    preview: new Element('img', {
                                        src: URL_DIR + 'image.php?' + Object.toQueryString({
                                            id       : childData[i].id,
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
                                data : data,
                                page : children.page,
                                total: children.total
                            });

                            self.Loader.hide();

                            resolve();
                        });
                    });
                }, reject);
            });
        },

        /**
         * Save the priorities
         *
         * @return Promise
         */
        save: function () {
            return new Promise((resolve, reject) => {
                this.Loader.show();

                const priorities = [],
                      data       = this.$Grid.getData();

                for (let i = 0, len = data.length; i < len; i++) {
                    priorities.push({
                        id      : data[i].id,
                        priority: parseInt(data[i].order.value)
                    });
                }

                QUIAjax.post('ajax_media_folder_setPriorities', () => {
                    this.refresh().then(resolve);
                }, {
                    project   : this.getAttribute('project'),
                    folderId  : this.getAttribute('folderId'),
                    priorities: JSON.encode(priorities),
                    onError   : reject
                });
            });
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid || !this.$Elm) {
                return;
            }

            const size = this.$Elm.getSize();

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
