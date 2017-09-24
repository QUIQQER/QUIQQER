/**
 * Makes a user input field to a field selection field
 *
 * @module controls/email/Select
 * @author www.pcsg.de (Patrick Müller)
 *
 * @event onAddItem [ this, id ]
 * @event onChange [ this ]
 */
define('controls/email/Select', [

    'qui/QUI',
    'qui/controls/elements/Select',
    'Locale',
    'Ajax',

    //'css!controls/email/Select.css'

], function (QUIControl, QUIElementSelect, QUILocale, QUIAjax) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    /**
     * @class controls/email/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIElementSelect,
        Type   : 'controls/email/Select',

        Binds: [
            '$onCreate',
            '$onSearchButtonClick',
            'mailSearch',
            '$setValue'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('icon', 'fa fa-at');
            this.setAttribute('child', 'controls/email/SelectItem');
            this.setAttribute('searchbutton', false);
            //this.setAttribute('Search', this.mailSearch);

            this.$entries = [];

            this.setAttribute(
                'placeholder',
                QUILocale.get(lg, 'control.email.select.placeholder')
            );

            this.addEvents({
                onCreate : this.$onCreate,
                onAddItem: this.$onAddItem
            })
        },

        /**
         * Event: onCreate
         */
        $onCreate: function () {
            var self = this;

            this.$Search.addEvent('keydown', function (event) {
                if (event.code === 13) {
                    var mail = event.target.value;

                    if (self.$entries.contains(mail)) {
                        return;
                    }

                    self.Loader.show();

                    self.$checkMail(mail).then(function(isValid) {
                        self.Loader.hide();

                        if (isValid) {
                            self.addItem(mail);
                            self.$Input.value = '';
                        }

                        self.$Input.focus();
                    });
                }
            });
        },

        /**
         * Event: onAddItem
         *
         * @param {Object} Control
         * @param {String} mailaddress
         * @param {Object} Child [qui/controls/elements/SelectItem]
         */
        $onAddItem: function(Control, mailaddress, Child)
        {
            Child.addEvent('onDestroy', function() {
                this.$entries.erase(mailaddress);
            }.bind(this));

            this.$entries.push(mailaddress);
        },

        /**
         * Execute the search
         *
         * @param {String} value
         * @returns {Promise}
         */
        mailSearch: function (value) {
            return Promise.resolve();
        },

        /**
         * event : on search click
         *
         * @param {Object} Select
         * @param {Object} Btn
         */
        $onSearchButtonClick: function (Select, Btn) {
            // @todo
        },

        /**
         * Validate email syntax
         *
         * @param {String} mail
         * @returns {Promise}
         */
        $checkMail: function(mail) {
            return new Promise(function(resolve, reject) {
                QUIAjax.get('ajax_email_validate', resolve, {
                    mail   : mail,
                    onError: reject
                })
            });
        }
    });
});
