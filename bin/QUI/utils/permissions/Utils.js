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

define('utils/permissions/Utils', [

    'css!utils/permissions/Utils.css'

],function()
{
    "use strict";

    return {

        /**
         * Parse a permission param to a DOMNode
         *
         * @param {Object} params
         * @return {DOMNode}
         */
        parse : function(params)
        {
            var n = params.name;

            var Entry = new Element( 'div.qui-permission-entry' );

            var Input = new Element('input.right', {
                type : 'text',
                name : n,
                id   : 'perm-'+ n,

                'data-area' : params.area
            });

            Input.addClass( params.type );

            if ( params.type == 'bool' ) {
                Input.type = 'checkbox';
            }

            var Label = new Element('label', {
                'for' : 'perm-'+ n,
                html  : params.title || params.name
            });

            Input.inject( Entry );
            Label.inject( Entry );


            if ( params.desc )
            {
                var Container = new Element('div', {
                    styles : {
                        'float' : 'left',
                        marginLeft : 10
                    }
                });

                Container.wraps( Label );

                Label.setStyles({
                    cursor : 'pointer',
                    margin : 0
                });

                new Element('p', {
                    html : params.desc,
                    styles : {
                        margin : 0
                    }
                }).inject( Container );
            }

            return Entry;
        }

    };
});