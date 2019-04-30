/**
 * Dropdown for project selection
 *
 * @module controls/projects/Select
 * @author www.pcsg.de (Patrick Müller)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require qui/controls/loader/Loader
 * @require Projects
 *
 * @event onChange [ value, self ]
 * @event onLoad [ self ]
 */
define('controls/projects/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'qui/controls/loader/Loader',

    'Projects'

], function (QUI, QUIControl, QUISelect, QUILoader, Projects) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/Select',

        options: {
            langSelect   : true,
            emptyselect  : true,
            icon         : 'fa fa-home',
            localeStorage: false // name for the locale storage, if this is set, the value is stored in the locale storage
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();
        },

        /**
         *  create
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div');

            var localStorageValue = QUI.Storage.get('dashboard-media-info-card-project-select');

            this.$Select = new QUISelect({
                name         : 'projects-select',
                events       : {
                    onChange: function (value) {
                        self.fireEvent('change', [value, self]);
                    }
                },
                localeStorage: this.getAttribute('localeStorage')
            });

            this.$Select.inject(this.$Elm);
            this.Loader.inject(this.$Elm);

            if (this.getAttribute('styles')) {
                this.$Select.getElm().setStyles(this.getAttribute('styles'));
            }

            this.Loader.show();

            // empty value
            if (this.getAttribute('emptyselect')) {
                this.$Select.appendChild('', '', this.getAttribute('icon'));
            }

            Projects.getList(function (result) {
                var i, len, langs, project;

                for (project in result) {
                    if (!result.hasOwnProperty(project)) {
                        continue;
                    }

                    if (self.getAttribute('langSelect') === false) {
                        self.$Select.appendChild(
                            project,
                            project,
                            self.getAttribute('icon')
                        );

                        continue;
                    }

                    langs = result[project].langs.split(',');

                    for (i = 0, len = langs.length; i < len; i++) {
                        self.$Select.appendChild(
                            project + ' ( ' + langs[i] + ' )',
                            project + ',' + langs[i],
                            self.getAttribute('icon')
                        );
                    }
                }

                var value = self.$Select.firstChild().getAttribute('value');

                if (localStorageValue) {
                    try {
                        value = JSON.decode(localStorageValue);
                    } catch (e) {
                        value = self.$Select.firstChild().getAttribute('value');
                    }
                }

                self.$Select.setValue(value);

                self.fireEvent('load', [self]);
                self.Loader.hide();
            });

            return this.$Elm;
        },

        /**
         *
         * @returns {*}
         */
        getValue: function () {
            return this.$Select.getValue();
        }
    });
});
