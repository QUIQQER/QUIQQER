/**
 * Image input
 *
 * @module controls/projects/project/media/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/utils/String
 * @require controls/projects/project/media/Popup
 * @require Ajax
 * @require Locale
 * @require css!controls/projects/project/media/Input.css
 *
 * @event onChange [ {self}, {String} ]
 */
define('controls/projects/project/media/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/utils/String',
    'controls/projects/project/media/Popup',
    'Projects',
    'Ajax',
    'Locale',

    'css!controls/projects/project/media/Input.css'

], function (QUIControl, QUIButton, QUIStringUtils, MediaPopup, Projects, Ajax, Locale) {
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
            '$onCreate'
        ],

        options: {
            name  : '',
            styles: false,

            fileid              : false,
            breadcrumb          : true,     // you can specified if the breadcrumb is shown or not
            selectable_types    : false,    // you can specified which types are selectable
            selectable_mimetypes: false     // you can specified which mime types are selectable
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input   = Input || null;
            this.$Preview = null;
            this.$Project = null;
        },

        /**
         * Set the internal project
         *
         * @param {Object} Project - classes/projects/project
         */
        setProject: function (Project) {
            if (typeOf(Project) == 'string') {
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

            this.$Input.setStyles({
                display: 'none'
            });

            if (this.$Input.value !== '') {

                var urlParams = QUIStringUtils.getUrlParams(this.$Input.value);

                if ("project" in urlParams) {
                    this.setProject(urlParams.project);
                }
            }


            // preview
            this.$Preview = new Element('div', {
                'class': 'qui-controls-project-media-input-preview'
            }).inject(this.$Elm);

            this.$MediaButton = new QUIButton({
                icon  : 'fa fa-picture-o icon-picture',
                alt   : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.alt'),
                title : Locale.get('quiqqer/system', 'projects.project.site.media.input.select.title'),
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
                            fileid = this.getAttribute('fileid');
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
                                    self.$refreshPreview();
                                }
                            }
                        }).open();
                    }
                }
            }).inject(this.$Elm);

            new QUIButton({
                icon  : 'icon-remove',
                alt   : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
                title : Locale.get('quiqqer/system', 'projects.project.site.media.input.clear.alt'),
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
            if (str.toString().match('image.php')) {
                this.$Input.value = str.toString();
            }

            this.fireEvent('change', [this, this.getValue()]);
            this.$refreshPreview();
        },

        /**
         * Clear the value
         */
        clear: function () {
            this.$Input.value = '';
            this.fireEvent('change', [this, this.getValue()]);
            this.$refreshPreview();
        },

        /**
         * refresh the preview
         */
        $refreshPreview: function () {
            var value = this.$Input.value;

            if (value === '' || value == '0') {
                this.$Preview.setStyle('background', null);
                return;
            }

            this.$Preview.getElements('.icon-refresh').destroy();
            this.$Preview.getElements('.icon-warning-sign').destroy();

            // loader image
            var MiniLoader = new Element('div', {
                'class': 'icon-refresh icon-spin',
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

            var self       = this,
                previewUrl = value;

            if (value.substr(0, 10) == 'image.php?') {
                previewUrl = URL_DIR + value + '&maxwidth=30&maxheight=30&quiadmin=1';
            }

            // load the image
            require([
                'image!' + previewUrl
            ], function () {
                MiniLoader.destroy();

                self.$Preview
                    .setStyle('background', 'url(' + previewUrl + ') no-repeat center center');

            }, function () {
                self.$Preview
                    .getElements('.icon-refresh')
                    .removeClass('icon-refresh')
                    .removeClass('icon-spin')
                    .addClass('icon-warning-sign');
            });

            //Asset.image(previewUrl, {
            //    onLoad : function () {
            //        MiniLoader.destroy();
            //        self.$Preview.setStyle('background', 'url(' + previewUrl + ') no-repeat center center');
            //    },
            //    onError: function () {
            //        self.$Preview.getElements('.icon-refresh')
            //            .removeClass('icon-refresh')
            //            .removeClass('icon-spin')
            //            .addClass('icon-warning-sign');
            //    }
            //});
        }
    });
});
