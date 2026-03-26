/**
 * @event onSuccess [this]
 * @event onError [this]
 */
define('controls/users/password/Window', [

    'qui/QUI',
    'qui/controls/windows/SimpleConfirmWindow',
    'controls/users/password/Password',
    'Locale',

    'css!controls/users/password/Window.css'

], function (QUI, SimpleConfirmWindow, Password, QUILocale) {
    "use strict";

    return new Class({
        Extends: SimpleConfirmWindow,
        Type: 'controls/users/password/Password',

        Binds: [
            '$onOpen',
            'create'
        ],

        options: {
            'class': 'qui-controls-user-password-quiWindow',
            icon: 'fa fa-key',
            title: QUILocale.get('quiqqer/core', 'menu.profile.userPassword.text'),
            maxHeight: 600,
            maxWidth: 500,
            uid: false,
            autoclose: false,
            message: false,
            mustChange: false,
            showCloseButton: true,
            buttonSubmit: {
                text: QUILocale.get('quiqqer/core', 'accept'),
                icon: 'fa fa-check',
                'class': 'btn btn-success'
            },
            buttonCancel: {
                text: QUILocale.get('quiqqer/core', 'cancel'),
                icon: false,
                'class': 'btn btn-inline-body'
            }
        },

        initialize: function (options) {
            options = options || {};

            if (options.ok_button && !options.buttonSubmit) {
                options.buttonSubmit = {
                    text: options.ok_button.text || false,
                    icon: options.ok_button.textimage || false,
                    'class': 'btn btn-success'
                };
            }

            if (options.cancel_button && !options.buttonCancel) {
                options.buttonCancel = {
                    text: options.cancel_button.text || false,
                    icon: options.cancel_button.textimage || false,
                    'class': 'btn btn-inline-body'
                };
            }

            if (options.mustChange) {
                options.autoclose = false;
                options.backgroundClosable = false;
                options.buttonCancel = false;
                options.showCloseButton = false;
            }

            this.parent(options);

            if (!this.getAttribute('uid')) {
                this.setAttribute('uid', USER.id);
            }

            this.$Password = null;
            this.$Message = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        create: function () {
            const Elm = this.parent();

            if (this.getAttribute('showCloseButton') === false) {
                const CloseButton = Elm.querySelector('.qui-window-simpleWindow__closeBtn');

                if (CloseButton && CloseButton.parentNode) {
                    CloseButton.parentNode.removeChild(CloseButton);
                }
            }

            return Elm;
        },

        /**
         * event : on open
         */
        $onOpen: function (Win) {
            const Content = Win.getContent();
            const initialMessage = this.getAttribute('message');

            Content.set('html', '<div class="qui-controls-user-password-window"></div>');
            const Wrapper = Content.querySelector('.qui-controls-user-password-window');
            this.$Message = null;

            if (this.getAttribute('title')) {
                new Element('div', {
                    'class': 'qui-controls-user-password-window-header',
                    html: '<h1>' + this.getAttribute('title') + '</h1>'
                }).inject(Wrapper);
            }

            this.$Message = new Element('div', {
                'class': 'q-message q-message-info',
                html: initialMessage || ''
            }).inject(Wrapper);

            if (!initialMessage) {
                this.$Message.setStyle('display', 'none');
            }

            this.$Password = new Password({
                uid: this.getAttribute('uid'),
                mustChange: false,
                events: {
                    onSaveBegin: function () {
                        Win.Loader.show();
                    },
                    onSave: function () {
                        Win.Loader.hide();
                    }
                }
            }).inject(Wrapper);

            if (this.getAttribute('mustChange')) {
                this.Background.getElm().removeEvents('click');
            }
        },

        /**
         * Submit the new password
         */
        submit: function () {
            const self = this;

            this.Loader.show();
            this.$Password.save().then(function () {
                self.$setMessage(false);
                this.close();
                this.fireEvent('success', [this]);
            }.bind(this)).catch(function (e) {
                this.Loader.hide();
                this.fireEvent('error', [this]);
                self.$setMessage(e.getMessage(), 'error');

            }.bind(this));
        },

        $setMessage: function (message, type) {
            if (!this.$Message) {
                return;
            }

            this.$Message.removeClass('q-message-info');
            this.$Message.removeClass('q-message-error');

            if (!message) {
                this.$Message.set('html', '');
                this.$Message.setStyle('display', 'none');
                return;
            }

            this.$Message.set('html', message);
            this.$Message.addClass(type === 'error' ? 'q-message-error' : 'q-message-info');
            this.$Message.setStyle('display', null);
        },

        cancel: function () {
            if (this.getAttribute('mustChange')) {
                return;
            }

            this.parent();
        }
    });
});
