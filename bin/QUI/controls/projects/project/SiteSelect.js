/**
 * Site Language Select
 * For each project language the user can select a site
 *
 * @module controls/projects/project/SiteSelect
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/project/SiteSelect', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/projects/Popup',
    'Locale',

    'css!controls/projects/project/SiteSelect.css'

], function (QUI, QUIControl, QUIButton, ProjectPopup, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/SiteSelect',

        Binds: [
            '$onChange'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Project = null;
            this.$Elm     = null;
            this.$List    = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Set the project
         *
         * @param Project
         */
        setProject: function (Project) {
            this.$Project = Project;
            this.$render();
        },

        /**
         * Create the DOMNode
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class'     : 'qui-controls-project-siteselect',
                'data-quiid': this.getId()
            });

            this.$List = new Element('div').inject(this.$Elm);

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name: this.getAttribute('name'),
                    type: 'hidden'
                }).inject(this.$Elm);
            } else {
                this.$Elm.wraps(this.$Input);
                this.$Input.type = 'hidden';
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Input.setStyles({
                'float': 'left'
            });

            if (!this.getAttribute('external')) {
                this.$Input.setStyle('cursor', 'pointer');
            }

            this.$render();

            return this.$Elm;
        },

        /**
         * Render project languages
         */
        $render: function () {
            if (!this.$Project) {
                return;
            }

            var self = this;

            this.$Project.getConfig().then(function (config) {
                var languages = config.langs.split(',');
                var Container;

                self.$List.set('html', '');

                var onProjectButtonClick = function (Btn) {
                    var Container;

                    if (typeOf(Btn) === 'domevent') {
                        Container = Btn.target.getParent(
                            '.qui-controls-project-siteselect-language'
                        );
                    } else {
                        Container = Btn.getElm().getParent(
                            '.qui-controls-project-siteselect-language'
                        );
                    }

                    var language = Container.get('data-lang');
                    var project  = Container.get('data-project');

                    new ProjectPopup({
                        project             : project,
                        lang                : language,
                        disableProjectSelect: true,
                        events              : {
                            onSubmit: function (Popup, params) {
                                Container.getElement('input').value = params.urls[0];
                                self.$onChange();
                            }
                        }
                    }).open();
                };

                var onDeleteButtonClick = function (Btn) {
                    var Container = Btn.getElm().getParent(
                        '.qui-controls-project-siteselect-language'
                    );

                    Container.getElement('input').value = '';
                    self.$onChange();
                };

                for (var i = 0, len = languages.length; i < len; i++) {
                    Container = new Element('div', {
                        'class'       : 'qui-controls-project-siteselect-language',
                        html          : '<input type="text" data-lang="' + languages[i] + '" />',
                        'data-lang'   : languages[i],
                        'data-project': self.$Project.getName()
                    }).inject(self.$List);

                    new Element('img', {
                        src    : URL_BIN_DIR + '16x16/flags/' + languages[i] + '.png',
                        'class': 'qui-controls-project-siteselect-flag'
                    }).inject(Container);

                    Container.getElement('input').addEvents({
                        change: self.$onChange,
                        keyup : self.$onChange,
                        click : onProjectButtonClick
                    });


                    new QUIButton({
                        icon  : 'fa fa-file-o',
                        styles: {
                            width: 40
                        },
                        events: {
                            onClick: onProjectButtonClick
                        }
                    }).inject(Container);

                    new QUIButton({
                        icon  : 'fa fa-remove',
                        alt   : QUILocale.get('quiqqer/quiqqer', 'projects.project.site.input.clear'),
                        title : QUILocale.get('quiqqer/quiqqer', 'projects.project.site.input.clear'),
                        styles: {
                            width: 40
                        },
                        events: {
                            onClick: onDeleteButtonClick
                        }
                    }).inject(Container);
                }
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.$Input = this.$Elm;
            this.create();

            if (this.$Elm.getParent().hasClass('field-container')) {
                this.$Elm.addClass('field-container-field');
            }
        },

        /**
         * event on input change
         */
        $onChange: function () {
            var result    = {};
            var inputList = this.$List.getElements('input');

            var i, len, lang, Container;

            for (i = 0, len = inputList.length; i < len; i++) {
                Container = inputList[i].getParent('.qui-controls-project-siteselect-language');
                lang      = Container.get('data-lang');

                result[lang] = inputList[i].value;
            }

            this.$Input.value = JSON.encode(result);
        }
    });
});
