define('controls/icons/Confirm', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale',

    'css!controls/icons/Confirm.css'

], function (QUI, Confirm, QUILocale) {
    "use strict";

    return new Class({
        Extends: Confirm,
        Type   : 'controls/icons/Confirm',

        Binds: [
            '$onOpen'
        ],

        options: {
            title    : QUILocale.get('quiqqer/core', 'control.icons.confirm.title'),
            icon     : 'fa fa-css3',
            'class'  : 'qui-window-popup-icons',
            width    : 1000,
            height   : 700,
            maxHeight: 700,
            maxWidth : 1000,
            texticon : false,
            autoclose: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Frame = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            var self    = this,
                Content = this.getContent();

            Content.set('html', '');
            Content.setStyles({
                padding: 0,
                height : '100%'
            });

            this.Loader.show();

            var id = this.getId(),
                lg = 'quiqqer/core';

            // Resolve all picker strings on the parent side. The iframe
            // does not have reliable access to the locale cache.
            var localeMap = {
                searchPlaceholder : QUILocale.get(lg, 'control.icons.confirm.filterIcons'),
                noResults         : QUILocale.get(lg, 'control.icons.confirm.noResultsInfo'),
                categoryAll       : QUILocale.get(lg, 'control.icons.confirm.category.all'),
                previewPlaceholder: QUILocale.get(lg, 'control.icons.confirm.preview.placeholder'),
                cssClassLabel     : QUILocale.get(lg, 'control.icons.confirm.preview.cssClass'),
                copyHtml          : QUILocale.get(lg, 'control.icons.confirm.preview.copyHtml'),
                copied            : QUILocale.get(lg, 'control.icons.confirm.preview.copied'),
                cssClassHint      : QUILocale.get(lg, 'control.icons.confirm.cssClassHint')
            };

            var src = URL_OPT_DIR + 'quiqqer/core/bin/QUI/controls/icons/iconList.php'
                + '?quiid=' + id
                + '&locale=' + encodeURIComponent(JSON.stringify(localeMap));

            this.$Frame = new Element('iframe', {
                'class'    : 'window-iconSelect-iframe',
                src        : src,
                border     : 0,
                frameborder: 0,
                styles     : {
                    border: 0,
                    height: '100%',
                    width : '100%'
                },
                events     : {
                    load: function () {
                        self.Loader.hide();

                        // Move focus into the iframe search field on open.
                        try {
                            var doc = this.contentDocument;
                            var search = doc && doc.getElementById('search');
                            if (search) {
                                search.focus();
                            }
                        } catch (e) {
                            // cross-origin or not ready – ignore
                        }
                    }
                }
            }).inject(Content);
        },

        /**
         * Return the selected icons
         *
         * @returns {Array}
         */
        getSelected: function () {
            if (!this.$Frame || typeof this.$Frame.contentWindow === 'undefined') {
                return [];
            }

            if (typeof this.$Frame.contentWindow.getSelected !== 'function') {
                return [];
            }

            return this.$Frame.contentWindow.getSelected();
        },

        /**
         * Submit the window
         */
        submit: function () {
            var selected = this.getSelected();

            if (!selected.length) {
                return;
            }

            this.fireEvent('submit', [this, selected]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
