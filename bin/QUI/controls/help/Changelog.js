/**
 * Help Window
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/help/Changelog', [

    'qui/controls/windows/Popup',
    'Ajax'

], function (Popup, Ajax) {
    "use strict";

    return new Class({

        Extends: Popup,
        Type   : 'controls/help/Changelog',

        Binds: [
            '$onOpen'
        ],

        options: {
            title    : 'Changelog',
            maxHeight: 800,
            maxWidth : 600
        },

        initialize: function (options) {
            this.parent(options);
            this.addEvent('onOpen', this.$onOpen);
        },

        $onOpen: function () {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_system_changelog', function (result) {
                var Content = self.getContent();

                Content.set('html', '<pre><code></code></pre>');
                Content.getElement('code').set('html', result);

                self.Loader.hide();
            });
        }
    });
});
