/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 *
 * @event onLoad
 * @event onSearchBegin
 * @event onSearchEnd
 */
define('controls/packages/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Packages',
    'Mustache',
    'controls/packages/PackageList',

    'text!controls/packages/Search.html',
    'css!controls/packages/Search.css'

], function (QUI, QUIControl, QUIButton, Packages, Mustache, PackageList, template) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Search',

        Binds: [
            '$onInject',
            '$onClickInstall'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$List    = null;
            this.$Results = null;
            this.$Input   = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'qui-control-packages-search',
                html   : Mustache.render(template)
            });

            this.$Results = this.$Elm.getElement('.qui-control-packages-search-result');
            this.$Input   = this.$Elm.getElement('[type="search"]');

            this.$Elm.getElement('form').addEvent('submit', function (event) {
                event.stop();
                this.search();
            }.bind(this));

            this.$List = new PackageList({
                buttons: [{
                    icon  : 'fa fa-download',
                    title : 'Paket herunterladen und installieren',
                    styles: {
                        width: '100%'
                    },
                    events: {
                        onClick: this.$onClickInstall
                    }
                }]
            }).inject(this.$Results);

            return this.$Elm;
        },

        /**
         * Return the result list
         *
         * @returns {*} PackageList
         */
        getList: function () {
            return this.$List;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Input.focus();
            this.fireEvent('load');
        },

        /**
         * Return the list
         *
         * @returns {Object} PackageList
         */
        search: function () {
            this.fireEvent('searchBegin', [this]);

            Packages.search(this.$Input.value).then(function (result) {
                this.$List.clear();

                for (var name in result) {
                    if (!result.hasOwnProperty(name)) {
                        continue;
                    }

                    this.$List.addPackage({
                        name       : name,
                        title      : name,
                        description: result[name]
                    });
                }

                this.$List.refresh();

                this.fireEvent('searchEnd', [this]);
            }.bind(this)).catch(function () {
                this.$List.clear();
                this.fireEvent('searchEnd', [this]);
            }.bind(this));
        },

        /**
         * event: install button click
         *
         * @param {Object} Btn - qui/controls/buttons/Button
         * @param {event} event
         */
        $onClickInstall: function (Btn, event) {
            event.stop();

            this.fireEvent('onShowLoader', [this]);

            Packages.install([
                Btn.getAttribute('package')
            ]).then(function () {
                this.fireEvent('onHideLoader', [this]);
            }.bind(this));
        }
    });
});
