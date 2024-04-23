/**
 * @module controls/messages/Button
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/messages/Button', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/messages/Loading',
    'classes/request/BulkUpload',
    'Locale',

    'css!controls/messages/Button.css'

], function (QUI, QUIControl, LoadingMessage, BulkUpload, QUILocale) {
    "use strict";

    const lg = 'quiqqer/quiqqer';
    const uploadMessages = {};
    const uploadNodes = [];

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/messages/Button',

        Binds: [
            'open',
            'close',
            'toggle',
            'refresh',
            'clear',
            'toggleFilter',
            'openFilter',
            'toggleFilter',
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
            this.$Filter = null;

            // message queue
            this.$queue = [];

            this.$filter = {
                information: true,
                success    : true,
                attention  : true,
                error      : true
            };
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
                         '<div name="filter-notifications">' +
                         '  <span class="fa fa-filter"></span>' +
                         '</div>' +
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
            this.$Header.getElement('[name="filter-notifications"]').addEvent('click', this.toggleFilter);

            QUI.addEvent('upload', (File) => {
                this.$addFileUpload(File);
            });

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

            let messages = this.$MessageHandler.getMessages();

            this.$Messages.set('html', '');

            messages.sort(function (a, b) {
                a = new Date(a.getAttribute('time'));
                b = new Date(b.getAttribute('time'));

                return a > b ? -1 : a < b ? 1 : 0;
            });

            messages = messages.filter((msg) => {
                switch (msg.getType()) {
                    case 'qui/controls/messages/Attention':
                        if (!this.$filter.attention) {
                            return false;
                        }
                        break;

                    case 'qui/controls/messages/Success':
                        if (!this.$filter.success) {
                            return false;
                        }
                        break;

                    case 'qui/controls/messages/Error':
                        if (!this.$filter.error) {
                            return false;
                        }
                        break;

                    case 'qui/controls/messages/Information':
                        if (!this.$filter.information) {
                            return false;
                        }
                        break;
                }

                return true;
            });

            let i, len;

            for (i = 0, len = messages.length; i < len; i++) {
                this.$createMessageNode(messages[i]).inject(this.$Messages);
            }

            for (i = 0, len = uploadNodes.length; i < len; i++) {
                uploadNodes[i].setStyles({
                    opacity : 1,
                    position: 'relative',
                    top     : null,
                    display : null
                });

                uploadNodes[i].inject(this.$Messages, 'top');
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
                    icon = 'fa fa-exclamation-circle';
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
                               '<div class="quiqqer-message-close">' +
                               '    <span class="fa fa-close"></span>' +
                               '</div>'
            });

            if (messageType === 'quiqqer-message-loading') {
                new Element('span', {
                    'class': 'fa fa-circle-o-notch fa-spin',
                    styles : {
                        float   : 'right',
                        color   : '#000',
                        fontSize: '16px',
                        padding : 0
                    }
                }).inject(Node.getElement('.quiqqer-message-date'));
            }

            if (Message.getElm().hasClass('quiqqer-message-loading--bulk')) {
                Node.addClass('quiqqer-message-loading--bulk');
            }

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
         * @param {HTMLElement} Node
         */
        $customMessageHandling: function (Message, Node) {
            if (Node) {
                this.$showMessageAtNode(Message, Node);
                return;
            }

            if (this.$open) {
                this.$MessageHandler.$newMessages++;
                this.$MessageHandler.save();
                this.refresh();

                return;
            }

            const MessageNode = this.$createMessageNode(Message);

            if (this.$queue.length) {
                this.$queue.push(MessageNode);
                this.$MessageHandler.$newMessages++;
                this.$MessageHandler.save();
                this.refresh();

                return;
            }

            this.$queue.push(MessageNode);
            this.$showMessage(MessageNode).catch(function (err) {
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

            let zIndex = QUI.Windows.$getmaxWindowZIndex() + 1;

            if (zIndex < 1000) {
                zIndex = 1000;
            }

            Node.setStyles({
                opacity : 0,
                position: 'absolute',
                top     : 50,
                zIndex  : zIndex
            });

            Node.inject(document.body);

            return new Promise((resolve) => {
                moofx(Node).animate({
                    opacity: 1,
                    top    : 60
                }, {
                    duration: 200,
                    callback: () => {
                        let messageDelay = self.getAttribute('messageDelay');

                        if (this.$queue.length >= 2) {
                            messageDelay = 1000;
                        }

                        if (Node.hasClass('quiqqer-message-loading--bulk')) {
                            return;
                        }

                        if (Node.hasClass('quiqqer-message-loading')) {
                            for (let i in uploadMessages) {
                                if (uploadMessages[i].getId() === Node.get('data-msg-id')) {
                                    return;
                                }
                            }

                            // file not found, so upload is already done
                            messageDelay = 50;
                            Node.getElement('.quiqqer-message-loading-progress-bar').setStyle('width', '100%');
                        }


                        setTimeout(() => {
                            self.$closeMessage(Node).then(() => {
                                let index = this.$queue.indexOf(Node);

                                if (index > -1) {
                                    this.$queue.splice(index, 1);
                                }

                                if (this.$queue.length) {
                                    return this.$showMessage(this.$queue[0]);
                                }
                            }).catch((err) => {
                                console.error(err);
                            });
                        }, messageDelay);

                        resolve();
                    }
                });
            });
        },

        $showMessageAtNode: function (Message, Parent) {
            const MessageNode = this.$createMessageNode(Message);
            let zIndex = QUI.Windows.$getmaxWindowZIndex() + 1;

            if (zIndex < 1000) {
                zIndex = 1000;
            }

            MessageNode.setStyles({
                opacity : 0,
                position: 'absolute',
                zIndex  : zIndex
            });

            MessageNode.inject(document.body);

            let pos = Parent.getPosition();
            let size = Parent.getSize();
            let startY = pos.y + size.y - 10;

            MessageNode.setStyles({
                left: pos.x,
                top : startY
            });

            const winSize = QUI.getWindowSize();
            const messageSize = MessageNode.getSize();

            if (messageSize.x + pos.x > winSize.x) {
                MessageNode.setStyle('left', winSize.x - messageSize.x - 10);
            }

            return new Promise((resolve) => {
                moofx(MessageNode).animate({
                    opacity: 1,
                    top    : startY + 10
                }, {
                    duration: 200,
                    callback: () => {
                        setTimeout(() => {
                            moofx(MessageNode).animate({
                                opacity: 0,
                                top    : startY
                            }, {
                                duration: 200,
                                callback: function () {
                                    MessageNode.destroy();
                                    resolve();
                                }
                            });
                        }, this.getAttribute('messageDelay'));

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
            if (!this.$MessageHandler) {
                return;
            }

            let Target = event.target;

            event.stop();

            if (!Target.hasClass('.quiqqer-message')) {
                Target = Target.getParent('.quiqqer-message');
            }

            // loading
            if (Target.hasClass('quiqqer-message-loading--bulk')) {
                moofx(Target).animate({
                    opacity: 0,
                    top    : 50
                }, {
                    duration: 200,
                    callback: () => {
                        uploadNodes.push(Target);
                        Target.setStyle('display', 'none');
                    }
                });

                return;
            }

            const msgId    = Target.get('data-msg-id'),
                  messages = this.$MessageHandler.getMessages();

            let Message = messages.filter(function (msg) {
                return msg.getId() === msgId;
            });

            if (Message.length) {
                this.$MessageHandler.$onMessageDestroy(Message[0]);
            }

            moofx(Target).animate({
                height : 0,
                margin : 0,
                padding: 0,
                opacity: 0
            }, {
                duration: 200,
                callback: () => {
                    Target.destroy();

                    if (!this.$MessageBox.getElement('.quiqqer-message')) {
                        this.refresh();
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
        },

        $addFileUpload: function (File) {
            if (File instanceof BulkUpload) {
                const progress = File.getProgress();

                const MessageInstance = new LoadingMessage({
                    percent: 0,
                    message: '<span class="quiqqer-message-loading-text">' +
                             QUILocale.get(lg, 'upload.bulk.message', {
                                 done : progress.done,
                                 total: progress.total
                             }) +
                             '</span>' +
                             '<span class="quiqqer-message-loading-progress">' +
                             '   <span class="quiqqer-message-loading-progress-bar"></span>' +
                             '</span>'
                });

                MessageInstance.getElm().addClass('quiqqer-message-loading--bulk');
                uploadMessages[File.getId()] = MessageInstance;

                File.addEvent('uploadPartEnd', function () {
                    const progress = File.getProgress();

                    const instanceId = uploadMessages[File.getId()].getId();
                    const MessageNode = document.body.getElement('[data-msg-id=' + instanceId + ']');

                    if (!MessageNode) {
                        return;
                    }

                    const Text = MessageNode.getElement('.quiqqer-message-loading-text');
                    const Bar = MessageNode.getElement('.quiqqer-message-loading-progress-bar');

                    Text.set('html', QUILocale.get(lg, 'upload.bulk.message', {
                        done : progress.done,
                        total: progress.total
                    }));

                    Bar.setStyle('width', progress.percent + '%');
                });

                File.addEvent('finish', () => {
                    if (typeof uploadMessages[File.getId()] === 'undefined') {
                        return;
                    }

                    const progress = File.getProgress();
                    const instanceId = uploadMessages[File.getId()].getId();
                    const MessageNode = document.body.getElement('[data-msg-id=' + instanceId + ']');

                    if (!MessageNode) {
                        return;
                    }

                    delete uploadMessages[File.getId()];

                    this.$closeMessage(MessageNode).then(() => {
                        require(['Projects'], (Projects) => {
                            if (!File.getAttribute('project')) {
                                return;
                            }

                            const Project = Projects.get(File.getAttribute('project'));
                            const Media = Project.getMedia();

                            Media.get(File.getAttribute('parentId')).then((Folder) => {
                                this.$MessageHandler.addSuccess(
                                    QUILocale.get(lg, 'upload.bulk.successful', {
                                        count : progress.total,
                                        folder: Folder.getAttribute('name')
                                    })
                                );
                            });
                        });

                        let inUploadNodes = uploadNodes.indexOf(MessageNode);

                        if (inUploadNodes > -1) {
                            uploadNodes.splice(inUploadNodes, 1);
                            MessageNode.destroy();
                        }

                        let index = this.$queue.indexOf(MessageNode);

                        if (index > -1) {
                            this.$queue.splice(index, 1);
                        }

                        if (this.$queue.length) {
                            return this.$showMessage(this.$queue[0]);
                        }
                    });
                });

                this.$customMessageHandling(MessageInstance, null);

                return;
            }


            const fileId = File.getId();

            if (typeof uploadMessages[fileId] !== 'undefined') {
                return;
            }

            const MessageInstance = new LoadingMessage({
                percent: 0,
                message: '<span>Upload ' + File.$File.name + '</span>' +
                         '<span class="quiqqer-message-loading-progress">' +
                         '  <span class="quiqqer-message-loading-progress-bar"></span>' +
                         '</span>'
            });

            uploadMessages[fileId] = MessageInstance;

            File.addEvent('onRefresh', function (File, percent) {
                if (typeof uploadMessages[File.getId()] === 'undefined') {
                    return;
                }

                const instanceId = uploadMessages[File.getId()].getId();
                const MessageNode = document.body.getElement('[data-msg-id=' + instanceId + ']');

                if (!MessageNode) {
                    return;
                }

                MessageNode.getElement('.quiqqer-message-loading-progress-bar')
                           .setStyle('width', percent + '%');
            });

            File.addEvent('onComplete', (File) => {
                if (typeof uploadMessages[File.getId()] === 'undefined') {
                    this.$MessageHandler.addSuccess(
                        QUILocale.get(lg, 'upload.successful', {
                            file: File.$File.name
                        })
                    );

                    return;
                }

                const instanceId = uploadMessages[File.getId()].getId();
                const MessageNode = document.body.getElement('[data-msg-id=' + instanceId + ']');

                delete uploadMessages[File.getId()];

                if (MessageNode) {
                    this.$closeMessage(MessageNode).then(() => {
                        this.$MessageHandler.addSuccess(
                            QUILocale.get(lg, 'upload.successful', {
                                file: File.$File.name
                            })
                        );

                        let index = this.$queue.indexOf(MessageNode);

                        if (index > -1) {
                            this.$queue.splice(index, 1);
                        }

                        if (this.$queue.length) {
                            return this.$showMessage(this.$queue[0]);
                        }
                    }).catch((err) => {
                        console.error(err);
                    });
                } else {
                    this.$MessageHandler.addSuccess(
                        QUILocale.get(lg, 'upload.successful', {
                            file: File.$File.name
                        })
                    );
                }
            });

            File.addEvent('onError', (File) => {
                if (typeof uploadMessages[File.getId()] === 'undefined') {
                    return;
                }

                delete uploadMessages[File.getId()];
            });

            this.$customMessageHandling(MessageInstance, null);
        },

        toggleFilter: function (e) {
            if (typeOf(e) === 'domevent') {
                e.stop();
            }

            if (!this.$Filter) {
                this.openFilter();
            } else {
                this.closeFilter();
            }
        },

        openFilter: function () {
            if (this.$Filter) {
                return;
            }

            this.$Messages.setStyle('height', 'calc(100% - 130px)');

            this.$Filter = new Element('div', {
                'class': 'quiqqer-messages-filter',
                html   : '<span class="fa fa-info-circle information" data-filter="information"></span>' +
                         '<span class="fa fa-check-circle success" data-filter="success"></span>' +
                         '<span class="fa fa-exclamation-circle attention" data-filter="attention"></span>' +
                         '<span class="fa fa-exclamation-circle error" data-filter="error"></span>'
            }).inject(this.$Messages, 'before');

            this.$Filter.getElements('.fa').addEvent('click', (event) => {
                const Target = event.target;
                const filter = Target.get('data-filter');

                if (typeof this.$filter[filter] === 'undefined') {
                    return;
                }

                Target.classList.toggle('active');

                this.$filter[filter] = Target.hasClass('active');
                this.refresh();
            });

            this.$Filter.getElements('.fa').forEach((Node) => {
                const filter = Node.get('data-filter');

                if (this.$filter[filter]) {
                    Node.addClass('active');
                }
            });
        },

        closeFilter: function () {
            this.$Filter.destroy();
            this.$Filter = null;

            this.$Messages.setStyle('height', 'calc(100% - 100px)');
        }
    });
});