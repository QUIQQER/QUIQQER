
/**
 * Package details window
 *
 * @module controls/packages/DetailWindow
 * @author www.pcsg.de (Henning Leutz)
 */


define([

    'qui/QUI',
    'qui/controls/windows/Popup',
    'controls/packages/Details'

], function (QUI, QUIPopup, Details) {
    "use strict";


    return new Class({

        Extends : QUIPopup,
        Type    : 'controls/packages/DetailWindow',

        Binds : [
            '$onOpen'
        ],

        options : {
            'package' : false,
            title     : 'Paket Details',
            icon      : 'icon-briefcase',
            maxWidth  : 400,
            maxHeight : 500
        },

        initialize : function (options) {
            this.parent(options);

            this.addEvents({
                onOpen : this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen : function () {
            var self    = this,
                Content = this.getContent();

            Content.set('html', '');

            new Details({
                'package' : this.getAttribute('package'),
                events :
                {
                    onOpenSheet : function (Detail, Sheet) {
                        self.hideButtons();

                        Sheet.getElements('.qui-sheet-buttons-back')
                             .setStyle('display', 'none');
                    }
                }
            }).inject(Content);
        }
    });

});
