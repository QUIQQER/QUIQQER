/**
 * @module controls/packages/upload/List
 */
define('controls/packages/upload/List', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'controls/packages/PackageList',

    'css!controls/packages/upload/List.css'

], function (QUI, QUIControl, QUIAjax, PackageList) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/upload/List',

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @return {Element}
         */
        create: function () {
            this.$Elm = this.parent();
            this.$Elm.addClass('quiqqer-packages-uploadedList');

            this.$List = new PackageList({
                showButtons: false
            }).inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var self = this;

            QUIAjax.get('ajax_system_packages_upload_getList', function (result) {
                for (var i = 0, len = result.length; i < len; i++) {
                    self.$List.addPackage(result[i]);
                }

                self.$List.viewList();
            });
        }
    });
});