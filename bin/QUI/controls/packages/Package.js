/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 * @requires Packages
 * @requires Mustache
 * @requires text!controls/packages/Package.html
 * @requires css!controls/packages/Package.css
 *
 * @event onLoad
 */
define('controls/packages/Package', [

    'qui/QUI',
    'qui/controls/Control',
    'Packages',
    'Mustache',
    'Locale',

    'text!controls/packages/Package.html',
    'css!controls/packages/Package.css'

], function (QUI, QUIControl, Packages, Mustache, QUILocale, template) {
    "use strict";

    var lg = "quiqqer/quiqqer";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Package',

        Binds: [
            '$onInject'
        ],

        options: {
            'package': false
        },

        initialize: function (options) {
            this.parent(options);

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
                'class': 'qui-control-package'
            });


            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            Packages.getPackage(this.getAttribute('package')).then(function (data) {
                var image = '<span class="fa fa-gift"></span>';

                if (data.image) {
                    image = '<img src="' + data.image + '" />';
                }

                var require = [];

                for (var key in data.require) {
                    if (!data.require.hasOwnProperty(key)) {
                        continue;
                    }

                    require.push({
                        name   : key,
                        version: data.require[key]
                    });
                }

                var locale = {
                    support     : QUILocale.get(lg, 'packages.dialog.info.support'),
                    preview     : QUILocale.get(lg, 'packages.dialog.info.preview'),
                    noPreview   : QUILocale.get(lg, 'packages.dialog.info.preview.none'),
                    more        : QUILocale.get(lg, 'packages.dialog.info.more'),
                    name        : QUILocale.get(lg, 'packages.dialog.info.name'),
                    license     : QUILocale.get(lg, 'packages.dialog.info.license'),
                    version     : QUILocale.get(lg, 'packages.dialog.info.version'),
                    type        : QUILocale.get(lg, 'packages.dialog.info.type'),
                    hash        : QUILocale.get(lg, 'packages.dialog.info.hash'),
                    dependencies: QUILocale.get(lg, 'packages.dialog.info.dependencies')
                };

                this.$Elm.set({
                    html: Mustache.render(template, {
                        data       : data,
                        title      : data.title,
                        description: data.description,
                        image      : image,
                        support    : data.support || {},
                        require    : require,
                        locale     : locale
                    })
                });

                this.fireEvent('load', [this]);
            }.bind(this));
        }
    });
});
