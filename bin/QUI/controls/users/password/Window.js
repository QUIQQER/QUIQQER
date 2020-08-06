/**
 * @module controls/users/password/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require controls/users/password/Password
 * @require css!controls/users/password/Window.css
 *
 * @event onSuccess [this]
 * @event onError [this]
 */
define('controls/users/password/Window', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'controls/users/password/Password',
    'Locale',

    'css!controls/users/password/Window.css'

], function (QUI, QUIConfirm, Password, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIConfirm,
        Type   : 'controls/users/password/Password',

        Binds: [
            '$onOpen'
        ],

        options: {
            icon      : 'fa fa-key',
            title     : QUILocale.get('quiqqer/system', 'menu.profile.userPassword.text'),
            maxHeight : 470,
            maxWidth  : 340,
            uid       : false,
            autoclose : false,
            message   : false,
            mustChange: false,
            ok_button : {
                text     : QUILocale.get('quiqqer/system', 'accept'),
                textimage: 'fa fa-check'
            }
        },

        initialize: function (options) {
            this.parent(options);

            if (!this.getAttribute('uid')) {
                this.setAttribute('uid', USER.id);
            }

            this.$Password = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function (Win) {
            Win.getContent().addClass('qui-controls-user-password-window');
            Win.getContent().set('html', '');

            if (this.getAttribute('message')) {
                var Message = new Element('div', {
                    'class': 'qui-controls-user-password-window-message',
                    html   : this.getAttribute('message')
                }).inject(Win.getContent());

                Win.getContent().setStyles({
                    paddingTop: Message.getSize().y + 20
                });
            }

            this.$Password = new Password({
                uid       : this.getAttribute('uid'),
                mustChange: false,
                events    : {
                    onSaveBegin: function () {
                        Win.Loader.show();
                    },
                    onSave     : function () {
                        Win.Loader.hide();
                    }
                }
            }).inject(Win.getContent());

            if (this.getAttribute('mustChange')) {
                this.setAttribute('autoclose', false);
                this.setAttribute('backgroundClosable', false);

                this.Background.getElm().removeEvents('click');
                this.$Title.getElements('.qui-window-popup-title-close').destroy();
                this.getButton('cancel').destroy();
            }
        },

        /**
         * Submit the new password
         */
        submit: function () {
            var self = this;

            this.Loader.show();
            this.$Password.save().then(function () {
                this.close();
                this.fireEvent('success', [this]);
            }.bind(this)).catch(function (e) {
                this.Loader.hide();
                this.fireEvent('error', [this]);

                var Message,
                    Content = self.getContent();

                Message = Content.getElement('.qui-controls-user-password-window-message');

                if (Message) {
                    Message.html = e.getMessage();
                } else {
                    Message = new Element('div', {
                        'class': 'qui-controls-user-password-window-message',
                        html   : e.getMessage()
                    }).inject(Content);
                }

                Content.setStyles({
                    paddingTop: Message.getSize().y + 20
                });

            }.bind(this));
        }
    });
});