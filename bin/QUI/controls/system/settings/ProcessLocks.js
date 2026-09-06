/** Administration of the process lock backend. */
define('controls/system/settings/ProcessLocks', [
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale',
    'Mustache',
    'text!controls/system/settings/ProcessLocks.html',
    'css!qui/controls/messages/Message.css',
    'css!controls/system/settings/ProcessLocks.css'
], function (QUIControl, QUIButton, Ajax, Locale, Mustache, template) {
    'use strict';

    const text = key => Locale.get('quiqqer/core', 'processLocks.' + key);

    return new Class({
        Extends: QUIControl,
        Type: 'controls/system/settings/ProcessLocks',

        initialize: function (Panel) {
            this.parent();
            this.$Panel = Panel;
            this.$loaded = false;
            this.$dirty = false;
            this.$busy = false;
            this.$destroyed = false;
            this.$statusTimeout = null;
            this.addEvents({
                onDestroy: () => {
                    this.$destroyed = true;
                    clearTimeout(this.$statusTimeout);
                },
                onInject: () => this.load(),
                onImport: () => {
                    this.$render(this.getElm());
                    this.load();
                }
            });
        },

        create: function () {
            const root = this.parent();
            this.$render(root);
            return root;
        },

        $render: function (root) {
            if (root.querySelector('[data-name="fields"]')) {
                return;
            }
            root.classList.add('quiqqer-process-lock-settings');

            const labels = Object.fromEntries([
                'title', 'backend', 'flock', 'dbal', 'namespace', 'namespaceHelp', 'path', 'pathHelp',
                'dbalHelp', 'host', 'port', 'database', 'username', 'password', 'clearPassword', 'tls',
                'redisHelp', 'customHelp', 'changeHelp'
            ].map(key => [key, text(key)]));

            root.innerHTML = Mustache.render(template, {labels, idPrefix: 'process-locks-' + this.getId()});

            new QUIButton({
                text: text('test'),
                textimage: 'fa fa-plug',
                events: {onClick: () => this.test()}
            }).inject(this.$node('testButton'));

            const table = this.$node('fields');
            table.addEventListener('input', () => {
                this.$dirty = true;
                this.$status('');
            });
            table.addEventListener('change', () => {
                this.$dirty = true;
                this.$updateFields();
                this.$status('');
            });
            this.$setDisabled(true);
        },

        $setDisabled: function (disabled) {
            this.$node('fields').querySelectorAll('input, select, button').forEach(field => {
                field.disabled = disabled;
            });
            if (!disabled) {
                this.$updateFields();
            }
        },

        $node: function (name) {
            return this.getElm().querySelector('[data-name="' + name + '"]');
        },

        $status: function (message, type = 'information', timeout = 0) {
            clearTimeout(this.$statusTimeout);
            const status = this.$node('status');
            status.textContent = message;
            status.className = 'messages-message message-' + type;
            status.closest('tr').hidden = message === '';

            if (timeout > 0) {
                this.$statusTimeout = setTimeout(() => this.$status(''), timeout);
            }
        },

        $updateFields: function () {
            const backend = this.$node('backend').value;
            this.$node('flockFields').hidden = backend !== 'flock';
            this.$node('dbalHelp').hidden = backend !== 'dbal';
            this.$node('redisFields').hidden = backend !== 'redis';
            this.$node('customHelp').hidden = backend !== 'custom';
            this.$node('password').disabled = this.$node('clearPassword').checked;
        },

        $apply: function (data) {
            if (data.backend === 'custom' && !Array.from(this.$node('backend').options).some(o => o.value === 'custom')) {
                const option = document.createElement('option');
                option.value = 'custom';
                option.textContent = text('custom');
                this.$node('backend').appendChild(option);
            }
            for (const name of ['backend', 'namespace', 'path', 'host', 'port', 'database', 'username']) {
                this.$node(name).value = data[name] ?? '';
            }
            this.$node('tls').checked = Boolean(data.tls);
            this.$node('clearPassword').checked = false;
            this.$node('password').value = '';
            this.$node('password').placeholder = data.passwordConfigured ? text('passwordKept') : '';
            this.$loaded = true;
            this.$dirty = false;
            this.$updateFields();
        },

        $values: function () {
            const values = {};
            for (const name of ['backend', 'namespace', 'path', 'host', 'port', 'database', 'username', 'password']) {
                values[name] = this.$node(name).value;
            }
            values.tls = this.$node('tls').checked;
            values.clearPassword = this.$node('clearPassword').checked;
            return values;
        },

        $request: function (action, data) {
            return new Promise((resolve, reject) => {
                Ajax.post('ajax_system_settings_' + action + 'ProcessLocks', resolve, {
                    data: JSON.stringify(data), showError: false, onError: reject
                });
            });
        },

        load: function () {
            this.$Panel.Loader.show();
            this.$loaded = false;
            this.$setDisabled(true);
            this.$status('');
            this.$node('fields').hidden = true;
            this.getElm().setAttribute('aria-busy', 'true');
            this.$Loading = this.$request('get').then(data => {
                if (this.$destroyed) {
                    return;
                }
                this.$apply(data);
                this.$setDisabled(false);
                this.$status('');
            }).catch(() => {
                if (!this.$destroyed) {
                    this.$status(text('loadFailed'), 'error');
                }
            }).finally(() => {
                if (!this.$destroyed) {
                    this.$node('fields').hidden = false;
                    this.getElm().setAttribute('aria-busy', 'false');
                }
            });
            return this.$Loading;
        },

        /** Let the settings panel keep its loader visible until initialization finishes. */
        whenLoaded: function () {
            return this.$Loading;
        },

        save: function () {
            if (!this.$loaded || this.$busy) {
                this.$Panel.Loader.hide();
                return Promise.reject(new Error(text('notReady')));
            }
            if (!this.$dirty) {
                return Promise.resolve();
            }
            this.$busy = true;
            this.$setDisabled(true);
            return this.$request('save', this.$values()).then(data => {
                this.$apply(data);
                this.$status(text('saved'), 'success', 5000);
            }).catch(error => {
                this.$status(text('saveFailed'), 'error');
                this.$Panel.Loader.hide();
                throw error;
            }).finally(() => {
                this.$busy = false;
                this.$setDisabled(false);
            });
        },

        test: function () {
            if (!this.$loaded || this.$busy) {
                return Promise.resolve();
            }
            this.$busy = true;
            this.$setDisabled(true);
            this.$status(text('testing'));
            return this.$request('test', this.$values()).then(success => {
                this.$status(text(success ? 'testSuccess' : 'testFailed'), success ? 'success' : 'error', success ? 5000 : 0);
            }).catch(() => this.$status(text('testFailed'), 'error')).finally(() => {
                this.$busy = false;
                this.$setDisabled(false);
            });
        }
    });
});
