/**
 * Logout popup / window
 *
 * @event onQuiqqerUserAuthLogout
 */
define('controls/users/LogoutWindow', [

    'qui/QUI',
    'qui/controls/windows/SimpleConfirmWindow',
    'controls/users/Login',
    'Locale',
    'Ajax',

    'css!controls/users/LogoutWindow.css'

], function (QUI, SimpleConfirmWindow, Login, QUILocale, Ajax) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: SimpleConfirmWindow,
        Type: 'controls/users/LogoutWindow',

        Binds: [
            'logout',
            '$onOpen'
        ],

        options: {
            title: QUILocale.get(lg, 'window.logout.title'),
            text: QUILocale.get(lg, 'window.logout.information'),
            maxWidth: 400,
            maxHeight: 300,
            buttonCancel: {
                text: QUILocale.get(lg, 'window.logout.button.cancel'),
                icon: false,
                'class': 'btn btn-link-body'
            },
            buttonSubmit: {
                text: QUILocale.get(lg, 'window.logout.button.ok'),
                icon: 'fa fa-sign-out',
                'class': 'btn btn-success'
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
                    'class': 'btn btn-link-body'
                };
            }

            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen,
                onSubmit: this.logout
            });
        },

        /**
         * event : on open
         */
        $onOpen: function (Win) {
            const Content = Win.getContent();
            const title = this.getAttribute('title');
            const text = this.getAttribute('text');

            Content.set('html', '<div class="qui-controls-users-logout-window"></div>');

            const Wrapper = Content.querySelector('.qui-controls-users-logout-window');
            const Header = new Element('div', {
                'class': 'qui-controls-users-logout-window__header'
            }).inject(Wrapper);

            new Element('span', {
                'class': 'qui-controls-users-logout-window__icon icon-sign-out fa fa-sign-out'
            }).inject(Header);

            if (title) {
                new Element('h1', {
                    'class': 'qui-controls-users-logout-window__title',
                    html: title
                }).inject(Header);
            }

            if (text) {
                new Element('div', {
                    'class': 'qui-controls-users-logout-window__message',
                    html: text
                }).inject(Wrapper);
            }
        },

        /**
         * Execute the logout
         */
        logout: function () {
            this.Loader.show();

            Ajax.post('ajax_users_logout', function () {
                QUI.fireEvent('quiqqerUserAuthLogout');

                if (window.location.toString().indexOf('#') !== -1) {
                    window.location.reload();
                    return;
                }

                window.location = window.location;
            });
        }
    });
});
