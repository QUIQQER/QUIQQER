/**
 * @module controls/users/search/Window
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/users/search/Window', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'controls/users/search/Search',
    'Locale'

], function (QUI, QUIConfirm, UserSearch, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIConfirm,
        Type   : 'controls/users/search/Window',

        Binds: [
            'submit',
            '$onOpen',
            '$onResize'
        ],

        options: {
            maxWidth      : 1200,
            maxHeight     : 800,
            editable      : false,
            autoclose     : true,
            searchSettings: false,
            search        : true
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get('quiqqer/quiqqer', 'users.panel.search.title'),
                icon : 'fa fa-users'
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
            Content.setStyle('padding', 0);

            this.$Search = new UserSearch({
                search        : this.getAttribute('search'),
                searchSettings: this.getAttribute('searchSettings'),
                editable      : this.getAttribute('editable'),
                events        : {
                    onDblClick: this.submit
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
