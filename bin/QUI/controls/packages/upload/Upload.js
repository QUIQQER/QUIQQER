/**
 * @module controls/packages/upload/Upload
 *
 * Install a package via it's archive package
 */
define('controls/packages/upload/Upload', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/upload/Form'

], function (QUI, QUIControl, UploadForm) {
    "use strict";

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
                Packages.updateWithLocalServer().then(function () {
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
        }
    });
});
