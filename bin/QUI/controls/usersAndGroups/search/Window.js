/**
 * @module controls/usersAndGroups/search/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require controls/users/search/Search
 * @require Locale
 */
define('controls/usersAndGroups/search/Window', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'controls/usersAndGroups/search/Search',
    'Locale'

], function (QUI, QUIConfirm, Search, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIConfirm,
        Type   : 'controls/usersAndGroups/search/Window',

        Binds: [
            'submit',
            '$onOpen',
            '$onResize'
        ],

        options: {
            maxHeight     : 600,
            maxWidth      : 800,
            autoclose     : true,
            searchSettings: false,
            search        : false
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get('quiqqer/quiqqer', 'control.usersgroups.window.search.title'),
                icon : 'fa fa-search'
            });

            this.parent(options);

            this.$Search = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onResize: this.$onResize
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            var Content = this.getContent();

            Content.set('html', '');

            this.$Search = new Search({
                search: this.getAttribute('search'),
                events: {
                    onDblClick   : this.submit,
                    onSearchBegin: function () {
                        this.Loader.show();
                    }.bind(this),
                    onSearchEnd  : function () {
                        this.Loader.hide();
                    }.bind(this)
                }
            }).inject(Content);

            this.$Search.resize();
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (this.$Search) {
                this.$Search.resize();
            }
        },

        /**
         * Submit the window
         */
        submit: function () {
            var data = this.$Search.getSelectedData();

            if (!data.length) {
                return;
            }

            this.fireEvent('submit', [this, data]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});