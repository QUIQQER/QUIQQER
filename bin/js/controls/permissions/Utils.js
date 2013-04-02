/**
 * Permissions Utils
 * Helper for the permissions controls
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/permissions/Utils
 * @package com.pcsg.qui.js.controls.permissions
 * @namespace  QUI.controls.permissions
 */

define('controls/permissions/Utils', [

    'css!controls/permissions/Utils.css'

],function()
{
    "use strict";

    QUI.namespace( 'controls.permissions' );

    QUI.controls.permissions.Utils = {

        /**
         * Parse a permission param to a DOMNode
         *
         * @param {Object} params
         * @return {DOMNode}
         */
        parse : function(params)
        {
            var Entry, Input, Label,
                n = params.name;


            Input = new Element('input.right', {
                type : 'text',
                name : n,
                id   : 'perm-'+ n,

                'data-area' : params.area
            });

            Input.addClass( params.type );

            if ( params.type == 'bool' ) {
                Input.type = 'checkbox';
            }

            Label = new Element('label', {
                'for' : 'perm-'+ n,
                html  : params.title || params.name
            });

            Entry = new Element( 'div.qui-permission-entry' );
            Input.inject( Entry );
            Label.inject( Entry );


            return Entry;
        }

    };

    return QUI.controls.permissions.Utils;
});