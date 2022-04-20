/**
 * Image input
 *
 * @module controls/projects/project/media/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onChange [ {self}, {String} ]
 */
define('controls/projects/project/media/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/icons/Confirm',
    'qui/utils/String',
    'controls/projects/project/media/Popup',
    'Projects',
    'Ajax',
    'Locale',

    'css!controls/projects/project/media/Input.css'

], function (QUIControl, QUIButton, IconConfirm, QUIStringUtils, MediaPopup, Projects, Ajax, Locale) {
    "use strict";

    const lg = 'quiqqer/quiqqer';

    /**
     * @class controls/projects/Input
     *
     * @param {Object} options
     * @param {HTMLElement} [Input] - (optional) if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/media/Input',

        Binds: [
            '$onInject',
            '$openCSSClassDialog'
        ],

        options: {
            name  : '',
            styles: false,

            fileid              : false,
            breadcrumb          : true,     // you can specified if the breadcrumb is shown or not
            selectable_types    : false,    // you can specified which types are selectable
            selectable_mimetypes: false,    // you can specified which mime types are selectable
            cssclasses          : false,    // css classes can be selected
            mediabutton         : true,     // images can be selected
            ratio_warning       : false     // if the image has not an 1:1 ration, a warning icon is displayed
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input = Input || null;

            this.$Path = null;
            this.$Preview = null;
            this.$Project = null;

            this.$CSSButton = null;
            this.$MediaButton = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Set the internal project
         *
         * @param {Object} Project - classes/projects/project
         */
        setProject: function (Project) {
            if (typeOf(Project) === 'string') {
                Project = Projects.get(Project);
            }

            this.$Project = Project;

            if (this.$Input) {
                this.$Input.set('data-project', Project.getName());
            }
        },

        /**
         * Create the DOMNode
         *
         * @return {HTMLElement|Element}
         */
        create: function () {
            const self = this;

            this.$Elm = new Element('div', {
                'class'     : 'qui-controls-project-media-input box',
                'data-quiid': this.getId(),
                'data-qui'  : 'controls/projects/project/media/Input'
            });

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name: this.getAttribute('name')
                }).inject(this.$Elm);
            } else {
                this.$Elm.wraps(this.$Input);
            }

            if (this.$Input.getAttribute('data-qui-options-ratio_warning')) {
                this.setAttribute('ratio_warning', this.$Input.getAttribute('data-qui-options-ratio_warning'));
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Input.set('data-quiid', this.getId());

            this.$Input.setStyles({
                display: 'none'
            });

            this.$Input.addEvent('change', function () {
                self.setValue(this.value);
            });

            if (this.$Input.value !== '') {
                const urlParams = QUIStringUtils.getUrlParams(this.$Input.value);

                if ("project" in urlParams) {
                    this.setProject(urlParams.project);
                }
            }

            // preview
            this.$Preview = new Element('div', {
                html   : '&nbsp;',
                'class': 'qui-controls-project-media-input-preview',
                events : {
                    mouseenter: function () {
                        let pos = self.$Preview.getPosition();
                        let size = 250;

                        let winSize = window.QUI.getWindowSize();
                        let top = pos.y + 30;
                        let left = pos.x + 30;

                        if (top + size > winSize.y) {
                            top = pos.y - size;
                        }

                        if (left + size > winSize.x) {
                            top = pos.x - size;
                        }

                        const Parent = new Element('div', {
                            'class': 'qui-controls-project-media-input-preview--view',
                            html   : '<div></div>',
                            styles : {
                                backgroundColor: '#fff',
                                borderRadius   : 5,
                                boxShadow      : '1px 1px 5px -2px rgba(0, 0, 0, 0.75)',
                                height         : 250,
                                width          : 250,
                                padding        : 5,
                                position       : 'absolute',
                                top            : top,
                                left           : left,
                                zIndex         : 10
                            }
                        }).inject(document.body);

                        let url = self.$Preview.getStyle('background');

                        if (url.indexOf('__') !== -1) {
                            let p = url.split('__');
                            url = p[0] + '.' + p[1].split('.')[1];
                        }

                        new Element('div', {
                            styles: {
                                background        : url,
                                backgroundSize    : 'contain',
                                backgroundPosition: 'center center',
                                height            : '100%',
                                width             : '100%'
                            }
                        }).inject(Parent);
                    },
                    mouseleave: function () {
                        document.getElements('.qui-controls-project-media-input-preview--view').destroy();
                    }
                }
            }).inject(this.$Elm);

            this.$Path = new Element('div', {
                html   : '&nbsp;',
                'class': 'qui-controls-project-media-input-path'
            }).inject(this.$Elm);

            this.$MediaButton = new QUIButton({
                name  : 'media-input-image-select',
                icon  : 'fa fa-picture-o',
                alt   : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.input.select.alt'),
                title : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.input.select.title'),
                styles: {
                    width: 50
                },
                events: {
                    onClick: function () {
                        let value   = self.$Input.value,
                            project = '',
                            fileid  = false;

                        if (self.$Input.get('data-project')) {
                            project = self.$Input.get('data-project');
                        }

                        if (typeOf(self.$Project) === 'string') {
                            self.$Project = Projects.get(self.$Project);
                        }

                        if (self.$Project && "getName" in self.$Project) {
                            project = self.$Project.getName();
                        }

                        if (value !== '') {
                            let urlParams = QUIStringUtils.getUrlParams(value);

                            if ("id" in urlParams) {
                                fileid = urlParams.id;
                            }

                            if ("project" in urlParams) {
                                project = urlParams.project;
                            }
                        }

                        if (project === '') {
                            project = QUIQQER_PROJECT.name;
                        }

                        if (!fileid) {
                            fileid = self.getAttribute('fileid');
                        }

                        new MediaPopup({
                            project             : project,
                            fileid              : fileid,
                            breadcrumb          : self.getAttribute('breadcrumb'),
                            selectable_types    : self.getAttribute('selectable_types'),
                            selectable_mimetypes: self.getAttribute('selectable_mimetypes'),
                            events              : {
                                onSubmit: function (Popup, params) {
                                    self.$Input.value = params.url;

                                    self.fireEvent('change', [
                                        self,
                                        self.getValue()
                                    ]);

                                    self.$change();
                                    self.$refreshPreview();
                                }
                            }
                        }).open();
                    }
                }
            }).inject(this.$Elm);

            if (!this.getAttribute('mediabutton')) {
                this.$MediaButton.hide();
            }

            if (this.getAttribute('cssclasses')) {
                this.$CSSButton = new QUIButton({
                    name  : 'media-input-css-select',
                    icon  : 'fa fa-css3',
                    alt   : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.input.cssclass.alt'),
                    title : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.input.cssclass.title'),
                    styles: {
                        width: 50
                    },
                    events: {
                        onClick: this.$openCSSClassDialog
                    }
                }).inject(this.$Elm);
            } else {
                this.$Path.setStyle('width', 'calc(100% - 130px)');
            }

            new QUIButton({
                name  : 'media-input-clear',
                icon  : 'fa fa-remove',
                alt   : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.input.clear.alt'),
                title : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.input.clear.alt'),
                styles: {
                    width: 50
                },
                events: {
                    onClick: function () {
                        self.clear();
                    }
                }
            }).inject(this.$Elm);


            this.$Input.addEvents({
                focus: function () {
                    self.$MediaButton.click();
                }
            });

            this.$refreshPreview();

            return this.$Elm;
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Input = this.getElm();
            this.create();
        },

        /**
         * Return the value (URL of the selected file)
         * eq: image.php?****
         *
         * @return {String}
         */
        getValue: function () {
            return this.$Input ? this.$Input.value : '';
        },

        /**
         * Set the url of an item
         * eq: image.php?****
         *
         * @param {String} str - image.php string
         */
        setValue: function (str) {
            str = str || '';

            this.$Input.value = str.toString();
            this.fireEvent('change', [
                this,
                this.getValue()
            ]);
            this.$refreshPreview();
        },

        /**
         * Clear the value
         */
        clear: function () {
            this.$Input.value = '';
            this.$Path.set('html', '&nbsp;');
            this.fireEvent('change', [
                this,
                this.getValue()
            ]);

            this.$change();
            this.$refreshPreview();
        },

        /**
         * refresh the preview
         */
        $refreshPreview: function () {
            const value = this.$Input.value;

            if (value === '' || value === '0') {
                this.$Preview.setStyle('background', null);
                this.$Preview.getElements('.qui-controls-project-media-input-preview-icon').destroy();
                return;
            }

            this.$Preview.getElements('.qui-controls-project-media-input-preview-icon').destroy();
            this.$Preview.getElements('.fa-spinner').destroy();
            this.$Preview.getElements('.fa-warning').destroy();

            if (!value.match('image.php')) {
                const Span = new Element('span', {
                    'class': 'qui-controls-project-media-input-preview-icon'
                }).inject(this.$Preview);

                Span.addClass(value);
                return;
            }

            // loader image
            const MiniLoader = new Element('div', {
                'class': 'fa fa-spinner fa-spin',
                styles : {
                    fontSize : 18,
                    height   : 20,
                    left     : 4,
                    position : 'relative',
                    textAlign: 'center',
                    top      : 4,
                    width    : 20
                }
            }).inject(this.$Preview);

            const self = this;

            Ajax.get([
                'ajax_media_url_rewrited',
                'ajax_media_url_getPath',
                'ajax_media_url_getImageSize'
            ], function (result, path, size) {
                const previewUrl = (URL_DIR + result).replace('//', '/');

                if (self.getAttribute('ratio_warning') && size.width !== size.height) {
                    new Element('span', {
                        'class': 'fa fa-exclamation-triangle',
                        styles : {
                            bottom  : 0,
                            color   : '#FDDD5C',
                            left    : 25,
                            position: 'absolute'
                        },
                        title  : Locale.get(lg, 'control.project.input.ratio.warning')
                    }).inject(self.getElm());
                } else {
                    self.getElm().getElements('.fa-exclamation-triangle').destroy();
                }

                self.$Path.set('html', path);
                self.$Path.set('title', path);

                // load the image
                require(['image!' + previewUrl], function () {
                    MiniLoader.destroy();

                    self.$Preview.setStyle(
                        'background',
                        'url(' + previewUrl + ') no-repeat center center'
                    );
                }, function () {
                    console.log('error at: ', previewUrl);
                    self.$Preview
                        .getElements('.fa-spinner')
                        .removeClass('fa-spin')
                        .removeClass('fa-spinner')
                        .addClass('fa-warning');
                });

            }, {
                fileurl: value,
                params : JSON.encode({
                    height: 30,
                    width : 30
                })
            });
        },

        /**
         * trigger on change event
         */
        $change: function () {
            if ("createEvent" in document) {
                const evt = document.createEvent("HTMLEvents");
                evt.initEvent("change", false, true);
                this.$Input.dispatchEvent(evt);
            } else {
                this.$Input.fireEvent("onchange");
            }
        },

        /**
         * Open FontAwesome select popup.
         */
        $openCSSClassDialog: function () {
            new IconConfirm({
                events: {
                    onSubmit: function (Win, selected) {
                        this.setValue(selected[0]);
                        this.$change();
                    }.bind(this)
                }
            }).open();
        }
    });
});
