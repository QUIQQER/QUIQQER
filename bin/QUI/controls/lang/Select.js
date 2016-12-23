/**
 * @package controls/lang/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * Sprachauswahl - DropDown
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require Ajax
 * @require Locale
 *
 * @event onChange
 */
define('controls/lang/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUISelect, QUIAjax, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIControl,

        Binds: [
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input  = null;
            this.$Select = null;

            this.addEvents({
                onImport: this.$onInject
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var Elm = this.getElm();

            if (Elm.nodeName === 'INPUT') {
                Elm.type = 'hidden';

                this.$Input = Elm;
                this.$Elm   = new Element('div').wraps(this.$Input);
            }

            this.$Select = new QUISelect({
                disabled: true,
                events  : {
                    onChange: function (value) {
                        this.$Input.value = value;
                        this.fireEvent('change', [this, value]);
                    }.bind(this)
                }
            }).inject(this.$Elm);

            QUIAjax.get('ajax_system_getAvailableLanguages', function (languages) {
                for (var i = 0, len = languages.length; i < len; i++) {
                    this.$Select.appendChild(
                        QUILocale.get('quiqqer/system', 'language.' + languages[i]),
                        languages[i],
                        URL_BIN_DIR + '16x16/flags/' + languages[i] + '.png'
                    );
                }

                this.$Select.setValue(this.$Input.value);

            }.bind(this));
        }
    });
});