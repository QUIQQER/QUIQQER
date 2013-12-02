/**
 * The Welcome Quiqqer panel
 *
 * @author www.namerobot.com (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/welcome/Panel
 * @package com.pcsg.qui.js.controls.project
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
            title : 'Welcome to QUIQQER'
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

                '<h1>Welcome to QUIQQER</h1>'
            );
        }
    });
});