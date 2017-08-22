/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onLoad
 */
define('controls/packages/Installed', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Packages',
    'Mustache',
    'Locale',
    'controls/packages/PackageList',

    'text!controls/packages/Installed.html',
    'css!controls/packages/Installed.css'

], function (QUI, QUIControl, QUIButton, Packages, Mustache, QUILocale, PackageList, template) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Installed',

        Binds: [
            '$onInject',
            '$refreshFilter',
            '$setView'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$List = new PackageList({
                view: options.view || 'list'
            });

            this.$Result = null;
            this.$Search = null;

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
                'class': 'qui-control-packages-installed',
                'html' : Mustache.render(template)
            });

            this.$Search = this.$Elm.getElement('.qui-control-packages-installed-search');
            this.$Result = this.$Elm.getElement('.qui-control-packages-installed-result');

            this.$List.inject(this.$Result);

            this.$SearchInput = this.$Elm.getElement('[type="search"]');

            this.$SearchInput.addEvents({
                change : this.$refreshFilter,
                keyup  : this.$refreshFilter,
                mouseup: this.$refreshFilter,
                cancel : this.$refreshFilter,
                blur   : this.$refreshFilter
            });

            this.$ViewTile = new QUIButton({
                name  : 'viewTile',
                title : QUILocale.get('quiqqer/quiqqer', 'packages.panel.menu'),
                icon  : 'fa fa-th',
                styles: {
                    width: 50
                },
                events: {
                    onClick: this.$setView
                }
            }).inject(this.$Search, 'top');

            this.$ViewList = new QUIButton({
                name  : 'viewList',
                title : QUILocale.get('quiqqer/quiqqer', 'packages.panel.menu'),
                icon  : 'fa fa-th-list',
                styles: {
                    width: 50
                },
                events: {
                    onClick: this.$setView
                }
            }).inject(this.$Search, 'top');

            this.$ViewList.setActive();

            this.$Elm.getElement('form').addEvent('submit', function (event) {
                event.stop();
                this.$refreshFilter();
            }.bind(this));
            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            self.$List.clear();

            return Packages.getInstalledPackages().then(function (result) {
                result.sort(function (a, b) {
                    var nameA = a.title.toUpperCase();
                    var nameB = b.title.toUpperCase();

                    if (nameA < nameB) {
                        return -1;
                    }

                    if (nameA > nameB) {
                        return 1;
                    }

                    return 0;
                });

                for (var i = 0, len = result.length; i < len; i++) {
                    self.$List.addPackage(result[i]);
                }

                self.$List.refresh();
                self.fireEvent('load', [self]);
            });
        },

        /**
         * Return the list
         *
         * @returns {Object} PackageList
         */
        getList: function () {
            return this.$List;
        },

        /**
         * Filter the package list
         */
        $refreshFilter: function () {
            this.$List.filter(this.$SearchInput.value);
        },

        $setView: function (Btn) {
            switch (Btn.getAttribute('name')) {
                case 'viewTile':
                    this.$ViewTile.setActive();
                    this.$ViewList.setNormal();
                    this.$List.viewTile();
                    break;

                default:
                    this.$ViewTile.setNormal();
                    this.$ViewList.setActive();
                    this.$List.viewList();
            }
        }
    });
});
