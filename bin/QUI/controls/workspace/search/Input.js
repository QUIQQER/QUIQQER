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
    'Mustache',
    'controls/workspace/search/Search',

    'text!controls/workspace/search/Input.html',
    'css!controls/workspace/search/Input.css'

], function (QUI, QUIControl, Mustache, Search, template) {
    "use strict";

    if (!("Search" in window.QUIQQER)) {
        window.QUIQQER.Search = new Search();
    }

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/workspace/search/Input',

        Binds: [
            'create',
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

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Input.addEvent('focus', function () {

                window.QUIQQER.Search.open().then(function () {
                    window.QUIQQER.Search.setValue(this.$Input.value);
                    window.QUIQQER.Search.search();
                }.bind(this));

            }.bind(this));
        }
    });
});
