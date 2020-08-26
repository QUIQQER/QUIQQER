/**
 * @module controls/editors/toolbars/Window
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require Ajax
 * @require Locale
 * @require controls/grid/Grid
 */
define('controls/editors/toolbars/Window', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale',
    'controls/grid/Grid'

], function (QUI, QUIConfirm, QUIAjax, QUILocale, Grid) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/editors/toolbars/Window',

        Binds: [
            '$onOpen',
            '$onResize',
            'refresh',
            'submit'
        ],

        options: {
            maxHeight: 500,
            maxWidth : 300
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                title: '',
                icon : 'fa fa-font'
            });

            this.$Grid = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onResize: this.$onResize
            });
        },

        /**
         * refresh
         *
         * @return {Promise}
         */
        refresh: function () {
            this.Loader.show();

            return new Promise(function (resolve) {
                QUIAjax.get('ajax_editor_get_toolbars', function (list) {
                    var data = list.map(function (toolbar) {
                        return {
                            toolbar: toolbar
                        };
                    });

                    this.$Grid.setData({
                        data: data
                    });

                    this.Loader.hide();

                    resolve();
                }.bind(this));
            }.bind(this));
        },

        /**
         * Submit
         */
        submit: function () {
            if (!this.$Grid) {
                return;
            }

            var toolbars = this.$Grid.getSelectedData().map(function (entry) {
                return entry.toolbar;
            });

            if (!toolbars.length) {
                return;
            }

            this.fireEvent('submit', [this, toolbars]);
            this.close();
        },

        /**
         * event on open
         */
        $onOpen: function () {
            this.getContent().set('html', '');
            this.Loader.show();

            var Container = new Element('div', {
                styles: {
                    height: '100%',
                    width : '100%'
                }
            }).inject(this.getContent());

            this.$Grid = new Grid(Container, {
                columnModel: [{
                    header   : QUILocale.get(
                        'quiqqer/quiqqer',
                        'editors.settings.table.toolbar.name'
                    ),
                    dataIndex: 'toolbar',
                    dataType : 'string',
                    width    : 200
                }]
            });


            // Events
            this.$Grid.addEvents({
                onDblClick: this.submit,
                onRefresh : this.refresh
            });

            this.$Grid.refresh();
            this.$onResize();
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid) {
                return;
            }

            var size = this.getContent().getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        }
    });
});
