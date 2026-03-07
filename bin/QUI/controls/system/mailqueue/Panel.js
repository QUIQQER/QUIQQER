define('controls/system/mailqueue/Panel', [
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'utils/Panels',
    'controls/system/mailqueue/Mail',
    'Ajax',
    'Locale'
], function (QUIPanel, QUIConfirm, Grid, PanelUtils, MailPanel, QUIAjax, QUILocale) {
    'use strict';

    const lg = 'quiqqer/core';

    return new Class({
        Extends: QUIPanel,
        Type: 'controls/system/mailqueue/Panel',

        Binds: [
            '$onCreate',
            '$onInject',
            '$onResize',
            '$gridRefresh',
            '$refreshGridButtons',
            '$onGridDblClick',
            '$onSearchKeyUp',
            '$onSearchInput',
            '$onDeleteClick'
        ],

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'mailqueue.panel.title'),
                icon: 'fa fa-envelope-open-o'
            });

            this.parent(options);

            this.$Container = null;
            this.$GridContainer = null;
            this.$Grid = null;
            this.$SearchInput = null;
            this.$SearchDelay = null;
            this.$DeleteButton = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        $onCreate: function () {
            this.$Container = new Element('div', {
                styles: {
                    width: '100%',
                    height: '100%'
                }
            }).inject(this.getBody());

            this.$SearchInput = new Element('input', {
                type: 'search',
                placeholder: QUILocale.get(lg, 'mailqueue.filter.search.placeholder'),
                styles: {
                    'float': 'right',
                    margin: '10px 0 0 0',
                    width: 260
                },
                events: {
                    keyup: this.$onSearchKeyUp,
                    input: this.$onSearchInput,
                    search: this.$onSearchKeyUp
                }
            });

            this.addButton({
                name: 'search',
                textimage: 'fa fa-search',
                styles: {
                    'float': 'right'
                },
                events: {
                    onClick: () => {
                        this.$Grid.setAttribute('page', 1);
                        this.$gridRefresh();
                    }
                }
            });

            this.addButton(this.$SearchInput);

            this.$GridContainer = new Element('div', {
                styles: {
                    width: '100%',
                    height: '100%'
                }
            }).inject(this.$Container);

            this.$Grid = new Grid(this.$GridContainer, {
                columnModel: [{
                    header: 'ID',
                    dataIndex: 'id',
                    dataType: 'number',
                    width: 80
                }, {
                    header: QUILocale.get(lg, 'mailqueue.grid.header.lastsend'),
                    dataIndex: 'lastsend_display',
                    dataType: 'string',
                    width: 180,
                    sortable: false
                }, {
                    header: QUILocale.get(lg, 'mailqueue.grid.header.status'),
                    dataIndex: 'status_label',
                    dataType: 'string',
                    width: 130,
                    sortable: false
                }, {
                    header: QUILocale.get(lg, 'mailqueue.grid.header.retry'),
                    dataIndex: 'retry',
                    dataType: 'number',
                    width: 90
                }, {
                    header: QUILocale.get(lg, 'mailqueue.grid.header.subject'),
                    dataIndex: 'subject',
                    dataType: 'string',
                    width: 280
                }, {
                    header: QUILocale.get(lg, 'mailqueue.grid.header.to'),
                    dataIndex: 'mail_to_display',
                    dataType: 'string',
                    width: 360,
                    sortable: false
                }],
                multipleSelection: true,
                pagination: true,
                serverSort: true,
                sortOn: 'lastsend',
                sortBy: 'DESC',
                perPage: 20,
                buttons: [{
                    name: 'delete',
                    text: QUILocale.get(lg, 'mailqueue.delete.button'),
                    textimage: 'fa fa-trash',
                    disabled: true,
                    position: 'right',
                    events: {
                        onClick: this.$onDeleteClick
                    }
                }]
            });

            this.$DeleteButton = this.$Grid.getButtons().filter((Button) => {
                return Button.getAttribute('name') === 'delete';
            })[0] || null;

            this.$Grid.addEvents({
                onRefresh: this.$gridRefresh,
                onClick: this.$refreshGridButtons,
                onDblClick: this.$onGridDblClick
            });

            this.$gridRefresh();
        },

        $onInject: function () {
            this.$gridRefresh();
        },

        $gridRefresh: function () {
            if (!this.$Grid) {
                return;
            }

            this.Loader.show();

            QUIAjax.get('ajax_system_mailqueue_list', (result) => {
                if (!this.$Grid) {
                    this.Loader.hide();
                    return;
                }

                this.$Grid.setData(result);
                this.$refreshGridButtons();
                this.Loader.hide();
            }, {
                params: JSON.encode({
                    perPage: this.$Grid.getAttribute('perPage') || 20,
                    page: this.$Grid.getAttribute('page') || 1,
                    sortOn: this.$Grid.getAttribute('sortOn') || 'lastsend',
                    sortBy: this.$Grid.getAttribute('sortBy') || 'DESC',
                    search: this.$SearchInput ? this.$SearchInput.value : ''
                })
            });
        },

        $refreshGridButtons: function () {
            if (!this.$Grid || !this.$DeleteButton) {
                return;
            }

            const selected = this.$Grid.getSelectedData();

            if (!selected || !selected.length) {
                this.$DeleteButton.disable();
                return;
            }

            this.$DeleteButton.enable();
        },

        $onSearchKeyUp: function (event) {
            if (event && event.key === 'enter') {
                this.$Grid.setAttribute('page', 1);
                this.$gridRefresh();
                return;
            }

            if (this.$SearchDelay) {
                clearTimeout(this.$SearchDelay);
            }

            this.$SearchDelay = (() => {
                this.$Grid.setAttribute('page', 1);
                this.$gridRefresh();
            }).delay(350);
        },

        $onSearchInput: function () {
            if (!this.$SearchInput || this.$SearchInput.value !== '') {
                return;
            }

            if (this.$SearchDelay) {
                clearTimeout(this.$SearchDelay);
                this.$SearchDelay = null;
            }

            this.$Grid.setAttribute('page', 1);
            this.$gridRefresh();
        },

        $onDeleteClick: function () {
            if (!this.$Grid) {
                return;
            }

            const selected = this.$Grid.getSelectedData();

            if (!selected || !selected.length) {
                return;
            }

            const ids = [];
            const information = ['<ul style="margin:0;padding-left:18px;">'];

            selected.forEach((entry) => {
                const id = String(entry.id || '').trim();

                if (!id) {
                    return;
                }

                ids.push(id);

                let subject = String(entry.subject || '-').trim();

                if (!subject) {
                    subject = '-';
                }

                subject = subject
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                information.push('<li><strong>' + id + '</strong>: ' + subject + '</li>');
            });

            information.push('</ul>');

            if (!ids.length) {
                return;
            }

            new QUIConfirm({
                icon: 'fa fa-trash',
                texticon: 'fa fa-trash',
                title: QUILocale.get(lg, 'mailqueue.delete.dialog.title'),
                text: QUILocale.get(lg, 'mailqueue.delete.dialog.text'),
                information: information.join(''),
                autoclose: false,
                maxWidth: 650,
                maxHeight: 500,
                ok_button: {
                    text: QUILocale.get(lg, 'mailqueue.delete.button'),
                    textimage: 'fa fa-trash'
                },
                events: {
                    onSubmit: (Win) => {
                        Win.Loader.show();

                        QUIAjax.get('ajax_system_mailqueue_delete', () => {
                            Win.close();
                            this.$Grid.setAttribute('page', 1);
                            this.$gridRefresh();
                        }, {
                            id: JSON.encode(ids)
                        });
                    }
                }
            }).open();
        },

        $onGridDblClick: function () {
            const selected = this.$Grid ? this.$Grid.getSelectedData() : [];

            if (!selected || !selected.length || !selected[0].id) {
                return;
            }

            const row = selected[0];
            const Panel = new MailPanel({
                mailId: row.id,
                '#id': 'mailqueue-mail-' + row.id
            });

            PanelUtils.openPanelInTasks(Panel);
        },

        $onResize: function () {
            if (!this.$Grid || !this.$Container || !this.$GridContainer) {
                return;
            }

            const content = this.getContent();
            const size = content.getSize();

            const paddingLeft = parseInt(content.getStyle('padding-left'), 10) || 0;
            const paddingRight = parseInt(content.getStyle('padding-right'), 10) || 0;
            const paddingTop = parseInt(content.getStyle('padding-top'), 10) || 0;
            const paddingBottom = parseInt(content.getStyle('padding-bottom'), 10) || 0;

            const width = Math.max(0, size.x - paddingLeft - paddingRight);
            const height = Math.max(0, size.y - paddingTop - paddingBottom);

            this.$Container.setStyle('width', width);
            this.$Container.setStyle('height', height);
            this.$GridContainer.setStyle('width', width);
            this.$GridContainer.setStyle('height', height);
            this.$Grid.setWidth(width);
            this.$Grid.setHeight(height);
        }
    });
});
