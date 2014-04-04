
/**
 * VHost Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/system/VHosts', [

    'qui/QUI',
    'qui/controls/desktop/Panel'

], function(QUI, QUIPanel)
{
    "use strict";

    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/system/VHosts',

        Binds : [
            '$onCreate'
        ],

        options : {
            title : 'Virtual Hosts Einstellungen',
            icon  : 'icon-external-link'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * event : on create
         */
        $onCreate : function()
        {
            this.addButton({
                text : 'Virtual Host hinzufügen',
                textimage : 'icon-plus'
            });
        }

    });

});
