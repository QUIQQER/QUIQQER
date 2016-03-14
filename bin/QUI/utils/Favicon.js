/**
 *
 * @module utils/Favicon
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/messages/Favico
 */
define('utils/Favicon', [

    'qui/controls/messages/Favico'

], function (Favico) {
    "use strict";
    //
    //var Favicon     = document.getElement('[rel="shortcut icon"]'),
    //    defaultIcon = Favicon.get('href'),
    //    defaultType = Favicon.get('type');
    //
    //var LoadingAnimation = new Element('img', {
    //    src   : URL_BIN_DIR + 'images/loader.gif',
    //    styles: {
    //        display: 'none'
    //    }
    //}).inject(document.body);

    return {

        Favicon: null,
        $timer : null,

        getFavicon: function () {
            if (!this.Favicon) {
                this.Favicon = new Favico();
            }

            return this.Favicon;
        },

        /**
         * Show a loading animation in the favicon
         */
        loading: function () {
            this.Favicon = new Favico({
                fontFamily: 'FontAwesome',
                animation : 'pop'
            });

            this.Favicon.badge('*');
            this.$timer = (function () {
                this.Favicon.badge('*');
            }.bind(this)).periodical(1000);
        },

        /**
         * Set the default favicon icon
         */
        setDefault: function () {
            if (this.$timer) {
                clearInterval(this.$timer);
            }

            this.getFavicon().reset();
            this.$timer = null;
        }
    };
});
