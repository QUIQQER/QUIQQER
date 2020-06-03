/**
 * @module controls/users/address/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onLoad [self]
 */
define('controls/users/address/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Users'

], function (QUI, QUIControl, QUIAjax, Users) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/users/address/Select',

        Binds: [
            '$onImport',
            '$onInject'
        ],

        options: {
            name: ''
        },

        initialize: function (options) {
            this.parent(options);

            this.$Select = null;
            this.$User   = null;
            this.$value  = false;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('select', {
                disabled: true
            });

            return this.$Elm;
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Elm.disabled = true;

            this.setAttribute('name', this.$Elm.name);
            this.setValue(this.$Elm.value);

            if (this.$Elm.get('data-value')) {
                this.setValue(this.$Elm.get('data-value'));
            }

            if (this.$Elm.value && this.$Elm.value !== '') {
                this.setValue(this.$Elm.value);
            }

            this.$onInject();
        },

        /**
         * Set the user for the control
         *
         * @param {Object} User
         */
        setUser: function (User) {
            if (Users.isUser(User)) {
                this.$User = User;
            }
        },

        /**
         * Set the select value (Address-Id)
         *
         * @param {String} value
         */
        setValue: function (value) {
            if (value === '' || value === false) {
                return;
            }

            this.$value = value;

            if (this.$Elm) {
                this.$Elm.value = value;
            }
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            if (!Users.isUser(this.$User)) {
                var PanelNode = this.getElm().getParent('.qui-panel'),
                    Panel     = QUI.Controls.getById(PanelNode.get('data-quiid'));

                if ("getUser" in Panel) {
                    this.setUser(Panel.getUser());
                }
            }

            if (!Users.isUser(this.$User)) {
                this.fireEvent('load', [this]);
                return;
            }

            var self = this;

            QUIAjax.get('ajax_users_address_list', function (result) {
                var i, len, text, entry;

                new Element('option', {
                    html : '',
                    value: ''
                }).inject(self.$Elm);

                for (i = 0, len = result.length; i < len; i++) {
                    entry = result[i];

                    text = entry.id + ': ' +
                        entry.street_no + ',' +
                        entry.zip + ' ' + entry.city + ', ' +
                        entry.country;

                    new Element('option', {
                        html : text,
                        value: entry.id
                    }).inject(self.$Elm);
                }

                self.$Elm.disabled = false;

                if (self.$value) {
                    self.setValue(self.$value);
                }

                self.fireEvent('load', [self]);
            }, {
                uid: this.$User.getId()
            });
        }
    });
});
