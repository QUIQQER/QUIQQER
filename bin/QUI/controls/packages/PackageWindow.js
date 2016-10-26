/**
 * @module controls/packages/PackageWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/windows/Popup
 * @requires controls/packages/Package
 */
define('controls/packages/PackageWindow', [

    'qui/QUI',
    'qui/controls/windows/Popup',
    'controls/packages/Package'

], function (QUI, QUIPopup, Package) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/packages/PackageWindow',

        Binds: [
            '$onOpen'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 800,
            'package': false,
            buttons  : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Package = null;

            this.setAttribute('title', this.getAttribute('package'));
            this.setAttribute('icon', 'fa fa-gift');

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            this.getContent().set({
                html  : '',
                styles: {
                    padding: 0
                }
            });

            this.Loader.show();

            this.$Package = new Package({
                'package': this.getAttribute('package'),
                events   : {
                    onLoad: function () {
                        this.Loader.hide();
                    }.bind(this)
                }
            }).inject(this.getContent());
        }
    });
});
