/**
 * @module controls/users/auth/GlobalAuthenticatorSettings
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
                const secondaryDescription = document.createElement('div');
                secondaryDescription.classList.add('quiqqer-message', 'quiqqer-message-information');
                secondaryDescription.style.display = 'flex';
                secondaryDescription.style.alignItems = 'center';
                secondaryDescription.style.gap = '10px';
                secondaryDescription.style.width = '100%';
                secondaryDescription.style.maxWidth = '100%';
                secondaryDescription.style.margin = '10px 0';
                secondaryDescription.innerHTML = `
                    <span>
                        Hier können Sie festlegen, dass Benutzer zusätzlich zum primären Authenticator einen zweiten Authenticator (2FA) 
                        nutzen müssen. Sie können separat bestimmen, ob die Zwei-Faktor-Authentifizierung für das Frontend, 
                        das Backend oder beide Bereiche verpflichtend sein soll.
                    </span>
                `;


                tableHeaderText = Mustache.render(templateHeader, {
                    title: QUILocale.get(lg, 'quiqqer.settings.auth.secondary')
                });

                const secondaryTable = document.createElement('table');
                secondaryTable.classList.add('data-table', 'data-table-flexbox');
                secondaryTable.innerHTML = '<thead>' + tableHeaderText + '</thead><tbody></tbody>';
                secondaryTable.setAttribute('data-name', 'secondary-authenticator');

                const secondTd = secondaryTable.querySelector('thead th div:nth-child(2)');
                const thirdTd = secondaryTable.querySelector('thead th div:nth-child(3)');

                secondTd.parentNode.removeChild(secondTd);
                thirdTd.parentNode.removeChild(thirdTd);

                secondaryTable.querySelector('tbody').innerHTML = `
                    <tr>
                        <td>
                            <label class="field-container hasCheckbox">
                                <div class="field-container-item">Frontend</div>  
                                <div class="field-container-field">
                                    <input type="checkbox" name="secondary_frontend" />
                                    Nutzer müssen im Frontend eine zweite Authentifizierung einrichten.  
                                </div>  
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="field-container hasCheckbox">
                                <div class="field-container-item">Backend</div>  
                                <div class="field-container-field">
                                    <input type="checkbox" name="secondary_backend" />
                                    Nutzer müssen im Backend eine zweite Authentifizierung einrichten.
                                </div>  
                            </label>
                        </td>
                    </tr>
                `;

                secondaryTable.querySelector('thead th').style.flexDirection = 'column';
                secondaryTable.querySelector('thead th').appendChild(secondaryDescription);
                cell.appendChild(secondaryTable);
                this.$secondaryTable = secondaryTable;

                // configs / checkbox / selected
                this.$checkCheckboxes(globals.primary.frontend, primaryTable, 'frontend');
                this.$checkCheckboxes(globals.primary.backend, primaryTable, 'backend');

                const secondaryFrontend = this.$secondaryTable.querySelector('input[name="secondary_frontend"]');
                const secondaryBackend = this.$secondaryTable.querySelector('input[name="secondary_backend"]');

                secondaryFrontend.addEventListener('change', this.$onChange);
                secondaryFrontend.checked = globals.secondary.frontend;

                secondaryBackend.addEventListener('change', this.$onChange);
                secondaryBackend.checked = globals.secondary.backend;

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
                //secondary: {frontend: [], backend: []}
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

            result.secondary_frontend = this.$secondaryTable.querySelector('input[name="secondary_frontend"]').checked ? 1 : 0;
            result.secondary_backend = this.$secondaryTable.querySelector('input[name="secondary_backend"]').checked ? 1 : 0;

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
