/**
 * @module controls/icons/Confirm
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 */
define('controls/icons/Confirm', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale',

    'css!controls/icons/Confirm.css'

], function (QUI, QUIConfirm, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIConfirm,
        Type   : 'controls/icons/Confirm',

        Binds: [
            '$onOpen'
        ],

        options: {
            title    : QUILocale.get('quiqqer/quiqqer', 'control.icons.confirm.title'),
            icon     : 'fa fa-css3',
            'class'  : 'qui-window-popup-icons',
            maxHeight: 600,
            maxWidth : 800
        },

        initialize: function (options) {
            this.parent(options);

            this.$Frame          = null;
            this.$Search         = null;
            this.$IconContainer  = null;
            this.icons           = null;
            this.allIconsVisible = true;
            this.NoResultsInfo   = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            this.getContent().set('html', '');

            var self               = this,
                id                 = this.getId(),
                SearchContainer    = this.createSearch();

            SearchContainer.inject(this.getContent(), 'top');

            if (this.$Search) {
                this.$Search.focus();
            }

            var frameHeight = SearchContainer.getSize().y + 10;

            this.$Frame = new Element('iframe', {
                'class'    : 'window-iconSelect-iframe',
                src        : URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/controls/icons/iconList.php?quiid=' + id,
                border     : 0,
                frameborder: 0,
                styles     : {
                    border: '0px solid #fff',
                    height: 'calc(100% - ' + frameHeight + 'px)',
                    width : '100%'
                },
                events     : {
                    load: function () {
                        self.$IconContainer = self.$Frame.contentDocument.getElement('div.icons');
                        self.icons          = self.$Frame.contentDocument.getElements('.icons-entry');
                    }
                }
            }).inject(this.getContent());
        },

        /**
         * Return the selected icons
         *
         * @returns {Array}
         */
        getSelected: function () {
            if (typeof this.$Frame.contentWindow === 'undefined') {
                return [];
            }

            return this.$Frame.contentWindow.getSelected();
        },

        /**
         * Submit the window
         */
        submit: function () {
            if (typeof this.$Frame.contentWindow === 'undefined') {
                return;
            }

            var selected = this.$Frame.contentWindow.getSelected();

            if (!selected.length) {
                return;
            }

            this.fireEvent('submit', [this, selected]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        },

        /**
         * Create search HTML Nodes - outer div with icon and input field
         *
         * @returns HTML Node {Element}
         */
        createSearch: function () {
            var SearchContainer = new Element('div', {
                'class': 'window-iconSelect-searchContainer',
                html   : '<span class="fa fa-search window-iconSelect-search-prefix"></span>'
            });

            var self     = this,
                inputEsc = false;

            this.$Search = new Element('input', {
                'class'    : 'window-iconSelect-search',
                type       : 'text',
                placeholder: QUILocale.get('quiqqer/quiqqer', 'control.icons.confirm.filterIcons'),
                events     : {
                    keydown: function (event) {
                        if (event.key === 'esc') {
                            event.stop();
                            inputEsc = true;
                            return;
                        }

                        inputEsc = false;
                    },
                    keyup  : function (event) {
                        // Esc clears the input field
                        if (inputEsc) {
                            event.stop();
                            this.value = '';
                            if (!self.allIconsVisible) {
                                self.showAllIcons();
                            }
                            return;
                        }

                        self.execSearch(this.value.trim());
                    }
                }
            }).inject(SearchContainer, 'top');

            return SearchContainer;
        },

        /**
         * Start search with delay
         *
         * @param searchTerm {string}
         */
        execSearch: function (searchTerm) {
            var self = this;

            if (searchTerm === '') {
                if (!this.allIconsVisible) {
                    this.showAllIcons();
                }

                return;
            }

            // prevents the search from being execute
            // after action-less keys (alt, shift, ctrl, etc.)
            if (searchTerm === this.searchTerm) {
                return;
            }

            if (this.$Timer) {
                clearInterval(this.$Timer);
            }

            this.$Timer = (function () {
                self.searchTerm = searchTerm;

                self.hideAllIcons();
                self.findAndShowIcons(searchTerm.toLowerCase());
            }).delay(400);
        },

        /**
         * Hide all icons
         */
        hideAllIcons: function () {
            this.icons.forEach(function (Icon) {
                Icon.setStyle('display', 'none');
            });

            this.allIconsVisible = false;
        },

        /**
         * Show all icons
         */
        showAllIcons: function () {
            this.hideNoResultsInfo();

            this.icons.forEach(function (Icon) {
                Icon.setStyle('display', '');
            });

            this.allIconsVisible = true;
        },

        /**
         * Find and show any icon that matches to search term
         *
         * @param searchTerm {string}
         */
        findAndShowIcons: function (searchTerm) {
            var self    = this,
                founded = false;

            this.icons.forEach(function (Icon) {
                var name = Icon.getAttribute('data-icon');

                if (name.indexOf(searchTerm) >= 0) {
                    Icon.setStyle('display', '');

                    founded              = true;
                    self.allIconsVisible = false;
                }
            });

            if (!founded) {
                this.showNoResultsInfo();
                return;
            }

            this.hideNoResultsInfo()
        },

        /**
         * Create and show no-results-info div
         */
        showNoResultsInfo: function () {
            if (this.NoResultsInfo) {
                return;
            }

            this.NoResultsInfo = new Element('div', {
                'class': 'no-results-info',
                html   : '<p>' + QUILocale.get('quiqqer/quiqqer', 'control.icons.confirm.noResultsInfo') + '</p><span class="fa fa-css3"></span>'
            }).inject(this.$IconContainer);

            moofx(this.NoResultsInfo).animate({
                opacity: 0.25
            }, {
                duration: 300
            });
        },

        /**
         * Hide / destroy no-results-info
         */
        hideNoResultsInfo: function () {
            if (this.NoResultsInfo) {
                this.NoResultsInfo.destroy();
                this.NoResultsInfo = null;
            }
        }
    });
});