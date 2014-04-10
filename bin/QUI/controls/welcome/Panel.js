/**
 * The Welcome Quiqqer panel
 *
 * @author www.namerobot.com (Henning Leutz)
 * @module controls/welcome/Panel
 *
 * @requires qui/controls/desktop/Panel
 */

define('controls/welcome/Panel', [

    'qui/controls/desktop/Panel'

], function(QUIPanel)
{
    "use strict";

    /**
     * @class controls/welcome/Panel
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/welcome/Panel',

        Binds : [
            '$onCreate'
        ],

        options : {
            icon : 'icon-thumbs-up',
            title : 'Willkommen bei QUIQQER'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * Create the project panel body
         *
         * @method controls/welcome/Panel#$onCreate
         */
        $onCreate : function()
        {
            this.getContent().set(
                'html',

                '<h1>Willkommen bei QUIQQER</h1>'+
                '<p>Hier kommt ein Willkommenstext mit allen Einzelheiten zum Start</p>'
            );
        }
    });
});