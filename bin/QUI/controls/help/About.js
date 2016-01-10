/**
 * Help Window
 *
 * @module controls/help/About
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Popup
 */

define('controls/help/About', [

    'qui/controls/windows/Popup',
    'Locale'

], function (QUIPopup, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/help/About',

        Binds: [
            '$onOpen'
        ],

        options: {
            maxHeight: 300,
            maxWidth : 450,
            title    : 'About',
            closeButtonText : QUILocale.get('quiqqer/system', 'close')
        },

        initialize: function (options) {
            this.parent(options);
            this.addEvent('onOpen', this.$onOpen);
        },

        $onOpen: function () {
            // #locale
            this.getContent().set(
                'html',

                '<div style="text-align: center;">' +
                '<h2>QUIQQER Management System</h2>' +
                '<p><a href="http://www.quiqqer.com" target="_blank">www.quiqqer.com</a></p>' +
                '<br />' +
                'Version: ' + QUIQQER_VERSION +
                '<br />' +
                '<p>' +
                'Copyright ' +
                '<a href="http://www.pcsg.de" target="_blank">' +
                'http://www.pcsg.de' +
                '</a>' +
                '</p>' +
                '<p>Author: Henning Leutz & Moritz Scholz</p>' +
                '</div>'
            );
        }
    });
});
