/**
 * @module controls/workspace/search/Search
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Mustache
 * @require Ajax
 * @require Locale
 * @require text!controls/workspace/search/Search.html
 * @require text!controls/workspace/search/Search.ResultGroup.html
 * @require css!controls/workspace/search/Search.css
 */
define('controls/workspace/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Popup',
    'qui/controls/buttons/Select',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',

    'controls/workspace/search/FilterSelect',
    'utils/Panels',
    'Mustache',
    'Ajax',
    'Locale',

    'text!controls/workspace/search/Search.html',
    'text!controls/workspace/search/Search.ResultGroup.html',
    'css!controls/workspace/search/Search.css'

], function (QUI, QUIControl, QUIPanel, QUIPopup, QUISelect, QUIButton, QUILoader,
             FilterSelect, PanelUtils, Mustache, QUIAjax, QUILocale, template,
             templateResultGroup) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Type   : 'controls/workspace/search/Search',
        Extends: QUIControl,

        Binds: [
            'close',
            'create',
            'open',
            'executeSearch',
            '$onInject',
            '$onWindowKeyUp',
            '$renderResult',
            'search'
        ],

        options: {
            delay: 200
        },

        initialize: function (options) {
            this.parent(options);

            this.$Elm    = null;
            this.$Input  = null;
            this.$Header = null;
            this.$Close  = null;
            this.$Result = null;

            this.$open           = false;
            this.$value          = false;
            this.$FilterSelect   = null;
            this.$extendedSearch = false;
            this.$Settings       = {};
        },

        /**
         * event : on create
         */
        create: function () {
            var Elm  = this.parent();
            var self = this;

            Elm.addClass('qui-workspace-search-search');
            Elm.set('html', Mustache.render(template));

            Elm.setStyles({
                position: 'absolute'
            });

            this.$Header     = Elm.getElement('header');
            this.$Result     = Elm.getElement('.qui-workspace-search-search-container-result');
            this.$SearchIcon = Elm.getElement('.qui-workspace-search-search-container-input label .fa');
            this.Loader      = new QUILoader();

            // input events
            var inputEsc = false;

            this.$Input = Elm.getElement('input');

            this.$Input.addEvent('keydown', function (event) {
                if (event.key == 'esc') {
                    event.stop();
                    inputEsc = true;
                    return;
                }

                inputEsc = false;
            });

            this.$Input.addEvent('keyup', function (event) {
                if (inputEsc && this.$Input.value !== '') {
                    event.stop();
                    this.$Input.value = '';
                }

                // auto-search requires minimum characters
                if ((this.$Input.value.length < this.$Settings.minCharacters) &&
                    event.code != 13) {
                    return;
                }

                this.search();
            }.bind(this));

            // search btn
            new QUIButton({
                'class'  : 'qui-workspace-search-search-container-btn',
                textimage: 'fa fa-search',
                text     : QUILocale.get(lg, 'controls.workspace.search.btn.text'),
                events   : {
                    onClick: function() {
                        self.$Input.focus();

                        if (self.$Input.value.trim() == '') {
                            self.$Input.value = '';
                            return;
                        }

                        self.search();
                    }
                }
            }).inject(
                Elm.getElement(
                    '.qui-workspace-search-search-container-input label'
                ),
                'after'
            );

            this.$Close = Elm.getElement('.qui-workspace-search-search-container-close');
            this.$Close.addEvent('click', this.close);

            new Element('img', {
                src: URL_BIN_DIR + 'quiqqer_logo.png'
            }).inject(this.$Header, 'top');

            return Elm;
        },

        /**
         * Open the search
         *
         * @return {Promise}
         */
        open: function () {
            var self = this;

            if (!this.$Elm) {
                this.create();
            }

            if (this.$open) {
                return Promise.resolve();
            }

            this.$open = true;

            this.$Elm.setStyles({
                top: '-100%'
            });

            this.Loader.inject(this.$Elm);
            this.$Elm.inject(document.body);

            // filter select
            this.$FilterSelect = new FilterSelect().inject(
                this.$Elm.getElement(
                    '.qui-workspace-search-search-container-filterselect'
                )
            );

            this.Loader.show();

            return new Promise(function (resolve) {
                self.$getSettings('general').then(function (Settings) {
                    self.$Settings = Settings;

                    self.$FilterSelect.addEvents({
                        onChange: self.search
                    });

                    moofx(self.$Elm).animate({
                        top: 0
                    }, {
                        duration: 250,
                        callback: function () {
                            if (self.$value) {
                                self.setValue(self.$value);
                            }

                            window.addEvent('keyup', self.$onWindowKeyUp);

                            self.$Input.focus();

                            if (self.$Input.value !== '') {
                                self.search();
                            }

                            self.fireEvent('open', [self]);
                            self.Loader.hide();

                            resolve();
                        }
                    });
                });
            });
        },

        /**
         * Set the value / search string for the search
         *
         * @param {String} value
         */
        setValue: function (value) {
            if (this.$Input) {
                this.$Input.value = value;
                return;
            }

            this.$value = value;
        },

        /**
         * Return the current search value
         *
         * @return {String}
         */
        getValue: function () {
            if (this.$Input) {
                return this.$Input.value;
            }

            return this.$value || '';
        },

        /**
         * Close the complete search
         *
         * @return {Promise}
         */
        close: function () {
            return new Promise(function (resolve) {

                moofx(this.$Elm).animate({
                    opacity: 0,
                    top    : -200
                }, {
                    duration: 250,
                    callback: function () {
                        this.$Elm.destroy();

                        this.$Elm   = null;
                        this.$open  = false;
                        this.$value = this.$Input.value;

                        window.removeEvent('keyup', this.$onWindowKeyUp);
                        this.fireEvent('close', [this]);

                        resolve();
                    }.bind(this)
                });

            }.bind(this));
        },

        /**
         * Open a cache entry and close the search
         *
         * @param {Number|String} id
         * @param {String} [provider]
         */
        openEntry: function (id, provider) {
            this.getEntry(id, provider).then(function (data) {
                if (!data || !("searchdata" in data)) {
                    return;
                }

                var searchData;

                try {
                    searchData = JSON.decode(data.searchdata);
                } catch (e) {
                    return;
                }

                if ("require" in searchData) {
                    require([searchData.require], function (Cls) {
                        if (typeOf(Cls) == 'class') {
                            var params   = searchData.params || {};
                            var Instance = new Cls(params);

                            if (instanceOf(Instance, QUIPanel)) {
                                PanelUtils.openPanelInTasks(Instance);
                            }

                            if (instanceOf(Instance, QUIPopup)) {
                                Instance.open();
                            }
                        }
                    });

                    this.close();
                }
            }.bind(this)).catch(function (Exception) {
                console.error(Exception);
            });
        },

        /**
         * Excecute the search with a delay
         */
        search: function () {
            if (!this.$open) {
                this.open();
            }

            var self = this;

            if (this.$Timer) {
                clearInterval(this.$Timer);
            }

            var searchValue = this.$Input.value.trim();

            if (searchValue == '') {
                this.$Input.value = '';
                //this.$Input.focus();

                return;
            }

            var twoStepSearch = parseInt(this.$Settings.twoStepSearch);

            this.$Timer = (function () {
                var Params = {
                    filterGroups: this.$FilterSelect.getValue()
                };

                if (!this.$extendedSearch && twoStepSearch) {
                    Params.limit = 5;
                }

                this.executeSearch(this.$Input.value, Params).then(function (result) {
                    self.$renderResult(result);

                    if (!self.$extendedSearch && twoStepSearch && result.length >= 5) {
                        self.$extendedSearch = true;
                        self.search();  // execute search without limits
                    } else {
                        self.$extendedSearch = false;
                    }
                });
            }).delay(this.getAttribute('delay'), this);
        },

        /**
         * Render the result array
         *
         * @param {Array} result
         */
        $renderResult: function (result) {
            var self           = this;
            var group, groupHTML, Entry, label;
            var html           = '',
                ResultsByGroup = {};

            for (var i = 0, len = result.length; i < len; i++) {
                Entry = result[i];

                if (typeof ResultsByGroup[Entry.group] === 'undefined') {
                    label = Entry.group;

                    if ("groupLabel" in Entry) {
                        label = Entry.groupLabel;
                    }

                    ResultsByGroup[Entry.group] = {
                        label  : label,
                        entries: []
                    };
                }

                ResultsByGroup[result[i].group].entries.push(result[i]);
            }

            for (group in ResultsByGroup) {
                if (!ResultsByGroup.hasOwnProperty(group)) {
                    continue;
                }

                groupHTML = Mustache.render(templateResultGroup, {
                    title  : ResultsByGroup[group].label,
                    entries: ResultsByGroup[group].entries
                });

                html = html + groupHTML;
            }

            this.$Result.set('html', html);
            this.$Result.getElements('li').addEvent('click', function (event) {
                var Target = event.target;

                if (Target.nodeName !== 'LI') {
                    Target = Target.getParent('li');
                }

                this.openEntry(Target.get('data-id'), Target.get('data-provider'));
            }.bind(this));
        },

        /**
         * event : on window key up
         * looks for ESC
         *
         * @param event
         */
        $onWindowKeyUp: function (event) {
            if (event.key == 'esc') {
                this.close();
            }
        },

        /**
         * Execute a search
         *
         * @param {String} search
         * @param {Object} [params] - Search where params
         * @returns {Promise}
         */
        executeSearch: function (search, params) {
            if (search === '') {
                return Promise.resolve([]);
            }

            params = params || {};

            var self = this;

            this.$SearchIcon.removeClass('fa-arrow-right');
            this.$SearchIcon.addClass('fa-spinner fa-spin');

            return new Promise(function (resolve) {
                QUIAjax.get('ajax_workspace_search', function (result) {
                    self.$SearchIcon.addClass('fa-arrow-right');
                    self.$SearchIcon.removeClass('fa-spinner');
                    self.$SearchIcon.removeClass('fa-spin');
                    resolve(result);
                }, {
                    search: search,
                    params: JSON.encode(params)
                });
            });
        },

        /**
         * Return a search cache entry
         *
         * @param {Number|String} id - id of the entry
         * @param {String} [provider] - optional, provider to get the entry data, if the entry is from a module
         * @returns {Promise}
         */
        getEntry: function (id, provider) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_workspace_getEntry', resolve, {
                    id       : id,
                    provider : provider,
                    showError: false,
                    onError  : reject
                });
            });
        },

        /**
         * Get search settings
         *
         * @param {string} section
         * @param {string} [setting]
         * @return {Promise}
         */
        $getSettings: function (section, setting) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_workspace_search_getSetting', resolve, {
                    onError: reject,
                    section: section,
                    'var'  : setting ? null : setting
                });
            });
        },

        /**
         * Get all available provider search ResultsByGroup
         *
         * @return {Promise}
         */
        $getFilterGroups: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_workspace_search_getFilterGroups', resolve, {
                    onError: reject
                });
            });
        }

    });
});
