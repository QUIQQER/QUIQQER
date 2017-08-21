/**
 * @module controls/packages/upload/Window
 *
 * opens the package install upload dialog
 */
define('controls/packages/upload/Window', [

    'qui/QUI',
    'qui/controls/windows/Popup',
    'controls/packages/upload/Upload'

], function (QUI, QUIPopup, PackageUpload) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/packages/upload/Window',

        Binds: [
            '$onOpen'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 800,
            buttons  : false
        },

        initialize: function (options) {
            this.setAttributes({
                title: 'Paket Upload',
                icon : 'fa fa-upload'
            });

            this.parent(options);

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

            this.$TextContainer = new Element('div', {
                html  : 'Laden Sie die Zip-Archive eines oder mehrerer QUIQQER Pakete hoch um diese zu installieren.',
                styles: {
                    'float': 'left',
                    padding: '0 0 20px 0',
                    width  : '100%'
                }
            }).inject(this.getContent());

            var UploadContainer = new Element('div', {
                styles: {
                    'float': 'left',
                    width  : '100%'
                }
            }).inject(this.getContent());

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
                    }
                }
            }).inject(UploadContainer);

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
        }
    });
});
