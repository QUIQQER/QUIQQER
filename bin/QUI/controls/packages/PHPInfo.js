/**
 * @module controls/packages/PHPInfo
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 *
 * @event onLoad
 */
define('controls/packages/PHPInfo', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',

    'css!controls/packages/PHPInfo.css'

], function (QUI, QUIControl, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/PHPInfo',

        Binds: [
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Container = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'qui-control-packages-phpinfo',
                'html' : '<div class="qui-control-packages-phpinfo-container"></div>'
            });

            this.$Container = this.$Elm.getElement(
                '.qui-control-packages-phpinfo-container'
            );

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            QUIAjax.get('ajax_system_phpinfo', function (result) {
                this.$Container.set('html', result);
                this.$Container.getChildren('h2')[0].destroy();

                this.fireEvent('load', [this]);
            }.bind(this));
        }
    });
});
