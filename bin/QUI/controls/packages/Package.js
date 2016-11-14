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

    'text!controls/packages/Package.html',
    'css!controls/packages/Package.css'

], function (QUI, QUIControl, Packages, Mustache, template) {
    "use strict";

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

                this.$Elm.set({
                    html: Mustache.render(template, {
                        data       : data,
                        title      : data.title,
                        description: data.description,
                        image      : image,
                        support    : data.support || {},
                        require    : require

                    })
                });

                this.fireEvent('load', [this]);
            }.bind(this));
        }
    });
});
