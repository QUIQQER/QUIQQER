/**
 * Image input
 *
 * @module controls/projects/project/media/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require controls/icons/Confirm
 * @require qui/utils/String
 * @require controls/projects/project/media/Popup
 * @require Projects
 * @require Ajax
 * @require Locale
 * @require css!controls/projects/project/media/Input.css
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
            '$onCreate',
            '$openCSSClassDialog'
        ],

        options: {
            name  : '',
            styles: false,

            fileid              : false,
            breadcrumb          : true,     // you can specified if the breadcrumb is shown or not
            selectable_types    : false,    // you can specified which types are selectable
            selectable_mimetypes: false,    // you can specified which mime types are selectable
            cssclasses          : false     // css classes can be selected
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input = Input || null;

            this.$Path    = null;
            this.$Preview = null;
            this.$Project = null;

            this.$CSSButton   = null;
            this.$MediaButton = null;
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
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class'     : 'qui-controls-project-media-input box',
                'data-quiid': this.getId()
            });

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name: this.getAttribute('name')
                }).inject(this.$Elm);
            } else {
                this.$Elm.wraps(this.$Input);
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
                var urlParams = QUIStringUtils.getUrlParams(this.$Input.value);

                if ("project" in urlParams) {
                    this.setProject(urlParams.project);
                }
            }

            // preview
            this.$Preview = new Element('div', {
                html   : '&nbsp;',
                'class': 'qui-controls-project-media-input-preview'
            }).inject(this.$Elm);

            this.$Path = new Element('div', {
                html   : '&nbsp;',
                'class': 'qui-controls-project-media-input-path'
            }).inject(this.$Elm);

            this.$MediaButton = new QUIButton({
                name  : 'media-input-image-select',
                icon  : 'fa fa-picture-o',
                alt   : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.alt'),
                title : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.title'),
                styles: {
                    width: 50
                },
                events: {
                    onClick: function () {
                        var value   = self.$Input.value,
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
                            var urlParams = QUIStringUtils.getUrlParams(value);

                            if ("id" in urlParams) {
                                fileid = urlParams.id;
                            }

                            if ("project" in urlParams) {
                                project = urlParams.project;
                            }
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
                                    self.fireEvent('change', [self, self.getValue()]);

                                    if ("createEvent" in document) {
                                        var evt = document.createEvent("HTMLEvents");
                                        evt.initEvent("change", false, true);
                                        self.$Input.dispatchEvent(evt);
                                    } else {
                                        self.$Input.fireEvent("onchange");
                                    }

                                    self.$refreshPreview();
                                }
                            }
                        }).open();
                    }
                }
            }).inject(this.$Elm);

            if (this.getAttribute('cssclasses')) {
                this.$CSSButton = new QUIButton({
                    name  : 'media-input-css-select',
                    icon  : 'fa fa-css3',
                    alt   : Locale.get('quiqqer/system', 'projects.project.site.media.input.cssclass.alt'),
                    title : Locale.get('quiqqer/system', 'projects.project.site.media.input.cssclass.title'),
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
                alt   : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
                title : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
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
            this.fireEvent('change', [this, this.getValue()]);
            this.$refreshPreview();
        },

        /**
         * Clear the value
         */
        clear: function () {
            this.$Input.value = '';
            this.$Path.set('html', '&nbsp;');
            this.fireEvent('change', [this, this.getValue()]);
            this.$refreshPreview();
        },

        /**
         * refresh the preview
         */
        $refreshPreview: function () {
            var value = this.$Input.value;

            if (value === '' || value === '0') {
                this.$Preview.setStyle('background', null);
                this.$Preview.getElements('.qui-controls-project-media-input-preview-icon').destroy();
                return;
            }

            this.$Preview.getElements('.qui-controls-project-media-input-preview-icon').destroy();
            this.$Preview.getElements('.fa-spinner').destroy();
            this.$Preview.getElements('.fa-warning').destroy();

            if (!value.match('image.php')) {
                var Span = new Element('span', {
                    'class': 'qui-controls-project-media-input-preview-icon'
                }).inject(this.$Preview);

                Span.addClass(value);
                return;
            }

            // loader image
            var MiniLoader = new Element('div', {
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

            var self = this;

            Ajax.get([
                'ajax_media_url_rewrited',
                'ajax_media_url_getPath'
            ], function (result, path) {
                var previewUrl = (URL_DIR + result).replace('//', '/');

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
         *
         */
        $openCSSClassDialog: function () {
            new IconConfirm({
                events: {
                    onSubmit: function (Win, selected) {
                        this.setValue(selected[0]);
                    }.bind(this)
                }
            }).open();
        }
    });
});
