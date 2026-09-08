/**
 * Edit a VHost and its project language routes.
 */
define('controls/system/VHost', [

    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'utils/Controls',
    'qui/utils/String',
    'Mustache',
    'Ajax',
    'Locale',
    'Projects',

    'text!controls/system/VHost.html',
    'css!controls/system/VHost.css'

], function (
    QUIControl,
    QUILoader,
    ControlUtils,
    StringUtils,
    Mustache,
    Ajax,
    QUILocale,
    Projects,
    template
) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIControl,
        Type: 'controls/system/VHosts',

        Binds: [
            '$onInject'
        ],

        options: {
            host: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Elm = null;
            this.$Content = null;
            this.$ErrorSite = null;
            this.$HttpsHost = null;
            this.$WwwRedirect = null;
            this.$PathLanguagesTable = null;
            this.$PathLanguagesBody = null;
            this.$ProjectSelect = null;
            this.$RootLanguageSelect = null;
            this.$TemplateSelect = null;
            this.$VhostData = {};
            this.$Vhosts = {};
            this.$Projects = {};

            this.Loader = new QUILoader();

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the root element.
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = document.createElement('div');
            this.$Elm.classList.add('control-system-vhost', 'box');

            this.$Content = document.createElement('div');
            this.$Content.dataset.name = 'content';
            this.$Elm.appendChild(this.$Content);

            this.Loader.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * Load and render the VHost configuration.
         */
        $onInject: function () {
            this.Loader.show();

            Ajax.get([
                'ajax_vhosts_get',
                'ajax_vhosts_getList',
                'ajax_template_getlist'
            ], (vhostData, vhosts, templates) => {
                Projects.getList().then((projects) => {
                    this.$VhostData = vhostData || {};
                    this.$Vhosts = vhosts || {};
                    this.$Projects = projects || {};

                    this.$render(templates || []);
                    this.Loader.hide();
                });
            }, {
                vhost: this.getAttribute('host')
            });
        },

        /**
         * Render the editor form.
         *
         * @param {Array} templates
         */
        $render: function (templates) {
            const idPrefix = 'vhost-' + this.getId();

            this.$Content.innerHTML = Mustache.render(template, {
                idPrefix: idPrefix,
                titleHostData: QUILocale.get(lg, 'system.vhost.table.hostdata'),
                labelDomain: QUILocale.get(lg, 'system.vhost.label.domain'),
                labelProject: QUILocale.get(lg, 'project'),
                labelRootLanguage: QUILocale.get(lg, 'system.vhost.label.rootLanguage'),
                labelTemplate: QUILocale.get(lg, 'template'),
                labelErrorSite: QUILocale.get(lg, 'system.vhost.label.errorsite'),
                labelHttpsHost: QUILocale.get(lg, 'system.vhost.label.httpshost'),
                descriptionDomainVariants: QUILocale.get(lg, 'system.vhost.domain.variants'),
                titleRedirects: QUILocale.get(lg, 'system.vhost.table.redirects'),
                labelWwwRedirect: QUILocale.get(lg, 'quiqqer.settings.general.webserver.wwwredirect.title'),
                optionWwwGlobal: QUILocale.get(lg, 'system.vhost.wwwRedirect.global'),
                optionWww: QUILocale.get(lg, 'quiqqer.settings.general.webserver.wwwredirect.www'),
                optionNonWww: QUILocale.get(lg, 'quiqqer.settings.general.webserver.wwwredirect.nonwww'),
                optionWwwNone: QUILocale.get(lg, 'quiqqer.settings.general.webserver.wwwredirect.none'),
                descriptionWwwRedirect: QUILocale.get(lg, 'system.vhost.wwwRedirect.description'),
                descriptionHttpsHost: QUILocale.get(lg, 'system.vhost.httpshost.description'),
                titlePathLanguages: QUILocale.get(lg, 'system.vhost.table.pathLanguages'),
                descriptionPathLanguages: QUILocale.get(
                    lg,
                    'system.vhost.table.pathLanguages.description'
                )
            });

            this.$ProjectSelect = this.$Elm.querySelector('[data-name="project"]');
            this.$RootLanguageSelect = this.$Elm.querySelector('[data-name="root-language"]');
            this.$TemplateSelect = this.$Elm.querySelector('[data-name="template"]');
            this.$ErrorSite = this.$Elm.querySelector('[data-name="error"]');
            this.$HttpsHost = this.$Elm.querySelector('[data-name="https-host"]');
            this.$WwwRedirect = this.$Elm.querySelector('[data-name="www-redirect"]');
            this.$PathLanguagesTable = this.$Elm.querySelector('[data-name="path-languages-table"]');
            this.$PathLanguagesBody = this.$Elm.querySelector('[data-name="path-languages"]');

            this.$Elm.querySelector('[data-name="domain"]').value = this.getAttribute('host');
            this.$HttpsHost.value = this.$VhostData.httpshost || '';
            this.$WwwRedirect.value = this.$VhostData.wwwRedirect ?? '';

            this.$setErrorSiteValue();
            this.$renderProjectOptions();
            this.$renderTemplateOptions(templates);

            this.$ProjectSelect.addEventListener('change', () => {
                this.$renderRootLanguages();
            });

            this.$RootLanguageSelect.addEventListener('change', () => {
                this.$renderPathLanguages();
            });

            ControlUtils.parse(this.$Elm);
            this.$renderRootLanguages();
        },

        /**
         * Set the configured error site URL.
         */
        $setErrorSiteValue: function () {
            const error = this.$VhostData.error || '';

            if (error === '') {
                return;
            }

            const parts = error.split(',');

            this.$ErrorSite.value = 'index.php?' + Object.toQueryString({
                project: parts[0] || '',
                lang: parts[1] || '',
                id: parts[2] || ''
            });
        },

        /**
         * Render project-only options.
         */
        $renderProjectOptions: function () {
            const projectNames = Object.keys(this.$Projects).sort();
            let projectName;

            this.$ProjectSelect.replaceChildren();
            this.$ProjectSelect.appendChild(new Option('', ''));

            for (projectName of projectNames) {
                this.$ProjectSelect.appendChild(new Option(projectName, projectName));
            }

            this.$ProjectSelect.value = this.$VhostData.project || '';
        },

        /**
         * Render available templates.
         *
         * @param {Array} templates
         */
        $renderTemplateOptions: function (templates) {
            let template;

            this.$TemplateSelect.replaceChildren();
            this.$TemplateSelect.appendChild(new Option('', ''));

            for (template of templates) {
                this.$TemplateSelect.appendChild(new Option(template.name, template.name));
            }

            this.$TemplateSelect.value = this.$VhostData.template || '';
        },

        /**
         * Render root language options for the selected project.
         */
        $renderRootLanguages: function () {
            const projectName = this.$ProjectSelect.value;
            const project = this.$Projects[projectName];
            const previousValue = this.$RootLanguageSelect.value;

            this.$RootLanguageSelect.replaceChildren();

            if (!project || !project.langs) {
                this.$PathLanguagesTable.hidden = true;
                this.$PathLanguagesBody.replaceChildren();
                return;
            }

            const languages = this.$parseLanguages(project.langs);
            let language, Owner, OptionElement;

            for (language of languages) {
                Owner = this.$getLanguageOwner(projectName, language);
                OptionElement = new Option(language, language);

                if (Owner && Owner.host !== this.getAttribute('host')) {
                    OptionElement.disabled = true;
                    OptionElement.textContent = QUILocale.get(
                        lg,
                        'system.vhost.language.assigned.option',
                        {
                            language: language,
                            host: Owner.host
                        }
                    );
                }

                this.$RootLanguageSelect.appendChild(OptionElement);
            }

            const configuredLanguage = this.$VhostData.project === projectName
                ? this.$VhostData.lang
                : '';
            const preferredLanguage = configuredLanguage || previousValue || project.default_lang;
            const PreferredOption = Array.from(this.$RootLanguageSelect.options).find((OptionElement) => {
                return OptionElement.value === preferredLanguage && !OptionElement.disabled;
            });
            const FirstAvailableOption = Array.from(this.$RootLanguageSelect.options).find((OptionElement) => {
                return !OptionElement.disabled;
            });

            if (PreferredOption) {
                this.$RootLanguageSelect.value = PreferredOption.value;
            } else if (FirstAvailableOption) {
                this.$RootLanguageSelect.value = FirstAvailableOption.value;
            }

            this.$renderPathLanguages();
        },

        /**
         * Render Path-language checkboxes if the project has further languages.
         */
        $renderPathLanguages: function () {
            const projectName = this.$ProjectSelect.value;
            const project = this.$Projects[projectName];
            const rootLanguage = this.$RootLanguageSelect.value;

            this.$PathLanguagesBody.replaceChildren();

            if (!project || !project.langs || !rootLanguage) {
                this.$PathLanguagesTable.hidden = true;
                return;
            }

            const pathLanguages = this.$parseLanguages(this.$VhostData.path_langs || '');
            const languages = this.$parseLanguages(project.langs).filter((language) => {
                return language !== rootLanguage;
            });

            if (languages.length === 0) {
                this.$PathLanguagesTable.hidden = true;
                return;
            }

            this.$PathLanguagesTable.hidden = false;
            let language, Owner, ownedByCurrentHost, ownedByOtherHost;
            let Row, Cell, Label, Language, Assignment, Checkbox, Description, checkboxId;

            for (language of languages) {
                Owner = this.$getLanguageOwner(projectName, language);
                ownedByCurrentHost = Owner && Owner.host === this.getAttribute('host');
                ownedByOtherHost = Owner && !ownedByCurrentHost;
                Row = document.createElement('tr');
                Cell = document.createElement('td');
                Label = document.createElement('label');
                Language = document.createElement('span');
                Assignment = document.createElement('span');
                Checkbox = document.createElement('input');
                Description = document.createElement('span');
                checkboxId = 'vhost-' + this.getId() + '-path-language-' + language;

                Label.classList.add('field-container');
                Language.classList.add('field-container-item');
                Language.textContent = language;
                Assignment.classList.add(
                    'field-container-field',
                    'control-system-vhost-path-language'
                );

                Checkbox.type = 'checkbox';
                Checkbox.id = checkboxId;
                Checkbox.value = language;
                Checkbox.dataset.name = 'path-language';
                Checkbox.checked = ownedByCurrentHost
                    || (
                        this.$VhostData.project === projectName
                        && pathLanguages.includes(language)
                    );
                Checkbox.disabled = Boolean(ownedByOtherHost);

                Label.htmlFor = checkboxId;

                Description.textContent = ownedByOtherHost
                    ? QUILocale.get(lg, 'system.vhost.language.assigned', {
                        host: Owner.host,
                        path: Owner.path ? '/' + Owner.path + '/' : '/'
                    })
                    : this.$getPathLanguagePreview(language);

                Assignment.appendChild(Checkbox);
                Assignment.appendChild(Description);
                Label.appendChild(Language);
                Label.appendChild(Assignment);
                Cell.appendChild(Label);
                Row.appendChild(Cell);
                this.$PathLanguagesBody.appendChild(Row);
            }
        },

        /**
         * Return the canonical owner of a project language.
         *
         * @param {String} projectName
         * @param {String} language
         * @return {Object|null}
         */
        $getLanguageOwner: function (projectName, language) {
            const hosts = Object.keys(this.$Vhosts);
            let host, data;

            for (host of hosts) {
                data = this.$Vhosts[host];

                if (!data || data.project !== projectName) {
                    continue;
                }

                if (data.lang === language) {
                    return {
                        host: host,
                        path: ''
                    };
                }
            }

            for (host of hosts) {
                data = this.$Vhosts[host];

                if (
                    !data
                    || data.project !== projectName
                    || !this.$parseLanguages(data.path_langs || '').includes(language)
                ) {
                    continue;
                }

                return {
                    host: host,
                    path: language
                };
            }

            return null;
        },

        /**
         * Return a preview of a Path-language base URL.
         *
         * @param {String} language
         * @return {String}
         */
        $getPathLanguagePreview: function (language) {
            const host = this.$HttpsHost.value || this.getAttribute('host');
            const basePath = String(URL_DIR || '/').replace(/^\/+|\/+$/g, '');
            const path = [basePath, language].filter(Boolean).join('/');

            return 'https://' + host + '/' + path + '/';
        },

        /**
         * Parse a comma-separated language list.
         *
         * @param {String|Array} languages
         * @return {Array}
         */
        $parseLanguages: function (languages) {
            if (Array.isArray(languages)) {
                return languages;
            }

            return String(languages)
                .split(',')
                .map((language) => language.trim().toLowerCase())
                .filter((language, index, list) => {
                    return language.length === 2 && list.indexOf(language) === index;
                });
        },

        /**
         * Save the VHost settings.
         *
         * @param {Function} [callback]
         * @return {Boolean}
         */
        save: function (callback) {
            const Form = this.$Elm.querySelector('[data-name="form"]');

            if (!Form.checkValidity()) {
                Form.reportValidity();
                return false;
            }

            this.Loader.show();

            const siteParts = StringUtils.getUrlParams(this.$ErrorSite.value);
            const pathLanguages = Array.from(
                this.$Elm.querySelectorAll('[data-name="path-language"]:checked:not(:disabled)')
            ).map((Checkbox) => Checkbox.value);
            let errorSite = '';

            if (siteParts.project) {
                errorSite = siteParts.project + ',' + siteParts.lang + ',' + siteParts.id;
            }

            Ajax.post('ajax_vhosts_save', () => {
                this.Loader.hide();

                if (typeof callback === 'function') {
                    callback();
                }
            }, {
                vhost: this.getAttribute('host'),
                data: JSON.encode({
                    project: this.$ProjectSelect.value,
                    lang: this.$RootLanguageSelect.value,
                    path_langs: pathLanguages.join(','),
                    template: this.$TemplateSelect.value,
                    error: errorSite,
                    httpshost: this.$HttpsHost.value,
                    wwwRedirect: this.$WwwRedirect.value
                })
            });

            return true;
        }
    });
});
