/**
 * @module controls/users/auth/GlobalAuthenticatorSettings
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/users/auth/GlobalAuthenticatorSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'Mustache',

    'text!controls/users/auth/GlobalAuthenticatorSettings.Row.html',
    'text!controls/users/auth/GlobalAuthenticatorSettings.Header.html'

], function (QUI, QUIControl, QUIAjax, QUILocale, Mustache, templateRow, templateHeader) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({
        Type: 'controls/users/auth/GlobalAuthenticatorSettings',
        Extends: QUIControl,

        Binds: [
            '$onChange'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$primaryTable = null;
            this.$secondaryTable = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            QUIAjax.get('ajax_users_authenticator_globalAuthenticators', (result) => {
                const available = result.available,
                    globals = result.global;

                console.log(result);

                new Element('div', {
                    html: QUILocale.get(lg, 'quiqqer.settings.auth.global.desc')
                }).inject(this.getElm(), 'after');

                let i, r, len, rLen, newRow;
                let row, cell;

                const Table = this.getElm().getParent('table');

                // primary authenticator
                row = document.createElement('tr');
                cell = document.createElement('td');

                row.appendChild(cell);
                Table.appendChild(row);

                let tableHeaderText = Mustache.render(templateHeader, {
                    title: QUILocale.get(lg, 'quiqqer.settings.auth.primary')
                });

                const primaryTable = document.createElement('table');
                primaryTable.classList.add('data-table', 'data-table-flexbox');
                primaryTable.innerHTML = '<thead>' + tableHeaderText + '</thead>';
                primaryTable.setAttribute('data-name', 'primary-authenticator');

                for (i = 0, len = available.length; i < len; i++) {
                    if (!available[i].isPrimaryAuthentication) {
                        continue;
                    }

                    newRow = document.createElement('div');
                    newRow.innerHTML = Mustache.render(templateRow, {
                        title: available[i].title,
                        description: available[i].description,
                        authenticator: available[i].authenticator
                    });

                    newRow.getElements('input').addEvent('change', this.$onChange);

                    primaryTable.appendChild(newRow);
                }

                cell.appendChild(primaryTable);
                this.$primaryTable = primaryTable;


                // secondary authenticator
                tableHeaderText = Mustache.render(templateHeader, {
                    title: QUILocale.get(lg, 'quiqqer.settings.auth.secondary')
                });

                const secondaryTable = document.createElement('table');
                secondaryTable.classList.add('data-table', 'data-table-flexbox');
                secondaryTable.innerHTML = '<thead>' + tableHeaderText + '</thead>';
                secondaryTable.setAttribute('data-name', 'secondary-authenticator');

                for (i = 0, len = available.length; i < len; i++) {
                    if (!available[i].isSecondaryAuthentication) {
                        continue;
                    }

                    newRow = document.createElement('div');
                    newRow.innerHTML = Mustache.render(templateRow, {
                        title: available[i].title,
                        description: available[i].description,
                        authenticator: available[i].authenticator
                    });

                    newRow.getElements('input').addEvent('change', this.$onChange);

                    secondaryTable.appendChild(newRow);
                }

                cell.appendChild(secondaryTable);
                this.$secondaryTable = secondaryTable;

                // configs / checkbox / selected
                this.$checkCheckboxes(globals.primary.frontend, primaryTable, 'frontend');
                this.$checkCheckboxes(globals.primary.backend, primaryTable, 'backend');
                this.$checkCheckboxes(globals.secondary.frontend, secondaryTable, 'frontend');
                this.$checkCheckboxes(globals.secondary.backend, secondaryTable, 'backend');

                this.$onChange();
            });
        },

        /**
         * Checks all checkboxes with given names and the specified value ("frontend" or "backend") and sets them to checked.
         *
         * @param {Array<string>} config - Array of names.
         * @param {HTMLElement} table - The DOM element to search in.
         * @param {string} type - frontend / backend
         */
        $checkCheckboxes: function (config, table, type) {
            for (let i = 0, len = config.length; i < len; i++) {
                let condition = [];
                condition.push('[name="' + config[i].escapeRegExp() + '"]');
                condition.push('[value="' + type + '"]');
                condition = condition.join('');

                let checkbox = table.querySelector(condition);

                if (checkbox) {
                    checkbox.checked = true;
                }
            }
        },

        /**
         * event: on checkbox change
         */
        $onChange: function () {
            const result = {
                primary: {frontend: [], backend: []},
                secondary: {frontend: [], backend: []}
            };

            function collect(table, target) {
                table.getElements('input[type="checkbox"]').forEach((elm) => {
                    if (elm.checked) {
                        if (elm.value === 'frontend') target.frontend.push(elm.name);
                        if (elm.value === 'backend') target.backend.push(elm.name);
                    }
                });
            }

            collect(this.$primaryTable, result.primary);
            collect(this.$secondaryTable, result.secondary);

            if (this.getElm().nodeName === 'INPUT') {
                this.getElm().value = JSON.encode(result);
            }
        },

        /**
         * Save the authenticator settings
         *
         * @returns {Promise}
         */
        save: function () {
            return new Promise((resolve, reject) => {
                QUIAjax.post('ajax_users_authenticator_save', resolve, {
                    authenticators: this.getElm().value,
                    onError: reject
                });
            });
        }
    });
});
