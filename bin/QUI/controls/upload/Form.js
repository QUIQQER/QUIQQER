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
 * @fires onCancel [this, File]
 * @fires onComplete [this]
 * @fires onFinished [this]
 * @fires onSubmit [Array, this]
 * @fires onInputDestroy
 * @fires onDragenter [event, DOMNode, controls/upload/Form]
 * @fires onDragleave [event, DOMNode, controls/upload/Form]
 * @fires onDragend [event, DOMNode, controls/upload/Form]
 * @fires onDrop [event, files, Elm, Upload]
 * @fires onError [qui/controls/messages/Error]
 */
define('controls/upload/Form', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/utils/Progressbar',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',
    'utils/Media',
    'classes/request/Upload',
    'Locale',

    'css!controls/upload/Form.css'

], function (QUI, QUIControl, QUIProgressbar, QUIButton, QUILoader, MediaUtils, Upload, Locale) {
    "use strict";

    const lg = 'quiqqer/quiqqer';
    let delayClick = false;

    /**
     * @class controls/upload/Form
     *
     * @param {Object} options
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type: 'controls/upload/Form',

        options: {
            name: false,
            action: URL_LIB_DIR + 'QUI/Upload/bin/upload.php',
            method: 'POST', // form method
            maxuploads: false,  // how many uploads are allowed
            multiple: false,  // are multiple uploads allowed?
            sendbutton: false,  // insert a send button
            cancelbutton: false,  // insert a cancel button
            styles: false,
            pauseAllowed: true,
            contextMenu: true, // context menu for the file upload

            // look
            typeOfLook: 'DragDrop',    // DragDrop, Icon, Single
            typeOfLookIcon: 'fa fa-upload' // works only with typeOfLook: Icon
        },

        Binds: [
            '$onFileUploadFinish',
            '$onFileUploadRefresh',
            '$onFileUploadCancel',
            '$onError',
            '$onInject',
            '$filterEmptyUploadElements',
            '$onInputChange'
        ],

        /**
         * constructor
         *
         * @fires onSubmit [FileList, this]
         * @fires onChange [FileList, this]
         */
        initialize: function (options) {
            if (typeof options === 'undefined') {
                options = {};
            }

            if (typeof options.params !== 'undefined') {
                this.setParams(options.params);
            }

            // quiqqer/quiqqer#772
            if (typeof options.multible !== 'undefined') {
                options.multiple = options.multible;
                delete options.multible;
            }

            const self = this;

            if (typeof options.name !== 'undefined' && options.name === '') {
                options.name = false;
            }

            this.parent(options);

            this.$Add = null;
            this.$Elm = null;
            this.$Form = null;
            this.$Frame = null;
            this.$Buttons = null;
            this.$BgText = null;
            this.$SendButton = null;

            this.$enabled = true;
            this.$files = {};
            this.$params = {};
            this.$finished = false;

            this.$Progress = null;
            this.$Info = null;

            this.$isDropping = false;

            this.addEvents({
                onInject: this.$onInject,
                onDestroy: function () {
                    if (self.$Form) {
                        self.$Form.destroy();
                    }

                    if (self.$Frame) {
                        self.$Frame.destroy();
                    }
                },

                onInputDestroy: function () {
                    const elms = self.$Form.getElements('input[type="file"]');

                    if (!elms.length) {
                        self.$BgText.setStyles({
                            display: null
                        });

                        (function () {
                            self.$Form.setStyle('cursor', 'pointer');
                            self.$formClick = false;
                        }).delay(100);

                        moofx(self.$BgText).animate({
                            opacity: 1
                        });
                    }

                    if (self.$Add &&
                        (self.getAttribute('maxuploads') === false ||
                            parseInt(self.getAttribute('maxuploads')) > elms.length)) {
                        self.$Add.enable();
                    }
                }
            });

            if (this.getAttribute('typeOfLook') === 'Icon' ||
                this.getAttribute('typeOfLook') === 'Single') {
                this.setAttribute('maxuploads', 1);
            }
        },

        /**
         * Add a param to the param list
         * This param would be send with the form
         *
         * @method controls/upload/Form#setParam
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
            for (const n in params) {
                if (params.hasOwnProperty(n)) {
                    this.setParam(n, params[n]);
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
            if (this.$SendButton) {
                this.$SendButton.disable();
                this.$SendButton.getElm().removeClass('btn-green');

                if (Object.getLength(this.$files)) {
                    this.$SendButton.enable();
                    this.$SendButton.getElm().addClass('btn-green');
                }
            }

            if (!this.getAttribute('maxuploads')) {
                return;
            }

            this.$Info.set(
                'html',
                Locale.get(lg, 'upload.form.info.max.text', {
                    count: Object.getLength(this.$files),
                    max: this.getAttribute('maxuploads')
                })
            );
        },

        /**
         * Create the Form DOMNode
         *
         * @method controls/upload/Form#create
         * @return {HTMLElement|Element} Form
         */
        create: function () {
            const self = this;

            this.$Elm = new Element('div', {
                'class': 'controls-upload-form',
                html: '<div class="controls-upload-info"></div>' +
                    '<div class="controls-upload-buttons"></div>' +
                    '<div class="controls-upload-bg-text">' +
                    Locale.get(lg, 'upload.form.background.text') +
                    '</div>',
                styles: {
                    height: 140
                }
            });

            this.Loader = new QUILoader().inject(this.$Elm);

            this.$Buttons = this.$Elm.getElement('.controls-upload-buttons');
            this.$BgText = this.$Elm.getElement('.controls-upload-bg-text');
            this.$Info = this.$Elm.getElement('.controls-upload-info');

            this.$createIconView();
            this.$createSingleView();

            this.$Frame = new Element('iframe', {
                name: 'upload' + this.getId(),
                styles: {
                    position: 'absolute',
                    top: -100,
                    left: -100,
                    height: 10,
                    width: 10
                }
            });

            this.$Frame.inject(document.body);

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Form = this.createForm();
            this.$Form.inject(this.$Elm, 'top');

            this.$Form.removeEvents('click');
            this.$Form.setStyle('cursor', 'pointer');
            this.$formClick = false;

            this.$Form.addEvent('click', function (event) {
                if (self.$formClick || delayClick) {
                    return;
                }

                delayClick = true;

                (function () {
                    delayClick = false;
                }).delay(100);

                if (Object.getLength(self.$files)) {
                    return;
                }

                if (event.target.nodeName === 'FORM') {
                    event.stop();
                }

                const Input = self.addInput();

                if (Input) {
                    Input.click();
                }
            });

            this.$dragDropInit();

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Add = new QUIButton({
                name: 'add',
                textimage: 'fa fa-hand-o-up',
                text: Locale.get(lg, 'upload.form.btn.add.text'),
                events: {
                    onClick: function () {
                        self.cleanup();

                        const Input = self.addInput();

                        if (Input) {
                            self.$formClick = true;
                            Input.click();

                            (function () {
                                self.$formClick = false;
                            }).delay(200);
                        }
                    }
                },
                styles: {
                    width: '30%'
                }
            }).inject(this.$Buttons);


            if (this.getAttribute('sendbutton')) {
                this.$SendButton = new QUIButton({
                    name: 'upload',
                    textimage: 'fa fa-upload',
                    text: Locale.get(lg, 'upload.form.btn.send.text'),
                    alt: Locale.get(lg, 'upload.form.btn.send.alt'),
                    title: Locale.get(lg, 'upload.form.btn.send.title'),
                    disabled: true,
                    events: {
                        onClick: function () {
                            self.submit();
                        }
                    },
                    styles: {
                        'float': 'right',
                        width: '30%'
                    }
                }).inject(this.$Buttons);
            }

            if (this.getAttribute('cancelbutton')) {
                new QUIButton({
                    name: 'cancel',
                    textimage: 'fa fa-remove',
                    text: Locale.get(lg, 'upload.form.btn.cancel.text'),
                    alt: Locale.get(lg, 'upload.form.btn.cancel.alt'),
                    title: Locale.get(lg, 'upload.form.btn.cancel.title'),
                    events: {
                        onClick: function () {
                            self.fireEvent('cancel');
                        }
                    },
                    styles: {
                        'float': 'right',
                        width: '20%'
                    }
                }).inject(this.$Buttons);
            }

            this.refreshDisplay();

            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.resize();
        },

        /**
         * resize the form
         */
        resize: function () {
            const buttonsHeight = this.$Buttons.getSize().y;
            const inforHeight = this.$Info.getSize().y;
            const height = buttonsHeight + inforHeight;

            this.$Form.setStyle('height', 'calc(100% - ' + height + 'px)');
        },

        /**
         * Create the icon view
         */
        $createIconView: function () {
            if (this.getAttribute('typeOfLook') !== 'Icon') {
                return;
            }

            this.$Buttons.setStyle('display', 'none');
            this.$BgText.setStyle('display', 'none');
            this.$Info.setStyle('display', 'none');

            const IconForm = new Element('form', {
                'class': 'controls-upload-form-icon',
                action: "",
                acceptCharset: "utf-8",
                enctype: "multipart/form-data",
                html: '<input type="file" />'
            }).inject(this.$Elm);

            if (this.getAttribute('name')) {
                IconForm.getElement('input').set('name', this.getAttribute('name'));
            }

            const self = this;
            const UploadIcon = new Element('span').inject(IconForm);
            const Input = IconForm.getElement('input');

            UploadIcon.addClass('controls-upload-icon');
            UploadIcon.addClass(this.getAttribute('typeOfLookIcon'));
            UploadIcon.addEvent('click', function (e) {
                e.stop();

                if (e.target.nodeName === 'INPUT') {
                    return;
                }

                require(['qui/utils/Elements'], function (ElementUtils) {
                    ElementUtils.simulateEvent(
                        IconForm.getElement('input'),
                        'click'
                    );
                });
            });

            if (this.getAttribute('sendbutton')) {
                new Element('button', {
                    'class': 'controls-upload-form-submit',
                    html: '' +
                        '<span class="fa fa-arrow-circle-o-right"></span>' +
                        '<span>' +
                        Locale.get(lg, 'control.upload.icon.submit') +
                        '</span>',
                    events: {
                        click: function (e) {
                            e.stop();

                            if (!Input.files.length) {
                                return;
                            }

                            self.$files = Input.files;
                            self.submit();
                        }
                    }
                }).inject(IconForm);
            }

            this.$Elm.addClass('controls-upload-form--icon');
            this.$Elm.setStyle('height', null);
        },

        /**
         * create the single view
         */
        $createSingleView: function () {
            if (this.getAttribute('typeOfLook') !== 'Single') {
                return;
            }

            this.$Buttons.setStyle('display', 'none');
            this.$BgText.setStyle('display', 'none');
            this.$Info.setStyle('display', 'none');

            this.$Elm.addClass('controls-upload-form--single');
            this.$Elm.setStyle('height', null);

            const IconForm = new Element('form', {
                'class': 'controls-upload-form-single',
                action: "",
                acceptCharset: "utf-8",
                enctype: "multipart/form-data",
                html: '<input type="file" />'
            }).inject(this.$Elm);

            if (this.getAttribute('name')) {
                IconForm.getElement('input').set('name', this.getAttribute('name'));
            }

            const self = this;
            const Input = IconForm.getElement('input');

            Input.addEvent('change', function () {
                if (!Input.files.length) {
                    return;
                }

                switch (Input.files[0].type) {
                    case 'image/jpeg':
                    case 'image/jpg':
                    case 'image/png':
                    case 'image/gif':
                        self.$imagePreview(Input.files[0]);
                        break;

                    default:
                        self.getElm().getElement(
                            '.controls-upload-form-single-container-preview'
                        ).setStyle('background-image', '');
                }

                const SubmitBtn = IconForm.getElement('button');

                if (SubmitBtn) {
                    SubmitBtn.disabled = false;
                }

                IconForm.getElement(
                    '.controls-upload-form-single-container-select'
                ).set('html', Input.files[0].name);

                self.$files = Input.files;
            });

            new Element('div', {
                'class': 'controls-upload-form-single-container',
                html: '' +
                    '<div class="controls-upload-form-single-container-preview"></div>' +
                    '<div class="controls-upload-form-single-container-select">' +
                    '   <span class="controls-upload-form-single-container-select-placeholder">' +
                    Locale.get(lg, 'control.upload.placeholder') +
                    '   </span>' +
                    '</div>',
                events: {
                    click: function (e) {
                        e.stop();

                        if (e.target.nodeName === 'INPUT') {
                            return;
                        }

                        require(['qui/utils/Elements'], function (ElementUtils) {
                            ElementUtils.simulateEvent(Input, 'click');
                        });
                    }
                }
            }).inject(IconForm);

            if (!this.getAttribute('sendbutton')) {
                return;
            }

            new Element('button', {
                disabled: true,
                'class': 'controls-upload-form-submit',
                html: '' +
                    '<span class="fa fa-arrow-circle-o-right"></span>' +
                    '<span>' +
                    Locale.get(lg, 'control.upload.icon.submit') +
                    '</span>',
                events: {
                    click: function (e) {
                        e.stop();

                        if (!Input.files.length) {
                            return;
                        }

                        self.$files = Input.files;
                        self.submit();
                    }
                }
            }).inject(IconForm);
        },

        /**
         *
         * @param file
         */
        $imagePreview: function (file) {
            // preview
            const reader = new FileReader();
            const Preview = this.getElm().getElement(
                '.controls-upload-form-single-container-preview'
            );

            reader.onload = function (e) {
                Preview.setStyle('background-image', 'url("' + e.target.result + '")');
            };

            reader.readAsDataURL(file);
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
            const Form = new Element('form', {
                enctype: "multipart/form-data",
                method: this.getAttribute('method'),
                action: this.getAttribute('action'),
                target: 'upload' + this.getId()
            });

            const self = this;

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

            const self = this;

            // Remove empty inputs
            if (!self.$isDropping) {
                this.$filterEmptyUploadElements();
            }

            const elms = this.$Form.getElements('input[type="file"]');

            if (this.getAttribute('maxuploads') !== false &&
                elms.length !== 0 &&
                this.getAttribute('maxuploads') <= elms.length) {
                return elms[0];
            }

            const Container = new Element('div.qui-form-upload');

            Container.addEvents({
                mouseenter: function () {
                    const Button = Container.getElement('button');
                    const Btn = QUI.Controls.getById(Button.get('data-quiid'));

                    Btn.enable();
                    Container.getElement('button').setStyle('display', null);
                },

                mouseleave: function () {
                    const Button = Container.getElement('button');
                    const Btn = QUI.Controls.getById(Button.get('data-quiid'));

                    Btn.disable();
                    Container.getElement('button').setStyle('display', 'none');
                }
            });

            const Input = new Element('input', {
                type: "file",
                name: "files",
                multiple: !!this.getAttribute('multiple'),
                events: {
                    change: this.$onInputChange.bind(this)
                },
                styles: {
                    display: 'none'
                }
            }).inject(Container);

            if (this.getAttribute('name')) {
                Input.set('name', this.getAttribute('name'));
            }

            if (this.getAttribute('accept')) {
                Input.accept = this.getAttribute('accept');
            }

            new Element('div', {
                'class': 'controls-upload-form-fileinfo smooth',
                alt: Locale.get(lg, 'upload.form.btn.change.alt'),
                title: Locale.get(lg, 'upload.form.btn.change.title'),
                events: {
                    click: function (event) {
                        event.stop();

                        let Target = event.target;

                        if (!Target.hasClass('.qui-form-upload')) {
                            Target = Target.getParent('.qui-form-upload');
                        }

                        const File = Target.getElement('input[type="file"]');

                        File.focus();
                        File.click.delay(200, File);
                    }
                }
            }).inject(Container);


            new QUIButton({
                name: 'remove',
                image: 'fa fa-remove',
                Container: Container,
                disabled: true,
                styles: {
                    display: 'none'
                },
                events: {
                    onClick: function (Btn, event) {
                        event.stop();

                        const Container = Btn.getAttribute('Container');
                        const fid = Slick.uidOf(Input);

                        if (self.$files[fid]) {
                            delete self.$files[fid];
                        }

                        Container.destroy();

                        self.$finished = false;
                        self.fireEvent('inputDestroy');
                        self.refreshDisplay();
                    }
                }
            }).inject(Container);

            Container.inject(this.$Form);

            return Input;
        },

        /**
         * Add an upload container to the form
         *
         * @param {File} File
         * @param {HTMLElement} [Input] - (optional), Parent Element
         */
        addUpload: function (File, Input) {
            const self = this;

            if (this.$enabled === false) {
                return;
            }

            if (typeof Input === 'undefined') {
                const list = this.$Form.getElements('input:display(inline)');

                if (!list.length) {
                    Input = this.addInput();
                } else {
                    Input = list[0];
                }
            }

            if (Input === false) {
                return;
            }

            this.$finished = false;

            this.$files[Slick.uidOf(Input)] = File;

            const Container = Input.getParent('.qui-form-upload'),
                FileInfo = Container.getElement('.controls-upload-form-fileinfo');

            FileInfo.set('html', File.name);

            FileInfo.setStyle(
                'background-image',
                'url(' + MediaUtils.getIconByMimeType(File.type) + ')'
            );


            this.refreshDisplay();
            this.fireEvent('add', [
                this,
                File
            ]);

            //Input.setStyle('display', 'none');
            Container.setStyle('visibility', 'visible');

            moofx(this.$BgText).animate({
                opacity: 0
            }, {
                callback: function () {
                    self.$BgText.setStyle('display', 'none');
                    self.$Form.setStyle('cursor', null);
                    self.$formClick = true;
                }
            });

            this.$filterEmptyUploadElements();
        },

        /**
         * Cleanup the form
         * Removes empty file entries
         */
        cleanup: function () {
            const emptyUploads = this.$Form.getElements('div.qui-form-upload').filter(function (Upload) {
                const Input = Upload.getElement('input');

                if (typeof Input.files === 'undefined') {
                    return false;
                }

                return !Input.files.length;
            });

            let i, len, button, Button;

            for (i = 0, len = emptyUploads.length; i < len; i++) {
                button = emptyUploads[i].getElement('button');
                Button = QUI.Controls.getById(button.get('data-quiid'));

                if (Button) {
                    Button.click();
                } else {
                    // put it to the end
                    emptyUploads[i].inject(emptyUploads[i].getParent());
                }
            }
        },

        /**
         * Create an info container element
         *
         * @return {HTMLElement}
         */
        createInfo: function () {
            this.$Info = new Element('div', {
                html: '<div class="file-name">' +
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
            const self = this;

            if (this.$enabled === false) {
                return;
            }

            // FileReader is undefined, so no html5 upload available
            // use the normal upload
            if (typeof FileReader === 'undefined') {
                this.$Form.getElements('input[type="hidden"]').destroy();

                // create the params into the form
                for (const n in this.$params) {
                    if (!this.$params.hasOwnProperty(n)) {
                        continue;
                    }

                    new Element('input', {
                        type: 'hidden',
                        value: this.$params[n],
                        name: n
                    }).inject(this.$Form);
                }

                new Element('input', {
                    type: 'hidden',
                    value: this.getId(),
                    name: 'uploadid'
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

            this.Loader.show();
            this.fireEvent('submit', [
                this.getFiles(),
                this
            ]);

            // send to upload manager
            const params = this.getParams(),
                files = self.getFiles();

            params.events = {
                onComplete: this.finish.bind(this)
            };

            if ("extract" in params && params.extract) {
                const extract = {};

                for (let i = 0, len = files.length; i < len; i++) {

                    extract[files[i].name] = true;
                }

                params.extract = extract;
            }

            if (!files.length) {
                return;
            }

            if (files.length >= 2) {
                require(['classes/request/BulkUpload'], (BulkUpload) => {
                    new BulkUpload({
                        parentId: params.parentid,
                        project: params.project,
                        phpOnFinish: params.onfinish,
                        phpOnStart: params.onstart,
                        events: {
                            onFinish: () => {
                                this.fireEvent('finished', [this]);
                                this.fireEvent('complete', [this]);
                            }
                        }
                    }).upload(files);
                });

                return;
            }

            require(['UploadManager'], function (UploadManager) {
                self.fireEvent('begin', [self]);

                UploadManager.addEvents({
                    onFileComplete: self.$onFileUploadFinish,
                    onFileCancel: self.$onFileUploadCancel,
                    onFileUploadRefresh: self.$onFileUploadRefresh,
                    onError: self.$onError
                });

                UploadManager.setAttribute('pauseAllowed', self.getAttribute('pauseAllowed'));
                UploadManager.setAttribute('contextMenu', self.getAttribute('contextMenu'));

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

            this.fireEvent('complete', [
                this,
                File,
                result
            ]);

            // already triggered
            if (this.$finished) {
                return;
            }

            require(['UploadManager'], function (UploadManager) {
                const files = UploadManager.$files;

                for (let i = 0, len = files.length; i < len; i++) {
                    if (!files[i].isFinished()) {
                        return;
                    }
                }

                this.Loader.hide();
                this.fireEvent('finished', [this]);
                this.$finished = true;
            }.bind(this));
        },

        /**
         * Return the selected File or FileList object
         *
         * @return {File|null}
         */
        getFile: function () {
            const files = this.getFiles();

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
            const result = [],
                files = this.$files;

            for (const i in files) {
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
            const Target = event.target,
                files = Target.files;

            event.stop();

            if (typeof files === 'undefined') {
                Target.destroy();
                return;
            }

            if (!files.length || !files[0]) {
                Target.destroy();
                return;
            }

            // check max length
            const maxUploads = this.getAttribute('maxuploads');
            const currentFileCount = Object.getLength(this.$files);

            if (maxUploads && maxUploads <= currentFileCount) {
                QUI.getMessageHandler().then((MH) => {
                    MH.addError(
                        Locale.get(lg, 'upload.form.message.limit', {
                            limit: maxUploads
                        })
                    );
                });

                Target.destroy();
                return;
            }

            if (files.length === 1) {
                this.addUpload(files[0], Target);
                this.fireEvent('change', [
                    this.getFiles(),
                    this
                ]);
                return;
            }

            for (let i = 0, len = files.length; i < len; i++) {
                if (Object.getLength(this.$files) >= maxUploads) {
                    QUI.getMessageHandler().then((MH) => {
                        MH.addError(
                            Locale.get(lg, 'upload.form.message.limit', {
                                limit: maxUploads
                            })
                        );
                    });

                    break;
                }

                if (i === 0) {
                    this.addUpload(files[i], Target);
                    continue;
                }

                this.addUpload(files[i]);
            }

            this.fireEvent('change', [
                this.getFiles(),
                this
            ]);
        },

        /**
         * Initialize the DragDrop events if drag drop supported
         */
        $dragDropInit: function () {
            const self = this;

            const Up = new Upload([this.$Form]);

            Up.addEvents({

                onDragenter: function (event, Elm) {
                    console.log('drag enter');

                    if (self.$enabled === false) {
                        return;
                    }

                    if (Elm.nodeName !== 'FORM') {
                        Elm = Elm.getParent('form');
                    }

                    Elm.addClass('highlight');
                    self.fireEvent('dragenter', [
                        event,
                        Elm,
                        self
                    ]);
                    event.stop();
                },

                onDragleave: function (event, Elm) {
                    console.log('drag leave');

                    if (self.$enabled === false) {
                        return;
                    }

                    if (Elm.nodeName !== 'FORM') {
                        return;
                    }

                    Elm.removeClass('highlight');
                    self.fireEvent('dragleave', [
                        event,
                        Elm,
                        self
                    ]);
                },

                onDragend: function (event, Elm) {
                    console.log('drag end');

                    if (self.$enabled === false) {
                        return;
                    }

                    if (Elm.nodeName !== 'FORM') {
                        Elm = Elm.getParent('form');
                    }

                    Elm.removeClass('highlight');
                    self.fireEvent('dragend', [
                        event,
                        Elm,
                        self
                    ]);
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

                    self.$isDropping = true;

                    // add to the list
                    for (let i = 0, len = files.length; i < len; i++) {
                        self.addUpload(files[i]);
                    }

                    self.fireEvent('drop', [
                        event,
                        files,
                        Elm,
                        self
                    ]);
                    self.fireEvent('dragend', [
                        event,
                        Elm,
                        self
                    ]);

                    self.$filterEmptyUploadElements();
                    self.$isDropping = false;
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
         * @param UploadManager
         * @param File
         */
        $onFileUploadCancel: function (UploadManager, File) {
            this.fireEvent('cancel', [
                this,
                File
            ]);
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
                const FileNameElm = this.$Info.getElement('.file-name');

                if (FileNameElm) {
                    FileNameElm.set('html', '');
                }
            }

            const self = this;

            new Element('div', {
                'class': 'box',
                html: Error.getMessage(),
                styles: {
                    clear: 'both',
                    'float': 'left',
                    width: '100%',
                    padding: '10px 0 0 20px',
                    background: 'url(' + URL_BIN_DIR + '16x16/error.png) no-repeat left center'
                },
                events: {
                    click: function () {
                        const Old = self.getElm();

                        Old.set('html', '');
                        self.$files = {};
                        self.$finished = false;
                        self.create().replaces(Old);
                    }
                }
            }).inject(this.$Info);

            QUI.getMessageHandler().then(function (MH) {
                MH.add(Error);
            });

            this.fireEvent('error', [
                this,
                Error
            ]);
        },

        /**
         * Removes upload elements that have no selected file.
         *
         * @return {void}
         */
        $filterEmptyUploadElements: function () {
            let elms = this.$Form.getElements('div.qui-form-upload');

            // Remove empty inputs
            elms = elms.filter((FileElm) => {
                const FileInfoElm = FileElm.getElement('.controls-upload-form-fileinfo');

                if (!FileInfoElm.innerHTML) {
                    FileElm.destroy();
                    return false;
                }

                return true;
            });

            if (!this.$Add) {
                return;
            }

            const maxFileUploads = parseInt(this.getAttribute('maxuploads'));

            if (!maxFileUploads) {
                return;
            }

            if (maxFileUploads <= elms.length) {
                this.$Add.disable();
            } else {
                this.$Add.enable();
            }
        }
    });
});
