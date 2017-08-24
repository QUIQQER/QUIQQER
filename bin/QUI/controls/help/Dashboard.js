/**
 * The Quiqqer Dashboard
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/help/Dashboard
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require controls/projects/Manager
 * @require utils/Panels
 * @require Ajax
 * @require css!controls/help/Dashboard.css
 */
define('controls/help/Dashboard', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'controls/projects/Manager',
    'utils/Panels',
    'Ajax',

    'css!controls/help/Dashboard.css'

], function (QUI, QUIPanel, ProjectManager, PanelUtils, QUIAjax) {
    "use strict";

    /**
     * @class controls/help/Dashboard
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/help/Dashboard',

        Binds: [
            '$onCreate',
            '$openSitePanel',
            'refreshLastMessages'
        ],

        options: {
            icon             : URL_BIN_DIR + '16x16/quiqqer.png',
            title            : 'QUIQQER Dashboard',
            displayNoTaskText: true
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate
            });
        },

        /**
         * Create the project panel body
         */
        $onCreate: function () {
            require(['text!controls/help/Dashboard.html'], function (result) {
                var self = this;

                this.getContent().set('html', result);

                this.refreshLastEditedSites();
                this.refreshLastMessages();

                QUI.getMessageHandler().then(function (MH) {

                    MH.addEvents({
                        onClear           : self.refreshLastMessages,
                        onClearNewMessages: self.refreshLastMessages,
                        onAdd             : self.refreshLastMessages,
                        onMessageDestroy  : self.refreshLastMessages
                    });

                });

            }.bind(this));
        },

        /**
         * Refresh the last messages
         */
        refreshLastMessages: function () {
            var Content = this.getContent();

            // 10 messages
            QUI.getMessageHandler().then(function (MH) {

                var start, end;

                var messages  = MH.getMessages(),
                    Container = Content.getElement(
                        '.quiqqer-dashboard-last-messages-result'
                    );

                if (!Container) {
                    return;
                }

                Container.set('html', '');

                // #locale
                if (!messages.length) {
                    Container.set('html', '<p>Keine Nachrichten vorhanden</p>');
                    return;
                }

                start = messages.length - 1;
                end   = start - 9;

                if (end < 0) {
                    end = 0;
                }

                if (start < 0) {
                    start = 0;
                }

                for (; start >= end; start--) {
                    messages[start].createMessageElement().inject(Container);
                }

            });
        },

        /**
         * Refresh the last edited sites
         */
        refreshLastEditedSites: function () {
            var self    = this,
                Content = this.getContent();

            QUIAjax.get([
                'ajax_search_lastEditSites'
            ], function (lastEdited) {

                var i, len, text, Entry;

                var Container = Content.getElement(
                    '.quiqqer-dashboard-last-edit-result'
                );

                if (!Container) {
                    return;
                }

                Container.set('html', '');

                for (i = 0, len = lastEdited.length; i < len; i++) {

                    Entry = lastEdited[i];

                    text = Entry.name + ' (' + Entry.project + ',' + Entry.lang + ')' +
                        ' - ' + Entry.e_date;

                    new Element('div', {
                        'class'       : 'quiqqer-dashboard-last-edit-result-entry smooth',
                        'data-project': Entry.project,
                        'data-lang'   : Entry.lang,
                        'data-id'     : Entry.id,
                        events        : {
                            click: self.$openSitePanel
                        },
                        html          : '<span class="fa fa-file-o"></span>' + text,
                        title         : text
                    }).inject(Container);
                }

            });
        },

        /**
         * Open Site Panel
         * @param {DOMEvent} event
         */
        $openSitePanel: function (event) {
            if (typeOf(event) === 'domevent') {
                event.stop();
            }

            var Target = event.target;

            if (!Target.hasClass('quiqqer-dashboard-last-edit-result-entry')) {
                Target = Target.getParent('.quiqqer-dashboard-last-edit-result-entry');
            }

            var project = Target.get('data-project');
            var lang    = Target.get('data-lang');
            var id      = Target.get('data-id');

            PanelUtils.openSitePanel(project, lang, id);
        }
    });
});
