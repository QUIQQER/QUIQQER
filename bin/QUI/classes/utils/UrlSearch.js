/**
 * @module classes/utils/UrlSearch
 * @author www.pcsg.de (Henning Leutz)
 */
define('classes/utils/UrlSearch', [

    'qui/classes/DOM',
    'URI'

], function (QDOM, URI) {
    "use strict";

    // history popstate for mootools
    Element.NativeEvents.popstate = 2;

    window.addEvent('load', function () {
        // browser workaround, to add the first page to the history
        if (window.location.hash === '') {
            history.pushState({}, '', '');
        }
    });


    return new Class({

        Extends: QDOM,
        Type   : 'classes/utils/UrlSearch',

        initialize: function (options) {
            this.parent(options);

            // read url
            window.addEvent('popstate', () => {
                this.fireEvent('change', [this]);
            });
        },

        // region setter

        setUrlGetter: function (getter) {
            const uri = URI(window.location.toString());

            history.pushState({}, '', uri.search(getter));
            this.fireEvent('change', [this]);
        },

        //endregion

        // region getter

        getUrlGetter: function () {
            const uri = URI(window.location.toString());
            return uri.search(true);
        }

        //endregion
    });
});
