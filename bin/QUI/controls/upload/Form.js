/**
 * A file upload formular
 * the control creates a upload formular
 * the formular sends the selected file to the upload manager
 *
 * @module controls/upload/Form
 * @author www.pcsg.de (Henning Leutz)
 *
 * @fires onAdd [this, File]
 * @fires onBegin [this]
 * @fires onCancel
 * @fires onComplete [this]
 * @fires onSubmit [Array, this]
 * @fires onInputDestroy
 * @fires onDragenter [event, DOMNode, controls/upload/Form]
 * @fires onDragleave [event, DOMNode, controls/upload/Form]
 * @fires onDragend [event, DOMNode, controls/upload/Form]
 * @fires onDrop [event, files, Elm, Upload]
 * @fires onError [ qui/controls/messages/Error }
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/utils/Progressbar
 * @require qui/controls/buttons/Button
 * @require utils/Media
 * @require classes/request/Upload
 * @require Locale
 * @require css!controls/upload/Form.css
 */
define('controls/upload/Form', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/utils/Progressbar',
    'qui/controls/buttons/Button',
    'utils/Media',
    'classes/request/Upload',
    'Locale',

    'css!controls/upload/Form.css'

], function (QUI, QUIControl, QUIProgressbar, QUIButton, MediaUtils, Upload, Locale) {
    "use strict";

    var lg = 'quiqqer/system';

    /**
     * @class controls/upload/Form
     *
     * @param {Object} options
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/upload/Form',

        options: {
            action      : URL_LIB_DIR + 'QUI/Upload/bin/upload.php',
            method      : 'POST', // form method
            maxuploads  : false,  // how many uploads are allowed
            multible    : false,  // are multible uploads allowed?
            sendbutton  : false,  // insert a send button
            cancelbutton: false,  // insert a cancel button
            styles      : false
        },

        Binds: [
            '$onFileUploadFinish',
            '$onFileUploadRefresh',
            '$onError'
        ],

        /**
         * constructor
         *
         * @fires onSubmit [FileList, this]
         * @fires onChange [FileList, this]
         */
        initialize: function (options) {
            if (typeof options.params !== 'undefined') {
                this.setParams(options.params);
            }

            var self = this;

            this.parent(options);

            this.$Add     = null;
            this.$Elm     = null;
            this.$Form    = null;
            this.$Frame   = null;
            this.$Buttons = null;
            this.$BgText  = null;

            this.$enabled = true;
            this.$files   = {};
            this.$params  = {};

            this.$Progress = null;
            this.$Info     = null;

            this.addEvents({
                onDestroy: function () {
                    if (self.$Form) {
                        self.$Form.destroy();
                    }

                    if (self.$Frame) {
                        self.$Frame.destroy();
                    }
                },

                onInputDestroy: function () {
                    var elms = self.$Form.getElements('input[type="file"]');

                    if (!elms.length) {
                        self.$BgText.setStyles({
                            display: null
                        });

                        moofx(self.$BgText).animate({
                            opacity: 1
                        });
                    }

                    if (self.$Add &&
                        (self.getAttribute('maxuploads') === false ||
                        self.getAttribute('maxuploads').toInt() > elms.length)) {
                        self.$Add.enable();
                    }
                }
            });
        },

        /**
         * Add a param to the param list
         * This param would be send with the form
         *
         * @method controls/upload/Form#addParam
         *
         * @param {String} param         - param name
         * @param {String|Number|Boolean} value - param value
         */
        setParam: function (param, value) {
            this.$params[param] = value;
        },

        /**
         * Adds params to the param list
         *
         * @param {Object} params - list of params
         */
        setParams: function (params) {
            for (var n in params) {
                if (params.hasOwnProperty(n)) {
                    this.addParam(n, params[n]);
                }
            }
        },

        /**
         * Return a form param
         *
         * @return {Boolean|Number|String|Object} Form parameter
         */
        getParam: function (n) {
            if (typeof this.$params[n] !== 'undefined') {
                return this.$params[n];
            }

            return false;
        },

        /**
         * Return the form param
         *
         * @return {Object} list of params
         */
        getParams: function () {
            return this.$params;
        },

        /**
         * refreshs the info display
         */
        refreshDisplay: function () {
            if (!this.getAttribute('maxuploads')) {
                return;
            }

            this.$Info.set(
                'html',
                Locale.get(lg, 'upload.form.info.max.text', {
                    count: Object.getLength(this.$files),
                    max  : this.getAttribute('maxuploads')
                })
            );
        },

        /**
         * Create the Form DOMNode
         *
         * @method controls/upload/Form#create
         * @return {HTMLElement} Form
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'controls-upload-form',
                html   : '<div class="controls-upload-info"></div>' +
                '<div class="controls-upload-buttons"></div>' +
                '<div class="controls-upload-bg-text">' +
                Locale.get(lg, 'upload.form.background.text') +
                '</div>',
                styles : {
                    height: 140
                }
            });

            this.$Buttons = this.$Elm.getElement('.controls-upload-buttons');
            this.$BgText  = this.$Elm.getElement('.controls-upload-bg-text');
            this.$Info    = this.$Elm.getElement('.controls-upload-info');

            this.$Frame = new Element('iframe', {
                name  : 'upload' + this.getId(),
                styles: {
                    position: 'absolute',
                    top     : -100,
                    left    : -100,
                    height  : 10,
                    width   : 10
                }
            });

            this.$Frame.inject(document.body);

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Form = this.createForm();
            this.$Form.inject(this.$Elm, 'top');

            this.$dragDropInit();

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            var buttonWidth = '100%';

            if (this.getAttribute('sendbutton') ||
                this.getAttribute('cancelbutton')) {
                buttonWidth = '50%';
            }

            if (this.getAttribute('sendbutton') &&
                this.getAttribute('cancelbutton')) {
                buttonWidth = '33.3%';
            }

            this.$Add = new QUIButton({
                name     : 'add',
                textimage: 'fa fa-hand-o-up',
                text     : Locale.get(lg, 'upload.form.btn.add.text'),
                events   : {
                    onClick: function () {
                        var Input = self.addInput();

                        if (Input) {
                            Input.click();
                        }
                    }
                },
                styles   : {
                    width: buttonWidth
                }
            }).inject(this.$Buttons);


            if (this.getAttribute('sendbutton')) {
                new QUIButton({
                    name     : 'upload',
                    textimage: 'fa fa-upload',
                    text     : Locale.get(lg, 'upload.form.btn.send.text'),
                    alt      : Locale.get(lg, 'upload.form.btn.send.alt'),
                    title    : Locale.get(lg, 'upload.form.btn.send.title'),
                    events   : {
                        onClick: function () {
                            self.submit();
                        }
                    },
                    styles   : {
                        width: buttonWidth
                    }
                }).inject(this.$Buttons);
            }

            if (this.getAttribute('cancelbutton')) {
                new QUIButton({
                    name     : 'cancel',
                    textimage: 'fa fa-remove',
                    text     : Locale.get(lg, 'upload.form.btn.cancel.text'),
                    alt      : Locale.get(lg, 'upload.form.btn.cancel.alt'),
                    title    : Locale.get(lg, 'upload.form.btn.cancel.title'),
                    events   : {
                        onClick: function () {
                            self.fireEvent('cancel');
                        }
                    },
                    styles   : {
                        width: buttonWidth
                    }
                }).inject(this.$Buttons, 'top');
            }

            this.refreshDisplay();

            return this.$Elm;
        },

        /**
         * enable the upload form
         */
        enable: function () {
            this.$enabled = true;
            this.$Add.enable();
            this.getElm().removeClass('controls-upload-form-disabled');
        },

        /**
         * disable the upload form
         */
        disable: function () {
            this.$enabled = false;
            this.$Add.disable();
            this.getElm().addClass('controls-upload-form-disabled');
        },

        /**
         * Return an upload form element
         *
         * @return {HTMLElement}
         */
        createForm: function () {
            var Form = new Element('form', {
                enctype: "multipart/form-data",
                method : this.getAttribute('method'),
                action : this.getAttribute('action'),
                target : 'upload' + this.getId()
            });

            var self = this;

            Form.addEvent('submit', function (event) {
                if (typeof FileReader === 'undefined') {
                    return true;
                }

                event.stop();
                self.submit();
            });

            Form.inject(this.$Elm);

            return Form;
        },

        /**
         * Adds an input upload field to the form
         *
         * @return {Boolean|HTMLInputElement}
         */
        addInput: function () {
            if (!this.$Form) {
                return false;
            }

            if (this.$enabled === false) {
                return false;
            }

            var self = this,
                elms = this.$Form.getElements('input[type="file"]');

            if (this.getAttribute('maxuploads') !== false &&
                elms.length !== 0 &&
                this.getAttribute('maxuploads') <= elms.length) {
                QUI.getMessageHandler(function (MH) {
                    MH.addError(
                        Locale.get(lg, 'upload.form.message.limit', {
                            limit: self.getAttribute('maxuploads')
                        })
                    );
                });

                return false;
            }

            var Container = new Element('div.qui-form-upload');

            var Input = new Element('input', {
                type  : "file",
                name  : "files",
                events: {
                    change: this.$onInputChange.bind(this)
                },
                styles: {
                    display: 'inline'
                }
            }).inject(Container);

            new Element('div', {
                'class': 'controls-upload-form-fileinfo smooth',
                alt    : Locale.get(lg, 'upload.form.btn.change.alt'),
                title  : Locale.get(lg, 'upload.form.btn.change.title'),
                events : {
                    click: function (event) {
                        var Target = event.target;

                        if (!Target.hasClass('.qui-form-upload')) {
                            Target = Target.getParent('.qui-form-upload');
                        }

                        Target.getElement('input[type="file"]').click();
                    }
                }
            }).inject(Container);


            new QUIButton({
                name  : 'remove',
                image : 'fa fa-remove',
                events: {
                    onClick: function () {
                        var fid = Slick.uidOf(Input);

                        if (self.$files[fid]) {
                            delete self.$files[fid];
                        }

                        Container.destroy();
                        self.fireEvent('inputDestroy');
                        self.refreshDisplay();
                    }
                }

            }).inject(Container);


            Container.inject(this.$Form);

            if (this.$Add &&
                this.getAttribute('maxuploads') &&
                this.getAttribute('maxuploads').toInt() <= elms.length + 1) {
                this.$Add.disable();
            }

            return Input;
        },

        /**
         * Add an upload container to the form
         *
         * @param {File} File
         * @param {HTMLElement} [Input] - (optional), Parent Element
         */
        addUpload: function (File, Input) {
            var self = this;

            if (this.$enabled === false) {
                return;
            }

            if (typeof Input === 'undefined') {
                var list = this.$Form.getElements('input:display(inline)');

                if (!list.length) {
                    Input = this.addInput();
                } else {
                    Input = list[0];
                }
            }

            this.$files[Slick.uidOf(Input)] = File;

            var Container = Input.getParent('.qui-form-upload'),
                FileInfo  = Container.getElement('.controls-upload-form-fileinfo');

            FileInfo.set('html', File.name);

            FileInfo.setStyle(
                'background-image',
                'url(' + MediaUtils.getIconByMimeType(File.type) + ')'
            );

            this.refreshDisplay();
            this.fireEvent('add', [this, File]);

            Input.setStyle('display', 'none');
            Container.setStyle('visibility', 'visible');

            moofx(this.$BgText).animate({
                opacity: 0
            }, {
                callback: function () {
                    self.$BgText.setStyle('display', 'none');
                }
            });
        },

        /**
         * Create an info container element
         *
         * @return {HTMLElement}
         */
        createInfo: function () {
            this.$Info = new Element('div', {
                html   : '<div class="file-name">' +
                Locale.get(lg, 'upload.form.info.text') +
                '</div>' +
                '<div class="upload-time"></div>' +
                '<div class="progress"></div>',
                'class': 'upload-manager-file box smooth'
            });

            this.$Progress = new QUIProgressbar({
                startPercentage: 0
            });

            this.$Progress.inject(this.$Info.getElement('.progress'));

            return this.$Info;
        },

        /**
         * Send the formular
         *
         * @method controls/upload/Form#submit
         */
        submit: function () {
            var self = this;

            if (this.$enabled === false) {
                return;
            }

            // FileReader is undefined, so no html5 upload available
            // use the normal upload
            if (typeof FileReader === 'undefined') {
                this.$Form.getElements('input[type="hidden"]').destroy();

                // create the params into the form
                for (var n in this.$params) {
                    if (!this.$params.hasOwnProperty(n)) {
                        continue;
                    }

                    new Element('input', {
                        type : 'hidden',
                        value: this.$params[n],
                        name : n
                    }).inject(this.$Form);
                }

                new Element('input', {
                    type : 'hidden',
                    value: this.getId(),
                    name : 'uploadid'
                }).inject(this.$Form);

                // send upload to the upload manager
                require(['UploadManager'], function (UploadManager) {
                    UploadManager.injectForm(this);
                });

                // and submit the form
                this.$Form.submit();
                this.fireEvent('begin', [this]);

                return;
            }

            this.fireEvent('submit', [this.getFiles(), this]);

            // send to upload manager
            var params = this.getParams(),
                files  = self.getFiles();

            params.events = {
                onComplete: this.finish.bind(this)
            };

            if ("extract" in params && params.extract) {
                var extract = {};

                for (var i = 0, len = files.length; i < len; i++) {
                    extract[files[i].name] = true;
                }

                params.extract = extract;
            }

            if (!files.length) {
                return;
            }

            require(['UploadManager'], function (UploadManager) {
                self.fireEvent('begin', [self]);

                UploadManager.addEvents({
                    onFileComplete     : self.$onFileUploadFinish,
                    onFileUploadRefresh: self.$onFileUploadRefresh,
                    onError            : self.$onError
                });

                self.$Elm.set('html', '');
                self.createInfo().inject(self.$Elm);

                UploadManager.uploadFiles(
                    files,
                    self.getParam('onfinish'),
                    params
                );
            });
        },

        /**
         * Set the status to finish and fires the onFinish Event
         *
         * @param {controls/upload/Form} File
         * @param {Object|Array|String|Boolean} result - result of the upload
         */
        finish: function (File, result) {
            if (this.$Progress) {
                this.$Progress.set(100);
            }

            if (this.$Info) {
                this.$Info.getElement('.file-name').set('html', '');
            }

            this.fireEvent('complete', [this, File, result]);
        },

        /**
         * Return the selected File or FileList object
         *
         * @return {File|null}
         */
        getFile: function () {
            var files = this.getFiles();

            if (files[0]) {
                return files[0];
            }

            return null;
        },

        /**
         * Return the selected FileList
         *
         * @return {Array}
         */
        getFiles: function () {
            var result = [],
                files  = this.$files;

            for (var i in files) {
                if (files.hasOwnProperty(i)) {
                    result.push(files[i]);
                }
            }

            return result;
        },

        /**
         * on upload input change
         *
         * @param {DOMEvent} event
         */
        $onInputChange: function (event) {
            var Target = event.target,
                files  = Target.files;

            if (typeof files === 'undefined') {
                return;
            }

            if (!files.length || !files[0]) {
                return;
            }

            this.addUpload(files[0], Target);
            this.fireEvent('change', [this.getFiles(), this]);
        },

        /**
         * Initialize the DragDrop events if drag drop supported
         */
        $dragDropInit: function () {
            var self = this;

            new Upload([this.$Form], {

                onDragenter: function (event, Elm) {
                    if (self.$enabled === false) {
                        return;
                    }

                    if (Elm.nodeName !== 'FORM') {
                        Elm = Elm.getParent('form');
                    }

                    Elm.addClass('highlight');
                    self.fireEvent('dragenter', [event, Elm, self]);
                    event.stop();
                },

                onDragleave: function (event, Elm) {
                    if (self.$enabled === false) {
                        return;
                    }

                    if (Elm.nodeName !== 'FORM') {
                        return;
                    }

                    Elm.removeClass('highlight');
                    self.fireEvent('dragleave', [event, Elm, self]);
                },

                onDragend: function (event, Elm) {
                    if (self.$enabled === false) {
                        return;
                    }

                    if (Elm.nodeName !== 'FORM') {
                        Elm = Elm.getParent('form');
                    }

                    Elm.removeClass('highlight');
                    self.fireEvent('dragend', [event, Elm, self]);
                },

                onDrop: function (event, files, Elm) {
                    if (self.$enabled === false) {
                        return;
                    }

                    if (Elm.nodeName !== 'FORM') {
                        Elm = Elm.getParent('form');
                    }

                    Elm.removeClass('highlight');

                    if (!files.length) {
                        return;
                    }

                    if (self.getAttribute('maxuploads') !== false &&
                        files.length > self.getAttribute('maxuploads')) {
                        QUI.getMessageHandler().then(function (MH) {
                            MH.addError(
                                Locale.get(lg, 'upload.form.message.limit', {
                                    limit: self.getAttribute('maxuploads')
                                })
                            );
                        });
                    }

                    // add to the list
                    for (var i = 0, len = files.length; i < len; i++) {
                        self.addUpload(files[i]);
                    }

                    self.fireEvent('drop', [event, files, Elm, self]);
                    self.fireEvent('dragend', [event, Elm, self]);
                }
            });
        },

        /**
         * event : upload refresh
         */
        $onFileUploadRefresh: function (UploadManager, percent) {
            if (!this.$Progress) {
                return;
            }

            this.$Progress.set(percent);
        },

        /**
         * Event, if one upload file is finish
         */
        $onFileUploadFinish: function () {

        },

        /**
         * Event on error
         *
         * @param {Object} Error - qui/controls/messages/Error
         */
        $onError: function (Error) {
            if (this.$Progress) {
                this.$Progress.hide();
            }

            if (this.$Info) {
                this.$Info.getElement('.file-name').set('html', '');
            }

            var self = this;

            new Element('div', {
                'class': 'box',
                html   : Error.getMessage(),
                styles : {
                    clear     : 'both',
                    'float'   : 'left',
                    width     : '100%',
                    padding   : '10px 0 0 20px',
                    background: 'url(' + URL_BIN_DIR + '16x16/error.png) no-repeat left center'
                },
                events : {
                    click: function () {
                        self.getElm().set('html', '');

                        self.$files = {};
                        self.$Form  = self.createForm();
                        self.$Form.inject(self.getElm());
                    }
                }
            }).inject(this.$Info);

            this.fireEvent('error', [this, Error]);
        }
    });
});
