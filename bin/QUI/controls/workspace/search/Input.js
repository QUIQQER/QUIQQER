/**
 * @module controls/workspace/search/Input
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Mustache
 * @require controls/workspace/search/Search
 * @require text!controls/workspace/search/Input.html
 * @require css!controls/workspace/search/Input.css
 */
define('controls/workspace/search/Input', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Mustache',
    'controls/workspace/search/Search',

    'text!controls/workspace/search/Input.html',
    'css!controls/workspace/search/Input.css'

], function (QUI, QUIControl, QUIButton, Mustache, Search, template) {
    "use strict";

    if (!("Search" in window.QUIQQER)) {
        window.QUIQQER.Search = new Search();
    }

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/workspace/search/Input',

        Binds: [
            'create',
            'openSearch',
            '$onInject',
            '$collectKeyUp'
        ],

        options: {
            styles: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * event : on create
         */
        create: function () {
            var Elm = this.parent();

            Elm.addClass('qui-workspace-search-input');
            Elm.set('html', Mustache.render(template));

            this.$Input = Elm.getElement('input');

            if (this.getAttribute('styles')) {
                Elm.setStyles(this.getAttribute('styles'));
            }

            window.QUIQQER.Search.addEvent('close', function () {
                this.$Input.value = window.QUIQQER.Search.getValue();
            }.bind(this));

            new QUIButton({
                icon  : 'fa fa-search',
                events: {
                    onClick: this.openSearch
                }
            }).inject(Elm);

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Input.addEvent('keyup', function (event) {
                if (event.key === 'enter') {
                    this.openSearch();
                }
            }.bind(this));
        },

        /**
         * Opent the desktop search
         */
        openSearch: function () {
            window.QUIQQER.Search.open().then(function () {
                window.QUIQQER.Search.setValue(this.$Input.value);
                window.QUIQQER.Search.search();
            }.bind(this));
        }
    });
});
