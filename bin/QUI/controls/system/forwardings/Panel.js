/**
 * @module controls/system/forwardings/Panel
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require controls/grid/Grid
 * @require Ajax
 * @require Locale
 */
define('controls/system/forwardings/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'controls/grid/Grid',
    'Ajax',
    'Locale'

], function (QUI, QUIPanel, Grid, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/system/forwardings/Panel',

        Binds: [
            '$onCreate',
            '$onResize',
            'refresh',
            'openCreateForwarding',
            'openUpdateForwarding',
            'openDeleteForwarding'
        ],

        options: {
            title: QUILocale.get(lg, 'system.forwardings.panel.title'),
            icon : 'fa fa-external-link'
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'system.forwardings.panel.title'),
                icon : 'fa fa-external-link'
            });

            this.parent(options);

            this.$Grid = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {

            // Buttons
            this.addButton({
                name     : 'add',
                text     : QUILocale.get('quiqqer/quiqqer', 'add'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.openCreateForwarding
                }
            });

            this.addButton({
                type: 'seperator'
            });

            this.addButton({
                name     : 'edit',
                text     : QUILocale.get('quiqqer/quiqqer', 'edit'),
                textimage: 'fa fa-edit',
                disabled : true,
                events   : {
                    onClick: this.openUpdateForwarding
                }
            });

            this.addButton({
                name     : 'remove',
                text     : QUILocale.get('quiqqer/quiqqer', 'remove'),
                textimage: 'fa fa-trash-o',
                disabled : true,
                events   : {
                    onClick: this.openRemoveForwarding
                }
            });

            // Grid
            var Container = new Element('div').inject(
                this.getContent()
            );

            this.$Grid = new Grid(Container, {
                pagination : true,
                columnModel: [{
                    header   : 'Von',
                    dataIndex: 'from',
                    dataType : 'string',
                    width    : 300
                }, {
                    header   : 'Nach',
                    dataIndex: 'to',
                    dataType : 'string',
                    width    : 300
                }, {
                    header   : 'Error-Code',
                    dataIndex: 'errorCode',
                    dataType : 'number',
                    width    : 100
                }],

                onrefresh: this.refresh
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.Loader.show();
            this.refresh().then(function () {
                this.Loader.hide();
            }.bind(this));
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid) {
                return;
            }

            var Body = this.getContent();

            if (!Body) {
                return;
            }

            var size = Body.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        },

        /**
         * Refresh the table
         */
        refresh: function () {
            return new Promise(function (resolve) {
                QUIAjax.get('ajax_system_forwardings_getList', function (result) {
                    console.log(result);

                    this.$Grid.setData({
                        data: result
                    });

                    resolve();
                }.bind(this));
            }.bind(this));
        },

        /**
         * Open the add dialog
         */
        openCreateForwarding: function () {

        },

        /**
         * Open the edit dialog
         */
        openUpdateForwarding: function () {

        },

        /**
         * Open the remove dialog
         */
        openRemoveForwarding: function () {

        }
    });
});
