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

                Select.value = QUIQQER_CONFIG.globals.quiqqer_version;

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
