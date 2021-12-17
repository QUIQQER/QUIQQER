/**
 * @module controls/messages/Button
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/messages/Button', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',

    'css!controls/messages/Button.css'

], function (QUI, QUIControl, QUILocale) {
    "use strict";

    const lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/messages/Button',

        Binds: [
            'open',
            'close',
            'toggle',
            'refresh',
            'clear',
            '$customMessageHandling',
            '$closeMessage',
            '$destroyMessage'
        ],

        options: {
            messageDelay: 4000
        },

        initialize: function (options) {
            this.parent(options);

            this.$open = false;
            this.$isOpening = false;
            this.$rendering = false;

            this.$Elm = null;
            this.$MessageHandler = null;
            this.$Batch = null;

            this.$MessageBox = null;
            this.$Messages = null;
            this.$Header = null;
        },

        create: function () {
            // button
            this.$Elm = new Element('button', {
                'class'     : 'qui-contextmenu-baritem quiqqer-message-handler-button',
                'data-qui'  : 'controls/messages/Button',
                'data-quiid': this.getId(),
                html        : '<span class="fa fa-comments"></span>',
                events      : {
                    click: this.toggle
                }
            });

            this.$Elm.addEvent('click', this.toggle);

            this.$Batch = new Element('span', {
                'class': 'quiqqer-message-handler-batch',
                html   : 0,
                styles : {
                    display: 'none',
                    opacity: 0
                }
            }).inject(this.$Elm);

            QUI.getMessageHandler().then((MH) => {
                MH.setAttribute('customMessageHandling', this.$customMessageHandling);

                MH.addEvents({
                    onClearNewMessages: this.refresh,
                    onClear           : this.refresh
                });

                this.$MessageHandler = MH;
                this.refresh();
                this.$Elm.set('disabled', false);
            });

            // create message box
            this.$MessageBox = new Element('div', {
                'class' : 'quiqqer-message-handler',
                tabIndex: -1,
                styles  : {
                    display: 'none'
                },
                events  : {
                    blur: () => {
                        this.close();
                    }
                }
            }).inject(document.body);

            this.$Header = new Element('header', {
                'class': 'quiqqer-message-handler-header',
                'html' : '' +
                         '<span class="quiqqer-message-handler-header-title">' +
                         '   ' + QUILocale.get(lg, 'message.handler.title') +
                         '</span>' +
                         '' +
                         '<button name="clear-notifications">' +
                         '   <span class="fa fa-trash"></span>' +
                         '   <span>' + QUILocale.get(lg, 'message.handler.clear') + '</span>' +
                         '</button>'
            }).inject(this.$MessageBox);

            this.$Messages = new Element('div', {
                'class': 'quiqqer-message-handler-messages'
            }).inject(this.$MessageBox);

            new Element('div', {
                'class': 'quiqqer-message-handler-close',
                html   : '<span class="fa fa-close"></span>',
                events : {
                    click: this.close
                }
            }).inject(this.$MessageBox);

            // events
            this.$Header.getElement('[name="clear-notifications"]').addEvent('click', this.clear);

            return this.$Elm;
        },

        /**
         * refresh the button display - batch
         */
        refresh: function () {
            if (this.$open && !this.$isOpening) {
                this.$Batch.set('html', 1);

                (function () {
                    moofx(this.$Batch).animate({
                        opacity: 0
                    }, {
                        callback: () => {
                            this.$Batch.setStyle('display', 'none');
                        }
                    });
                }.bind(this)).delay(2000);
            } else {
                this.$Batch.set('html', this.$MessageHandler.getNewMessages());
            }

            if (!this.$MessageHandler.getNewMessages()) {
                if (parseFloat(this.$Batch.getStyle('opacity')) !== 0) {
                    moofx(this.$Batch).animate({
                        opacity: 0
                    }, {
                        callback: () => {
                            this.$Batch.setStyle('display', 'none');
                        }
                    });
                }
            } else {
                if (this.$Batch.getStyle('opacity') === 0) {
                    this.$Batch.setStyle('display', null);

                    moofx(this.$Batch).animate({
                        opacity: 1
                    });
                }
            }

            this.$renderMessages();
        },

        /**
         * render the messages in the notification panel
         */
        $renderMessages: function () {
            if (!this.$open) {
                return;
            }

            if (this.$isOpening) {
                return;
            }

            if (this.$rendering) {
                return;
            }

            this.$rendering = true;
            this.$MessageHandler.clearNewMessages();

            const messages = this.$MessageHandler.getMessages();

            this.$Messages.set('html', '');

            messages.sort(function (a, b) {
                a = new Date(a.getAttribute('time'));
                b = new Date(b.getAttribute('time'));

                return a > b ? -1 : a < b ? 1 : 0;
            });

            for (let i = 0, len = messages.length; i < len; i++) {
                this.$createMessageNode(messages[i]).inject(this.$Messages);
            }

            if (messages.length) {
                this.$Header.getElement('[name="clear-notifications"]').setStyle('display', null);
            } else {
                this.$Header.getElement('[name="clear-notifications"]').setStyle('display', 'none');

                // no messages
                const Container = new Element('div', {
                    styles: {
                        alignItems    : 'center',
                        display       : 'flex',
                        flexDirection : 'column',
                        justifyContent: 'center',
                        height        : '100%'
                    }
                }).inject(this.$Messages);

                new Element('img', {
                    src   : URL_OPT_DIR + 'quiqqer/quiqqer/bin/QUI/controls/messages/messages-empty.svg',
                    styles: {
                        opacity: 0.75,
                        width  : 160
                    }
                }).inject(Container);

                new Element('div', {
                    html  : QUILocale.get(lg, 'message.handler.no.messages'),
                    styles: {
                        color     : '#cbcbcb',
                        paddingTop: '1rem'
                    }
                }).inject(Container);
            }

            this.$rendering = false;

        },

        /**
         * Clears all notifications
         */
        clear: function () {
            this.$MessageHandler.clear();
            this.close();
        },

        //region display api

        /**
         * open or close the message handler
         */
        toggle: function () {
            if (this.$open) {
                return this.close();
            }

            return this.open();
        },

        /**
         * opens the message handler
         */
        open: function () {
            this.$open = true;
            this.$isOpening = true;

            this.fireEvent('open', [this]);

            const Node = this.$MessageBox;

            Node.setStyles({
                opacity: 0,
                top    : 50
            });

            Node.setStyle('display', null);
            Node.inject(document.body);

            this.refresh();

            moofx(Node).animate({
                opacity: 1,
                top    : 60
            }, {
                duration: 200,
                callback: () => {
                    this.$isOpening = false;
                    this.$MessageBox.focus();
                    this.$renderMessages();
                }
            });
        },

        /**
         * close the message handler
         */
        close: function () {
            this.$open = false;

            if (!this.$MessageBox) {
                return;
            }

            moofx(this.$MessageBox).animate({
                opacity: 0,
                top    : 50
            }, {
                duration: 200,
                callback: () => {
                    this.$MessageBox.setStyle('display', 'none');
                    this.fireEvent('close', [this]);
                }
            });
        },

        //endregion

        /**
         * Create the DOMNode Element of a message
         *
         * @param Message
         * @return {Element}
         */
        $createMessageNode: function (Message) {
            let icon = '';
            let messageType = '';

            switch (Message.getType()) {
                case 'qui/controls/messages/Attention':
                    icon = 'fa fa-info-circle';
                    messageType = 'quiqqer-message-attention';
                    break;

                case 'qui/controls/messages/Success':
                    icon = 'fa fa-check-circle';
                    messageType = 'quiqqer-message-success';
                    break;

                case 'qui/controls/messages/Error':
                    icon = 'fa fa-exclamation-circle';
                    messageType = 'quiqqer-message-error';
                    break;

                case 'qui/controls/messages/Information':
                    icon = 'fa fa-info-circle';
                    messageType = 'quiqqer-message-information';
                    break;

                case 'qui/controls/messages/Loading':
                    icon = 'fa fa-circle-notch fa-spin';
                    messageType = 'quiqqer-message-loading';
                    break;
            }

            const Node = new Element('div', {
                'class'      : 'quiqqer-message',
                'data-msg-id': Message.getId(),
                html         : '' +
                               '<span class="quiqqer">' +
                               '   <span class="' + icon + '"></span>' +
                               '</span>' +
                               '<span class="quiqqer-message-text">' + Message.getAttribute('message') + '</span>' +
                               '<span class="quiqqer-message-date">' +
                               '   <span class="fa fa-clock-o"></span>' +
                               '   ' + this.$getMessageDisplayTime(Message) +
                               '</span>' +
                               '<div class="quiqqer-message-close"><span class="fa fa-close"></span></div>'
            });

            if (messageType !== '') {
                Node.addClass(messageType);
            }

            Node.getElement('.quiqqer-message-close').addEvent('click', this.$destroyMessage);
            Node.setStyle('zIndex', QUI.Windows.$getmaxWindowZIndex() + 1);

            return Node;
        },

        /**
         * returns the time of a message to repress
         *
         * @param {Object} Message
         * @return {string}
         */
        $getMessageDisplayTime: function (Message) {
            const Time = Message.getAttribute('time');

            const time = ('0' + Time.getDate()).slice(-2) + '.' +
                         ('0' + (Time.getMonth() + 1)).slice(-2) + '.' +
                         Time.getFullYear();

            const hours = ('0' + Time.getHours()).slice(-2);
            const minutes = ('0' + Time.getMinutes()).slice(-2);

            return time + ' ' + hours + ':' + minutes;
        },

        /**
         * custom message handling
         *
         * @param {Object} Message
         */
        $customMessageHandling: function (Message) {
            if (this.$open) {
                this.$MessageHandler.$newMessages++;
                this.$MessageHandler.save();
                this.refresh();

                return;
            }

            this.$showMessage(
                this.$createMessageNode(Message)
            ).catch(function (err) {
                console.error(err);
            });

            this.$MessageHandler.$newMessages++;
            this.$MessageHandler.save();
            this.refresh();
        },

        /**
         * Show the message
         *
         * @param Node
         * @return {Promise}
         */
        $showMessage: function (Node) {
            const self = this;
            const messages = document.getElements('.quiqqer-message');

            if (messages.length) {
                return this.$closeMessage(messages).then(function () {
                    return self.$showMessage(Node);
                });
            }

            if (this.$open) {
                return Promise.resolve();
            }

            Node.setStyles({
                opacity: 0,
                top    : 50
            });

            Node.inject(document.body);

            return new Promise(function (resolve) {
                moofx(Node).animate({
                    opacity: 1,
                    top    : 60
                }, {
                    duration: 200,
                    callback: function () {
                        setTimeout(function () {
                            self.$closeMessage(Node).catch(function (err) {
                                console.error(err);
                            });
                        }, self.getAttribute('messageDelay'));

                        resolve();
                    }
                });
            });
        },

        /**
         * Close the message and destroy it
         * If the message is in the notifications, the message will be destroyed
         *
         * @param event
         */
        $destroyMessage: function (event) {
            const self = this;
            let Target = event.target;

            event.stop();

            if (!Target.hasClass('.quiqqer-message')) {
                Target = Target.getParent('.quiqqer-message');
            }

            const msgId    = Target.get('data-msg-id'),
                  messages = this.$MessageHandler.getMessages();

            let Message = messages.filter(function (msg) {
                return msg.getId() === msgId;
            });

            if (Message.length) {
                Message[0].destroy();
            }

            moofx(Target).animate({
                height : 0,
                margin : 0,
                padding: 0,
                opacity: 0
            }, {
                duration: 200,
                callback: function () {
                    Target.destroy();

                    if (!self.getElm().getElement('.quiqqer-message')) {
                        self.refresh();
                    }
                }
            });
        },

        /**
         * Close the message
         *
         * @param Node
         * @return {Promise}
         */
        $closeMessage: function (Node) {
            if (!Node) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                moofx(Node).animate({
                    opacity: 0,
                    top    : 50
                }, {
                    duration: 200,
                    callback: function () {
                        Node.destroy();
                        resolve();
                    }
                });
            });
        }
    });
});