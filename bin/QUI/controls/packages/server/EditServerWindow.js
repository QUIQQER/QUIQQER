/**
 * Window: Edit a server
 *
 * @module controls/packages/server/EditServerWindow
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
define('controls/packages/server/EditServerWindow', [

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
        Type   : 'controls/packages/server/EditServerWindow',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        options: {
            title    : QUILocale.get(lg, 'packages.panel.server.win.edit.title'),
            icon     : 'fa fa-server',
            maxHeight: 600,
            maxWidth : 400,
            autoclose: false,
            ok_button: {
                text     : QUILocale.get('quiqqer/quiqqer', 'edit'),
                textimage: 'fa fa-server'
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

            this.Loader.show();

            var Content = this.getContent();

            Content.set('html', Mustache.render(templateAddServer, {
                description: ''
            }));

            var Form = Content.getElement('form');

            Form.addEvent('submit', function (event) {
                event.stop();
                this.submit();
            }.bind(this));

            this.$Server = Form.elements.server;
            this.$Type   = Form.elements.type;
            this.$Image  = Content.getElement('.qui-control-packages-server-image');

            this.$Type.addEvent('change', function () {
                this.$Image.set('html', Packages.getServerTypeIcon(this.$Type.value));
            }.bind(this));

            this.$Server.value = this.getAttribute('server');
            this.$Server.focus();

            Packages.getServer(this.getAttribute('server')).then(function (data) {
                this.$Type.value = data.type;
                this.$Type.fireEvent('change');
                this.Loader.hide();
            }.bind(this));
        },

        /**
         * Submit - add the server
         *
         * @return {Promise}
         */
        submit: function () {
            if (this.$Server.value === '' || this.$Type.value === '') {
                if ("checkValidity" in this.$Server) {
                    this.$Server.checkValidity();
                }

                // chrome validate message
                if ("reportValidity" in this.$Server) {
                    this.$Server.reportValidity();
                }

                return Promise.reject();
            }

            this.Loader.show();

            return Packages.editServer(this.getAttribute('server'), {
                type  : this.$Type.value,
                server: this.$Server.value
            }).then(function () {
                this.fireEvent('submit', [this]);
                this.close();
            }.bind(this));
        }
    });
});
