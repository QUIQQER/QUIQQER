/**
 * quiqqer config
 *
 * @module controls/system/settings/Config
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/system/settings/Config', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale'

], function(QUI, QUIControl, QUIConfirm, Ajax, QUILocale) {
    'use strict';

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIControl,
        Type: 'controls/system/settings/Config',

        Binds: [
            '$onImport'
        ],

        initialize: function(Panel) {
            this.$Panel = Panel;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function() {
            const self = this,
                Panel = this.$Panel;

            Ajax.get('ajax_system_getQuiqqerVersions', function(versions) {
                const Select = Panel.getContent().getElement('[name="globals.quiqqer_version"]');

                if (!Select) {
                    return;
                }

                for (let i = 0, len = versions.length; i < len; i++) {
                    new Element('option', {
                        value: versions[i],
                        html: versions[i]
                    }).inject(Select);
                }

                const selectedVersion = QUIQQER_CONFIG.globals.quiqqer_version;

                // replace last security version number
                if (selectedVersion.indexOf('dev') === -1) {
                    let parts = selectedVersion.split('.');
                    let version;

                    if ((parts[0] + '.' + parts[1]).indexOf('.*') === -1) {
                        version = parts[0] + '.' + parts[1] + '.*';
                    } else {
                        version = parts[0] + '.' + parts[1];
                    }

                    if (!Select.getElement('[value="' + version + '"]')) {
                        new Element('option', {
                            value: version,
                            html: version
                        }).inject(Select);
                    }

                    Select.value = version;
                } else {
                    Select.value = selectedVersion;
                }

                new Element('div', {
                    html: QUILocale.get(lg, 'quiqqer.config.current.version', {
                        version: QUIQQER_VERSION
                    }),
                    'class': 'messages message-attention',
                    styles: {
                        border: '1px solid rgba(147, 128, 108, 0.25)',
                        display: 'inline-block',
                        width: '100%',
                        textAlign: 'center',
                        padding: '5px'
                    }
                }).inject(Select.getParent('label'), 'after');

                Panel.Loader.hide();
            });
        }
    });
});
