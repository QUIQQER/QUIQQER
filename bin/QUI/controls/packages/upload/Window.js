/**
 * @module controls/packages/upload/Window
 *
 * opens the package install upload dialog
 */
define('controls/packages/upload/Window', [

    'qui/QUI',
    'qui/controls/windows/Popup',
    'qui/controls/buttons/Button',
    'controls/packages/upload/Upload',
    'controls/packages/upload/List',
    'Locale',

    'css!controls/packages/upload/Window.css'

], function (QUI, QUIPopup, QUIButton, PackageUpload, PackageList, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/packages/upload/Window',

        Binds: [
            '$onOpen',
            'openList'
        ],

        options: {
            maxHeight: 450,
            maxWidth : 900,
            buttons  : false
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'dialog.packages.install.upload.title'),
                icon : 'fa fa-upload'
            });

            this.parent(options);

            this.$UploadContainer = null;
            this.$ListContainer   = null;

            this.$Upload        = null;
            this.$TextContainer = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onResize: this.$onResize
            });
        },

        /**
         * event: on open
         */
        $onOpen: function () {
            var self = this;

            this.$UploadContainer = new Element('div', {
                styles: {
                    left    : 20,
                    position: 'absolute',
                    top     : 20,
                    width   : 'calc(100% - 40px)'
                }
            }).inject(this.getContent());

            this.$ListContainer = new Element('div', {
                styles: {
                    display : 'none',
                    opacity : 0,
                    position: 'absolute',
                    width   : 'calc(100% - 40px)'
                }
            }).inject(this.getContent());


            // upload
            this.$TextContainer = new Element('div', {
                html  : QUILocale.get(lg, 'dialog.packages.install.upload.description'),
                styles: {
                    'float': 'left',
                    padding: '0 0 20px 0',
                    width  : '100%'
                }
            }).inject(this.$UploadContainer);

            var UploadContainer = new Element('div', {
                styles: {
                    'float': 'left',
                    width  : '100%'
                }
            }).inject(this.$UploadContainer);

            this.$Upload = new PackageUpload({
                events: {
                    onCancel: function () {
                        self.close();
                    },

                    onBegin: function () {
                        self.Loader.show();
                    },

                    onFinished: function () {
                        self.close();
                    },

                    onNotInstalledPackagesFound: function () {
                        self.$TextContainer.setStyle('display', 'none');
                        self.Loader.hide();
                    }
                }
            }).inject(UploadContainer);


            // list
            this.$ListButton = new QUIButton({
                icon   : 'fa fa-list',
                'class': 'quiqqer-packages-upload-uploadedList-listBtn',
                title  : QUILocale.get(lg, 'dialog.packages.install.upload.listBtn.title'),
                events : {
                    onClick: function (Btn) {
                        if (Btn.isActive()) {
                            self.openUpload();
                        } else {
                            self.openList();
                        }
                    }
                }
            }).inject(this.$Title);

            new PackageList().inject(this.$ListContainer);


            this.$onResize();
        },

        /**
         * event: on resize
         */
        $onResize: function () {
            if (!this.$Upload) {
                return;
            }

            var size     = this.getContent().getSize(),
                textSize = this.$TextContainer.getSize();

            var height = size.y - textSize.y;

            this.$Upload.setAttribute('height', height - 50);
            this.$Upload.resize();
        },

        /**
         * Open the package list
         *
         * @return {Promise}
         */
        openList: function () {
            var self = this;

            this.getContent().setStyle('overflow', 'hidden');

            return new Promise(function (resolve) {
                moofx(self.$UploadContainer).animate({
                    left   : -20,
                    opacity: 0
                }, {
                    duration: 300,
                    callback: function () {
                        self.$UploadContainer.setStyle('display', 'none');

                        self.$ListContainer.setStyle('opacity', 0);
                        self.$ListContainer.setStyle('display', null);
                        self.$ListContainer.setStyle('left', 60);

                        moofx(self.$ListContainer).animate({
                            left   : 20,
                            opacity: 1
                        }, {
                            callback: function () {
                                self.getContent().setStyle('overflow', null);
                                self.$ListButton.setActive();
                                resolve();
                            }
                        });
                    }
                });
            });
        },

        /**
         * Open the upload
         *
         * @return {Promise}
         */
        openUpload: function () {
            var self = this;

            this.getContent().setStyle('overflow', 'hidden');

            return new Promise(function (resolve) {
                moofx(self.$ListContainer).animate({
                    left   : 60,
                    opacity: 0
                }, {
                    duration: 300,
                    callback: function () {
                        self.$ListContainer.setStyle('display', 'none');

                        self.$UploadContainer.setStyle('opacity', 0);
                        self.$UploadContainer.setStyle('display', null);
                        self.$UploadContainer.setStyle('left', -20);

                        moofx(self.$UploadContainer).animate({
                            left   : 20,
                            opacity: 1
                        }, {
                            callback: function () {
                                self.getContent().setStyle('overflow', null);
                                self.$ListButton.setNormal();
                                resolve();
                            }
                        });
                    }
                });
            });
        }
    });
});
