/**
 * @module controls/icons/Confirm
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 */
define('controls/icons/Confirm', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale'

], function (QUI, QUIConfirm, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIConfirm,
        Type   : 'controls/icons/Confirm',

        Binds: [
            '$onOpen'
        ],

        options: {
            title    : QUILocale.get('quiqqer/quiqqer', 'control.icons.confirm.title'),
            icon     : 'fa fa-css3',
            maxHeight: 600,
            maxWidth : 800
        },

        initialize: function (options) {
            this.parent(options);

            this.$Frame = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            this.getContent().set('html', '');

            var id = this.getId();

            this.$Frame = new Element('iframe', {
                src        : URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/controls/icons/iconList.php?quiid=' + id,
                border     : 0,
                frameborder: 0,
                styles     : {
                    border: '0px solid #fff',
                    height: 'calc(100% - 10px)',
                    width : '100%'
                }
            }).inject(this.getContent());
        },

        /**
         * Return the selected icons
         *
         * @returns {Array}
         */
        getSelected: function () {
            if (typeof this.$Frame.contentWindow === 'undefined') {
                return [];
            }

            return this.$Frame.contentWindow.getSelected();
        },

        /**
         * Submit the window
         */
        submit: function () {
            if (typeof this.$Frame.contentWindow === 'undefined') {
                return;
            }

            var selected = this.$Frame.contentWindow.getSelected();

            if (!selected.length) {
                return;
            }

            this.fireEvent('submit', [this, selected]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});