/**
 * @module controls/packages/Update
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 * @requires qui/controls/buttons/Button
 * @requires Packages
 * @requires Mustache
 * @requires Ajax
 * @requires css!controls/packages/Server.css
 *
 * @event onLoad
 */
define('controls/packages/Update', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'Packages',
    'Mustache',
    'Ajax',
    'Locale',
    'utils/Favicon',
    'package/quiqqer/translator/bin/Translator',

    'text!controls/packages/Update.html',
    'css!controls/packages/Update.css'

], function (QUI, QUIControl, QUIButton, QUIConfirm, Packages, Mustache,
             QUIAjax, QUILocale, FaviconUtils, Translator, template) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Update',

        Binds: [
            '$onInject',
            'checkUpdates',
            'executeCompleteSetup'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'qui-control-packages-update',
                html   : Mustache.render(template)
            });

            var Buttons = this.$Elm.getElement('.qui-control-packages-update-buttons');

            this.$Update = new QUIButton({
                name     : 'update',
                text     : QUILocale.get(lg, 'packages.panel.btn.startUpdate'),
                textimage: 'fa fa-check-circle-o',
                events   : {
                    onClick: this.checkUpdates
                }
            }).inject(Buttons);

            this.$Setup = new QUIButton({
                name     : 'setup',
                text     : QUILocale.get(lg, 'packages.panel.btn.setup'),
                textimage: 'fa fa-hdd-o',
                events   : {
                    onClick: this.executeCompleteSetup
                },
                styles   : {
                    margin: '0 0 0 20px'
                }
            }).inject(Buttons);

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.fireEvent('load', [this]);
        },

        /**
         * Execute a complete setup
         *
         * @returns {Promise}
         */
        executeCompleteSetup: function () {
            var Button = this.$Setup;

            FaviconUtils.loading();

            Button.setAttribute('textimage', 'fa fa-spinner fa-spin');

            return QUI.getMessageHandler().then(function (MH) {
                return MH.addLoading('message.setup.runs');

            }).then(function (Loading) {

                return Packages.setup().then(function () {
                    return Translator.refreshLocale();
                }).then(function () {
                    Loading.finish(QUILocale.get(lg, 'message.setup.successfull'));

                    return QUI.getMessageHandler().then(function (Handler) {
                        Handler.pushSuccess(
                            QUILocale.get(lg, 'message.setup.successfull.title'),
                            QUILocale.get(lg, 'message.setup.successfull'),
                            false
                        );
                    });
                }).catch(function (Error) {
                    return QUI.getMessageHandler().then(function (MH) {
                        if (typeOf(Error) === 'string') {
                            MH.addError(Error);
                            Loading.finish(Error, 'error');
                            return;
                        }

                        MH.addError(Error.getMessage());
                        Loading.finish(Error.getMessage(), 'error');
                    });
                });

            }).then(function () {
                Button.setAttribute('textimage', 'fa fa-hdd-o');
                FaviconUtils.setDefault();
            });
        },

        /**
         * Execute a complete setup
         *
         * @returns {Promise}
         */
        checkUpdates: function () {
            var Button = this.$Update;

            Button.setAttribute('textimage', 'fa fa-spinner fa-spin');

            return Packages.checkUpdate().then(function (result) {

                var title   = QUILocale.get(lg, 'message.update.not.available.title'),
                    message = QUILocale.get(lg, 'message.update.not.available.description');

                if (result) {
                    title   = QUILocale.get(lg, 'message.update.available.title');
                    message = QUILocale.get(lg, 'message.update.available.description');
                }

                QUI.getMessageHandler().then(function (Handler) {
                    if (result) {
                        Handler.pushAttention(title, message, false);
                        Handler.addAttention(message);
                        return;
                    }

                    Handler.pushInformation(title, message, false);
                    Handler.addInformation(message);
                });

                Button.setAttribute('textimage', 'fa fa-check-circle-o');
            });
        }
    });
});
