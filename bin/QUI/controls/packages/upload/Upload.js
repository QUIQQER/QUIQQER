/**
 * @module controls/packages/upload/Upload
 *
 * Install a package via it's archive package
 */
define('controls/packages/upload/Upload', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/upload/Form',
    'Locale',

    'css!controls/packages/upload/Upload.css'

], function (QUI, QUIControl, QUIButton, UploadForm, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/upload/Upload',

        Binds: [
            '$onFinished'
        ],

        options: {
            height: false,
            width : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Upload = null;
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            var self = this,
                Elm  = this.parent();

            Elm.addClass('qui-packages-upload');

            this.$Upload = new UploadForm({
                sendbutton  : true,
                cancelbutton: true,
                accept      : 'application/zip',
                events      : {
                    onCancel: function () {
                        self.fireEvent('cancel', [self]);
                    },

                    onAdd: function (Form, File) {
                        self.fireEvent('add', [self, File]);
                    },

                    onBegin: function () {
                        self.fireEvent('begin', [self]);
                    },

                    onComplete: function () {
                        self.fireEvent('complete', [self]);
                    },

                    onFinished: function () {
                        self.$onFinished();
                    },

                    onSubmit: function () {
                        self.fireEvent('submit', [self]);
                    },

                    onInputDestroy: function () {
                        self.fireEvent('inputDestroy', [self]);
                    },

                    onDragenter: function () {
                        self.fireEvent('dragenter', [self]);
                    },

                    onDragleave: function () {
                        self.fireEvent('dragleave', [self]);
                    },

                    onDragend: function () {
                        self.fireEvent('dragend', [self]);
                    },

                    onDrop: function () {
                        self.fireEvent('drop', [self]);
                    },

                    onError: function (err) {
                        self.fireEvent('error', [self, err]);
                    }
                }
            }).inject(Elm);

            this.$Upload.setParam('onfinish', 'ajax_system_packages_upload_package');
            this.$Upload.setParam('extract', 0);

            return Elm;
        },

        /**
         * Resize the control
         */
        resize: function () {
            if (!this.$Upload) {
                return;
            }

            if (this.getAttribute('height')) {
                this.getElm().setStyle('height', this.getAttribute('height'));
                this.$Upload.getElm().setStyle('height', this.getAttribute('height'));
            }

            if (this.getAttribute('width')) {
                this.getElm().setStyle('width', this.getAttribute('width'));
                this.$Upload.getElm().setStyle('width', this.getAttribute('width'));
            }
        },

        /**
         * event: on upload finished
         * Starts the update process
         */
        $onFinished: function () {
            var self = this;

            require(['Packages'], function (Packages) {
                Packages.getNotInstalledPackages().then(function (packages) {
                    if (!packages.length) {
                        return Packages.updateWithLocalServer();
                    }

                    return self.$notInstalledPackagesFound(packages);
                }).then(function () {
                    self.fireEvent('finished', [self]);
                }).catch(function (err) {
                    self.fireEvent('finished', [self]);
                    console.error(err);

                    if (typeOf(err) === 'qui/controls/messages/Error') {
                        QUI.getMessageHandler().then(function (MH) {
                            MH.add(err);
                        });
                    }
                });
            });
        },

        /**
         * open the not installed packages dialog
         *
         * @param {Array} packages
         * @return {Promise}
         */
        $notInstalledPackagesFound: function (packages) {
            var self = this;

            this.fireEvent('notInstalledPackagesFound', [this]);

            return new Promise(function (resolve) {
                moofx(self.$Upload.getElm()).animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        var Container = new Element('label', {
                            'class': 'qui-packages-upload-notInstalled',
                            html   : QUILocale.get(lg, 'dialog.packages.install.upload.notInstalled.text'),
                            styles : {
                                opacity: 0
                            }
                        }).inject(self.$Elm);

                        var i, len, title, version;

                        for (i = 0, len = packages.length; i < len; i++) {
                            title   = packages[i].title || packages[i].name;
                            version = packages[i].version;

                            title = title + ' (' + version + ')';

                            new Element('div', {
                                'class': 'qui-packages-upload-notInstalled-package',
                                html   : '<input type="checkbox" ' +
                                    'name="' + packages[i].name + '" ' +
                                    'data-version="' + version + '" /> ' + title
                            }).inject(Container);
                        }

                        Container.getElements('input').set('checked', true);

                        new QUIButton({
                            text  : QUILocale.get(lg, 'dialog.packages.install.upload.notInstalled.installBtn'),
                            styles: {
                                marginRight: 10
                            },
                            events: {
                                onClick: function () {
                                    var checked = Container.getElements('input:checked');
                                    var results = checked.map(function (Checkbox) {
                                        return {
                                            name   : Checkbox.get('name'),
                                            version: Checkbox.get('data-version')
                                        };
                                    });

                                    self.$install(results).then(resolve);
                                }
                            }
                        }).inject(Container);

                        new QUIButton({
                            text  : QUILocale.get('quiqqer/system', 'cancel'),
                            events: {
                                onClick: resolve
                            }
                        }).inject(Container);

                        moofx(Container).animate({
                            opacity: 1
                        }, {
                            duration: 200
                        });
                    }
                });
            });
        },

        /**
         * Install a list of packages
         *
         * @param {Array} packages
         * @return {Promise}
         */
        $install: function (packages) {
            if (!packages.length) {
                return Promise.resolve();
            }

            var self = this;

            return new Promise(function (resolve) {
                self.fireEvent('begin', [self]);

                require(['Packages'], function (Packages) {
                    var list = {};

                    for (var i = 0, len = packages.length; i < len; i++) {
                        if (typeof packages[i].name === 'undefined') {
                            list[packages[i]] = false;
                            continue;
                        }

                        list[packages[i].name] = packages[i].version;
                    }

                    Packages.installLocalPackages(list).then(resolve);
                });
            });
        }
    });
});
