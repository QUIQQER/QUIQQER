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

    const lg = 'quiqqer/quiqqer';

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

                Select.addEvent('change', function() {
                    if (this.value === 'dev-dev') {
                        self.setDevelopment();
                    }
                });

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
        },

        /**
         * Set the system to development mode
         */
        setDevelopment: function() {
            const self = this;

            new QUIConfirm({
                title: 'Development Modus',
                maxWidth: 600,
                maxHeight: 400,
                autoclose: false,
                events: {
                    onOpen: function(Win) {
                        const Content = Win.getContent();

                        Win.Loader.show();

                        Content.set(
                            'html',

                            '<p>Sie möchten QUIQQER in den Entwicklungsmodus stellen.</p>' +
                            '<p>Wir empfehlen folgende Pakete auch in den Entwicklungsmodus zu stellen.</p>' +
                            '<p>Bitte wählen Sie aus welche Pakete in Entwickler Versionen verwendet werden sollen.</p>' +
                            '<br />'
                        );

                        Ajax.get('ajax_system_packages_list', function(result) {
                            let id = Win.getId();

                            result.push(
                                {name: 'quiqqer/qui'},
                                {name: 'quiqqer/quiqqer'},
                                {name: 'quiqqer/qui-php'},
                                {name: 'quiqqer/utils'}
                            );

                            for (let i = 0, len = result.length; i < len; i++) {
                                new Element('div', {
                                    html: '<input type="checkbox" value="' + result[i].name + '" id="w' + id + '_' + i +
                                        '" />' +
                                        '<label for="w' + id + '_' + i + '">' + result[i].name + '</label>'
                                }).inject(Content);
                            }

                            Content.getElements('[type="checkbox"]').set('checked', true);

                            Win.Loader.hide();

                        }, {
                            params: JSON.encode({
                                type: 'quiqqer-library'
                            })
                        });
                    },

                    onSubmit: function(Win) {
                        Win.Loader.show();

                        const packages = Win.getContent().getElements('[type="checkbox"]:checked').map(function(Elm) {
                            return Elm.get('value');
                        });

                        if (!packages.length) {
                            Win.close();
                            return;
                        }

                        Ajax.post('ajax_system_packages_setVersion', function() {
                            Win.close();
                        }, {
                            packages: JSON.encode(packages),
                            version: 'dev-dev'
                        });
                    },

                    onCancel: function() {
                        self.$Panel.getContent().getElements('[name="globals.quiqqer_version"]').set(
                            'value',
                            QUIQQER_VERSION
                        );
                    }
                }
            }).open();
        }
    });
});
