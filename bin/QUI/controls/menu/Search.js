/**
 * Search for QUIQQER Administration
 *
 * @module controls/menu/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require css!controls/menu/Search.css
 */
define('controls/menu/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Locale',

    'css!controls/menu/Search.css'

], function (QUI, QUIControl, QUIButton, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/menu/Search',

        Binds: [
            '$onChange',
            '$onBlur',
            '$onKeyUp'
        ],

        options: {},

        initialize: function (options) {
            this.parent(options);

            this.$SearchType = null;
            this.$Input      = null;
        },

        /**
         * create the DOMNode of the control
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.parent();

            this.$Elm.addClass('control-menu-search');

            this.$Elm.set({
                html: '<input type="text" class="control-menu-search-input" />'
            });

            this.$Input = this.$Elm.getElement('.control-menu-search-input');
            this.$type  = false;

            this.$Input.setStyles({
                display: 'none'
            });

            this.$Input.addEvents({
                blur : this.$onBlur,
                keyup: this.$onKeyUp
            });


            this.$SearchType = new QUIButton({
                textimage: 'icon-search',
                text     : QUILocale.get('quiqqer/quiqqer', 'menu.search.title'),
                styles   : {
                    lineHeight: 38
                }
            }).inject(this.$Elm, 'top');

            this.$SearchType.appendChild({
                icon  : 'fa fa-file-o icon-file-alt',
                text  : QUILocale.get('quiqqer/quiqqer', 'menu.search.sites.title'),
                search: 'site',
                events: {
                    onClick: this.$onChange
                }
            });

            this.$SearchType.appendChild({
                icon  : 'icon-user',
                text  : QUILocale.get('quiqqer/quiqqer', 'menu.search.users.title'),
                search: 'user',
                events: {
                    onClick: this.$onChange
                }
            });

            this.$SearchType.appendChild({
                icon  : 'icon-group',
                text  : QUILocale.get('quiqqer/quiqqer', 'menu.search.groups.title'),
                search: 'group',
                events: {
                    onClick: this.$onChange
                }
            });


            return this.$Elm;
        },

        /**
         * event on change
         *
         * @param Item
         */
        $onChange: function (Item) {
            this.$SearchType.setAttribute(
                'textimage',
                Item.getAttribute('icon')
            );

            this.$type = Item.getAttribute('search');
            this.$SearchType.setAttribute('text', false);

            this.$SearchType
                .getElm()
                .getElements('.qui-button-text')
                .setStyle('display', 'none');

            this.$Input.setStyles({
                display: null,
                opacity: 0,
                width  : 0
            });

            moofx(this.$Input).animate({
                opacity: 1,
                width  : 160
            }, {
                callback: function () {
                    this.$Input.focus();
                }.bind(this)
            });
        },

        /**
         * event on blur
         */
        $onBlur: function () {
            if (this.$Input.value !== '') {
                return;
            }

            moofx(this.$Input).animate({
                opacity: 0,
                width  : 0
            }, {
                callback: function () {
                    this.$type = false;
                    this.$Input.setStyle('display', 'none');

                    this.$SearchType.setAttribute('text', 'Suche');
                    this.$SearchType.setAttribute('textimage', 'icon-search');

                    this.$SearchType
                        .getElm()
                        .getElements('.qui-button-text')
                        .setStyle('display', null);

                }.bind(this)
            });
        },

        /**
         * event on key up
         * @param event
         */
        $onKeyUp: function (event) {
            if (event.key != 'enter') {
                return;
            }

            if (this.$Input.value === '') {
                return;
            }

            var image = this.$SearchType.getAttribute('textimage');

            this.$SearchType.setAttribute(
                'textimage',
                'icon-refresh icon-spin'
            );

            var Prom  = false,
                value = this.$Input.value;

            // trigger the search
            switch (this.$type) {
                case 'site':
                    Prom = this.searchSite(value);
                    break;

                case 'group':
                    Prom = this.searchGroup(value);
                    break;

                case 'user':
                    Prom = this.searchUser(value);
                    break;
            }

            if (!Prom) {
                this.$SearchType.setAttribute('textimage', image);
                return;
            }

            Prom.then(function () {
                this.$SearchType.setAttribute('textimage', image);
            }.bind(this));
        },

        /**
         * Search a site
         * Opens the site search
         *
         * @param {String} value
         * @return Promise
         */
        searchSite: function (value) {

            return new Promise(function (resolve, reject) {

                require([
                    'controls/projects/project/site/Search',
                    'utils/Panels'
                ], function (SearchPanel, PanelUtils) {

                    PanelUtils.openPanelInTasks(
                        new SearchPanel({
                            value: value
                        })
                    ).then(function (Panel) {

                        Panel.setAttributes({
                            value: value
                        });

                        Panel.search();

                        resolve();

                    }).catch(reject);

                }, reject);
            });
        },

        /**
         * Search a user
         * Opens the user search
         *
         * @param {String} value
         */
        searchUser: function (value) {

            return new Promise(function (resolve, reject) {

                require([
                    'controls/users/Panel',
                    'utils/Panels'
                ], function (SearchPanel, PanelUtils) {

                    PanelUtils.openPanelInTasks(
                        new SearchPanel({
                            search        : true,
                            searchSettings: {
                                userSearchString: value
                            }
                        })
                    );

                    resolve();

                }, reject);

            });
        },

        /**
         * Search a group
         * Opens the group search
         *
         * @param {String} value
         */
        searchGroup: function (value) {
            return new Promise(function (resolve, reject) {
                require([
                    'controls/groups/Panel',
                    'utils/Panels'
                ], function (SearchPanel, PanelUtils) {

                    PanelUtils.openPanelInTasks(
                        new SearchPanel({
                            search: value
                        })
                    );

                    resolve();
                }, reject);
            });
        }
    });
});
