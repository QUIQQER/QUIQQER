define('controls/projects/project/media/Clipboard', [], function () {
    "use strict";

    let ActivePanel = null;

    return {
        setActivePanel: function (Panel) {
            ActivePanel = Panel;
        },

        isActivePanel: function (Panel) {
            return ActivePanel === Panel;
        },

        clearActivePanel: function (Panel) {
            if (ActivePanel === Panel) {
                ActivePanel = null;
            }
        },

        isEditableTarget: function (target) {
            while (target) {
                if (target.isContentEditable) {
                    return true;
                }

                if (target.tagName) {
                    const tagName = target.tagName.toLowerCase();

                    if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
                        return true;
                    }
                }

                target = target.parentNode;
            }

            return false;
        },

        getClipboardImageExtension: function (mimeType) {
            switch (mimeType) {
                case 'image/jpeg':
                    return 'jpg';

                case 'image/gif':
                    return 'gif';

                case 'image/webp':
                    return 'webp';

                case 'image/bmp':
                    return 'bmp';

                case 'image/svg+xml':
                    return 'svg';

                case 'image/tiff':
                    return 'tiff';

                case 'image/png':
                default:
                    return 'png';
            }
        },

        shouldNormalizeClipboardImageName: function (file) {
            if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                return false;
            }

            if (!file.name) {
                return true;
            }

            return /^image(?:\.[a-z0-9]+)?$/i.test(file.name);
        },

        normalizeClipboardFiles: function (files) {
            const now = new Date();
            const pad = function (value) {
                return value.toString().padStart(2, '0');
            };

            const timestamp = [
                now.getFullYear(),
                pad(now.getMonth() + 1),
                pad(now.getDate())
            ].join('') + '-' + [
                pad(now.getHours()),
                pad(now.getMinutes()),
                pad(now.getSeconds())
            ].join('');

            let imageCounter = 0;

            return files.map(function (file) {
                if (!this.shouldNormalizeClipboardImageName(file) || typeof File === 'undefined') {
                    return file;
                }

                imageCounter++;

                const extension = this.getClipboardImageExtension(file.type);
                let fileName = 'clipboard-' + timestamp;

                if (imageCounter > 1) {
                    fileName = fileName + '-' + imageCounter;
                }

                fileName = fileName + '.' + extension;

                return new File([file], fileName, {
                    type: file.type,
                    lastModified: file.lastModified || Date.now()
                });
            }.bind(this));
        },

        getFilesFromPasteEvent: function (event) {
            let clipboardData = event.clipboardData || null;

            if (!clipboardData && event.event) {
                clipboardData = event.event.clipboardData || null;
            }

            if (!clipboardData) {
                return [];
            }

            const files = [];

            if (clipboardData.items && clipboardData.items.length) {
                for (let i = 0, len = clipboardData.items.length; i < len; i++) {
                    const item = clipboardData.items[i];

                    if (item.kind !== 'file') {
                        continue;
                    }

                    const file = item.getAsFile();

                    if (file) {
                        files.push(file);
                    }
                }
            }

            if (files.length) {
                return this.normalizeClipboardFiles(files);
            }

            if (clipboardData.files && clipboardData.files.length) {
                for (let i = 0, len = clipboardData.files.length; i < len; i++) {
                    files.push(clipboardData.files[i]);
                }
            }

            return this.normalizeClipboardFiles(files);
        },

        getImageFileFromPasteEvent: function (event) {
            const files = this.getFilesFromPasteEvent(event);

            for (let i = 0, len = files.length; i < len; i++) {
                if (files[i].type && files[i].type.indexOf('image/') === 0) {
                    return files[i];
                }
            }

            return null;
        }
    };
});
