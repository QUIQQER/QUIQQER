/**
 * Help panel
 *
 * @module controls/desktop/panels/Help
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 */
define('controls/desktop/panels/Help', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'Locale'

], function (QUI, QUIPanel, QUIButton, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/desktop/panels/Help',

        Binds: [
            '$onCreate',
            '$onResize'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Frame = null;

            this.setAttribute('title', QUILocale.get('quiqqer/quiqqer', 'help.panel.title'));
            this.setAttribute('icon', 'fa fa-h-square');

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            var self    = this,
                Content = this.getContent();

            Content.setStyles({
                padding: 0
            });

            new QUIButton({
                text  : QUILocale.get('quiqqer/quiqqer', 'help.panel.button.open.text'),
                icon  : 'fa fa-refresh',
                styles: {
                    fontSize: 18,
                    width   : 200,
                    margin  : '20px auto',
                    'float' : 'none',
                    display : 'block'
                },
                events: {
                    onClick: function () {
                        self.load();
                    }
                }
            }).inject(Content);
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Frame) {
                return;
            }

            var Content = this.getContent(),
                size    = Content.getSize();

            this.$Frame.setStyles({
                height: size.y - 4,
                width : '100%'
            });
        },

        /**
         * load the help
         */
        load: function () {
            var self    = this,
                Content = this.getContent();

            Content.set('html', '');

            this.Loader.show();

            this.$Frame = new Element('iframe', {
                src        : '//www.quiqqer.com/Infos-Tipps-Hilfe/QUIQQER-FAQ',
                styles     : {
                    border: 0
                },
                frameborder: 0,
                border     : 0
            }).inject(Content);

            // check if frame is loaded
            var inter = window.setInterval(function () {
                try {
                    if (self.$Frame.contentWindow.document.readyState === "complete") {
                        window.clearInterval(inter);
                        self.Loader.hide();
                    }
                } catch (e) {
                    window.clearInterval(inter);
                    self.Loader.hide();
                }
            }, 100);

            // fallback
            (function () {
                self.Loader.hide();
            }).delay(10000);

            this.resize();
        }
    });
});
