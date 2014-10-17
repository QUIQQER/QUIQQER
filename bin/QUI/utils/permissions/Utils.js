/**
 * Permissions Utils
 * Helper for the permissions controls
 *
 * @module utils/permissions/Utils
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require css!utils/permissions/Utils.css
 */

define(['css!utils/permissions/Utils.css'], function()
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


            if ( params.desc ) {
                Label.set( 'data-desc', params.desc);
            }

            return Entry;
        }
    };
});
