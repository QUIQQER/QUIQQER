/**
 * Window: Edit a server
 *
 * @module controls/packages/server/DeleteServerWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require Mustache
 * @require Locale
 * @require Packages
 * @require text!controls/packages/server/Server.html
 * @require css!controls/packages/server/Server.css
 */
define('controls/packages/server/DeleteServerWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Mustache',
    'Locale',
    'Packages',

    'text!controls/packages/server/Server.html',
    'css!controls/packages/server/Server.css'

], function (QUI, QUIConfirm, Mustache, QUILocale, Packages, templateAddServer) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/packages/server/DeleteServerWindow',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        options: {
            title    : QUILocale.get(lg, 'packages.panel.server.win.delete.title'),
            icon     : 'fa fa-trash',
            maxHeight: 600,
            maxWidth : 400,
            autoclose: false,
            ok_button: {
                text     : QUILocale.get('quiqqer/system', 'delete'),
                textimage: 'fa fa-trash'
            },
            server   : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Server = null;
            this.$Type   = null;
            this.$Image  = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            if (!this.getAttribute('server')) {
                this.close();
                return;
            }

            var Content = this.getContent();

            Content.set('html', Mustache.render(templateAddServer, {
                description: QUILocale.get(lg, 'packages.panel.server.win.remove.text')
            }));

            var Form = Content.getElement('form');

            Form.addEvent('submit', function (event) {
                event.stop();
            }.bind(this));

            this.$Server = Form.elements.server;
            this.$Type   = Form.elements.type;
            this.$Image  = Content.getElement('.qui-control-packages-server-image');

            this.$Type.addEvent('change', function () {
                this.$Image.set('html', Packages.getServerTypeIcon(this.$Type.value));
            }.bind(this));

            this.$Server.value = this.getAttribute('server');

            Packages.getServer(this.getAttribute('server')).then(function (data) {
                this.$Type.value = data.type;
                this.$Type.fireEvent('change');

                this.$Server.set('disabled', true);
                this.$Type.set('disabled', true);

                this.Loader.hide();
            }.bind(this));
        },

        /**
         * Submit - add the server
         *
         * @return {Promise}
         */
        submit: function () {
            this.Loader.show();

            return Packages.removeServer(this.getAttribute('server')).then(function () {
                this.fireEvent('submit', [this]);
                this.close();
            }.bind(this));
        }
    });
});
