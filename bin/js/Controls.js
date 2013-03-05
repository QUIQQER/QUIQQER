/**
 * QUI.Controls
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Controls
 * @package com.pcsg.qui.js.controls
 * @namespace QUI.Controls
 */

define('Controls', function()
{
    /**
     * QUI Controls - manage controls
     */
    QUI.Controls = {

        $controls : {},
        $cids     : {},

        /**
         * Get the Controls by name
         *
         * @param {String} n - Name of the Control
         * @return {Array} All Controls with the needle name
         */
        get : function(n)
        {
            if ( typeof this.$controls[ n ] === 'undefined' ) {
                return [];
            }

            return this.$controls[ n ];
        },

        /**
         * Get the Controls by its unique id
         *
         * @param {String|Integer} id - ID of the wanted Control
         * @return {QUI.classes.Control|false} a QUI control, based on QUI.classes.Control or false
         */
        getById : function(id)
        {
            if ( typeof this.$cids[ id ] !== 'undefined' ) {
                return this.$cids[ id ];
            }

            return false;
        },

        /**
         * Load a control by a control type
         *
         * @param {String} type
         * @param {Function} onload
         *
         * @exampl QUI.Controls.getByType('QUI.controls.taskbar.Task', function(Modul) { })
         */
        getByType : function(type, onload)
        {
            var modul = type.replace( 'QUI.', '' )
                            .replace( /\./g, '/' );

            require( [ modul ] , onload );
        },

        /**
         * Add a Control to the list
         *
         * @param {QUI.classes.Control} Control
         */
        add : function(Control)
        {
            var n = Control.getAttribute( 'name' );

            if ( !n || n === '' ) {
                n = '#unknown';
            }

            if ( typeof this.$controls[ n ] === 'undefined' ) {
                this.$controls[ n ] = [];
            }

            this.$controls[ n ].push( Control );
            this.$cids[ Control.getId() ] = Control;
        },

        /**
         * Destroy a Control
         *
         * @param {QUI.classes.Control} Control
         */
        destroy : function(Control)
        {
            var n  = Control.getAttribute('name'),
                id = Control.getId();

            if ( !n || n === '' ) {
                n = '#unknown';
            }

            if ( typeof this.$cids[ id ] !== 'undefined' ) {
                delete this.$cids[ id ];
            }


            if ( typeof this.$controls[ n ] === 'undefined' ) {
                return;
            }

            var i, len;
            var tmp = [];

            for ( i = 0, len = this.$controls[ n ].length; i < len; i++ )
            {
                if ( id !== this.$controls[ n ][ i ].getId() ) {
                    tmp.push( this.$controls[ n ][ i ] );
                }
            }

            this.$controls[n] = tmp;
        }
    };

    return QUI.Controls;
});