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
    'utils/Panels',
    'Mustache',
    'Ajax',
    'Locale',

    'text!controls/workspace/search/Search.html',
    'text!controls/workspace/search/Search.ResultGroup.html',
    'css!controls/workspace/search/Search.css'

], function (QUI, QUIControl, QUIPanel, PanelUtils, Mustache, QUIAjax, QUILocale,
             template, templateResultGroup) {
    "use strict";

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
            '$renderResult'
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

            this.$open = false;
        },

        /**
         * event : on create
         */
        create: function () {
            var Elm = this.parent();

            Elm.addClass('qui-workspace-search-search');
            Elm.set('html', Mustache.render(template));

            Elm.setStyles({
                position: 'absolute'
            });

            this.$Header     = Elm.getElement('header');
            this.$Result     = Elm.getElement('.qui-workspace-search-search-container-result');
            this.$SearchIcon = Elm.getElement('.qui-workspace-search-search-container-input .fa-search');

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

                this.executeSearch();
            }.bind(this));


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

            this.$Elm.inject(document.body);

            return new Promise(function (resolve) {

                moofx(this.$Elm).animate({
                    top: 0
                }, {
                    duration: 250,
                    callback: function () {
                        this.$Input.focus();
                        window.addEvent('keyup', this.$onWindowKeyUp);
                        this.fireEvent('open', [this]);
                        resolve();
                    }.bind(this)
                });

            }.bind(this));
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

                        this.$Elm  = null;
                        this.$open = false;

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
         * @param id
         */
        openEntry: function (id) {
            this.getEntry(id).then(function (data) {
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
                            var Instance = new Cls();

                            if (instanceOf(Instance, QUIPanel)) {
                                PanelUtils.openPanelInTasks(Instance);
                            }
                        }
                    });

                    this.close();
                    return;
                }

            }.bind(this)).catch(function (Exception) {
                console.error(Exception);
            });
        },

        /**
         * Excecute the search with a delay
         */
        executeSearch: function () {
            if (this.$Timer) {
                clearInterval(this.$Timer);
            }

            this.$Timer = (function () {
                this.search(this.$Input.value).then(this.$renderResult);
            }).delay(this.getAttribute('delay'), this);
        },

        /**
         * Render the result array
         *
         * @param {Array} result
         */
        $renderResult: function (result) {
            var group, groupHTML;
            var html   = '',
                groups = {};

            for (var i = 0, len = result.length; i < len; i++) {
                if (typeof groups[result[i].searchtype] === 'undefined') {
                    groups[result[i].searchtype] = [];
                }

                groups[result[i].searchtype].push(result[i]);
            }

            for (group in groups) {
                if (!groups.hasOwnProperty(group)) {
                    continue;
                }

                groupHTML = Mustache.render(templateResultGroup, {
                    title  : group,
                    entries: groups[group]
                });

                html = html + groupHTML;
            }

            this.$Result.set('html', html);
            this.$Result.getElements('li').addEvent('click', function (event) {
                var Target = event.target;

                if (Target.nodeName !== 'LI') {
                    Target = Target.getParent('li');
                }

                this.openEntry(Target.get('data-id'));
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
        search: function (search, params) {
            if (search === '') {
                return Promise.resolve([]);
            }

            params = params || {};

            var self = this;

            this.$SearchIcon.removeClass('fa-search');
            this.$SearchIcon.addClass('fa-spinner fa-spin');

            return new Promise(function (resolve) {
                QUIAjax.get('ajax_workspace_search', function (result) {
                    self.$SearchIcon.addClass('fa-search');
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
         * @param {Number} id
         * @returns {Promise}
         */
        getEntry: function (id) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_workspace_getEntry', resolve, {
                    id       : id,
                    showError: false,
                    onError  : reject
                });
            });
        }
    });
});
