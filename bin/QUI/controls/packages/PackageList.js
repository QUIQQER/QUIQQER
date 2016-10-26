/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 *
 */
define('controls/packages/PackageList', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Locale',
    'Packages',
    'Mustache',

    'text!controls/packages/PackageList.ViewTile.html',
    'text!controls/packages/PackageList.ViewList.html',
    'css!controls/packages/PackageList.css'

], function (QUI, QUIControl, QUIButton, QUILocale, Packages, Mustache,
             templatePackageTile, templatePackageList) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/PackageList',

        Binds: [
            '$onInject',
            '$packageClick',
            '$packageClick'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$packages = [];
            this.$view     = options && options.view || 'tile';

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
                'class': 'qui-control-packages-packageList'
            });


            return this.$Elm;
        },

        /**
         *
         * @param data
         */
        addPackage: function (data) {
            this.$packages.push(data);
        },

        /**
         * clear the complete list
         */
        clear: function () {
            if (this.$Elm) {
                this.$Elm.set('html', '');
            }

            this.$packages = [];
        },

        /**
         * refresh the display
         */
        refresh: function () {
            switch (this.$view) {
                case 'list':
                    this.viewList();
                    break;

                default:
                case 'tile':
                    this.viewTile();
                    break;
            }
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.refresh();
        },

        /**
         * Show the packages in the tile view
         */
        viewTile: function () {
            if (!this.$Elm) {
                return;
            }

            var i, len, image, entry, Package;

            this.$Elm.set('html', '');

            for (i = 0, len = this.$packages.length; i < len; i++) {
                entry = this.$packages[i];
                image = '<span class="fa fa-gift"></span>';

                if (typeof entry.image !== 'undefined' && entry.image !== '') {
                    image = '<img src="' + entry.image + '" />';
                }

                Package = new Element('div', {
                    'class'    : 'packages-package-tile-package packages-package',
                    html       : Mustache.render(templatePackageTile, {
                        title      : entry.title,
                        description: entry.description,
                        image      : image
                    }),
                    'data-name': entry.name,
                    events     : {
                        click: this.$packageClick
                    }
                }).inject(this.$Elm);

                if (typeof entry.type === 'undefined' || !entry.type.match('quiqqer-')) {
                    continue;
                }

                new QUIButton({
                    icon  : 'fa fa-hdd-o',
                    title : 'Setup ausführen', // #locale
                    events: {
                        onClick: this.$setupClick
                    }
                }).inject(Package.getElement('.packages-package-tile-package-buttons'));
            }
        },

        /**
         * Show the packages in the list view
         */
        viewList: function () {
            var i, len, image, entry, Package;

            this.$Elm.set('html', '');

            for (i = 0, len = this.$packages.length; i < len; i++) {
                entry = this.$packages[i];
                image = '<span class="fa fa-gift"></span>';

                if (typeof entry.image !== 'undefined' && entry.image !== '') {
                    image = '<img src="' + entry.image + '" />';
                }

                Package = new Element('div', {
                    'class'    : 'packages-package-list-package packages-package',
                    html       : Mustache.render(templatePackageList, {
                        title      : entry.title,
                        description: entry.description,
                        image      : image
                    }),
                    'data-name': entry.name,
                    events     : {
                        click: this.$packageClick
                    }
                }).inject(this.$Elm);

                if (typeof entry.type === 'undefined' || !entry.type.match('quiqqer-')) {
                    continue;
                }

                new QUIButton({
                    icon  : 'fa fa-hdd-o',
                    title : 'Setup ausführen', // #locale
                    events: {
                        onClick: this.$setupClick
                    }
                }).inject(Package.getElement('.packages-package-list-package-buttons'));
            }
        },

        /**
         * Execute the setup of a package
         *
         * @param {Object} Btn
         * @param {Object} event
         * @returns {Promise}
         */
        $setupClick: function (Btn, event) {
            event.stop();

            var PackageNode = Btn.getElm().getParent('.packages-package');
            var pkgName     = PackageNode.get('data-name');

            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

            return Packages.setup(pkgName).then(function () {
                Btn.setAttribute('icon', 'fa fa-hdd-o');

                QUI.getMessageHandler().then(function (Handler) {
                    Handler.addSuccess(
                        QUILocale.get(lg, 'message.setup.successfull')
                    );
                });
            }).catch(function (error) {
                Btn.setAttribute('icon', 'fa fa-hdd-o');

                QUI.getMessageHandler().then(function (MH) {
                    if (typeOf(error) === 'string') {
                        MH.addError(error);
                        return;
                    }

                    MH.addError(error.getMessage());
                });
            });
        },

        /**
         * Opens the package information window
         *
         * @param {Object} event
         */
        $packageClick: function (event) {
            var Target = event.target;

            require(['controls/packages/PackageWindow'], function (PackageWindow) {
                if (!Target.hasClass('packages-package')) {
                    Target = Target.getParent('.packages-package');
                }

                if (!Target.hasClass('packages-package')) {
                    return;
                }

                new PackageWindow({
                    'package': Target.get('data-name')
                }).open();
            });
        }
    });
});
