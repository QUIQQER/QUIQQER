/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 *
 * @event onLoad
 */
define('controls/packages/Installed', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Packages',
    'Mustache',
    'controls/packages/PackageList'

], function (QUI, QUIControl, QUIButton, Packages, Mustache, PackageList) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Installed',

        Binds: [
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$List = new PackageList({
                view: options.view || 'tile'
            });

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
                'class': 'qui-control-packages-installed'
            });


            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            return Packages.getInstalledPackages().then(function (result) {
                for (var i = 0, len = result.length; i < len; i++) {
                    self.$List.addPackage(result[i]);
                }

                self.$List.inject(self.$Elm);
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
        }
    });
});
