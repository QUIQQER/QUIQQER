/**
 * @module controls/editors/toolbars/Select
 */
define('controls/editors/toolbars/Select', [

    'qui/QUI',
    'qui/controls/elements/Select',
    'Locale',
    'Ajax'

], function (QUIControl, QUIElementSelect, QUILocale, QUIAjax) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    /**
     * @class controls/editors/toolbars/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIElementSelect,
        Type   : 'controls/editors/toolbars/Select',

        Binds: [
            '$onSearchButtonClick',
            'toolbarSearch'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('Search', this.toolbarSearch);
            this.setAttribute('icon', 'fa fa-font');
            this.setAttribute('child', 'controls/editors/toolbars/SelectItem');

            this.setAttribute(
                'placeholder',
                QUILocale.get(lg, 'control.editor.toolbar.select.search.field.placeholder')
            );

            this.addEvents({
                onSearchButtonClick: this.$onSearchButtonClick
            });
        },

        /**
         * Execute the search
         *
         * @param {String} value
         * @returns {Promise}
         */
        toolbarSearch: function (value) {
            return new Promise(function (resolve) {
                QUIAjax.get('ajax_editor_toolbar_search', function (result) {
                    var data = [];

                    for (var i = 0, len = result.length; i < len; i++) {
                        data.push({
                            id   : result[i],
                            title: result[i],
                            icon : 'fa fa-font'
                        });
                    }

                    resolve(data);
                }, {
                    search: value,
                    fields: false,
                    params: JSON.decode({
                        limit: 10
                    })
                });
            });
        },

        /**
         *
         * @param {Object} Btn
         */
        $onSearchButtonClick: function (Btn) {
            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

            require([
                'controls/editors/toolbar/Window'
            ], function (Window) {
                new Window({
                    autoclose: true,
                    multiple : this.getAttribute('multiple'),
                    events   : {
                        onSubmit: function (Win, userIds) {
                            for (var i = 0, len = userIds.length; i < len; i++) {
                                this.addItem(userIds[i].id);
                            }
                        }.bind(this)
                    }
                }).open();

                Btn.setAttribute('icon', 'fa fa-search');
            }.bind(this));
        }
    });
});
