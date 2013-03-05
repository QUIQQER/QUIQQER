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
            var html, label;

            var n = params.name;

            // type: bool, string, int, group, array *}
            if ( params.type == 'string' )
            {
                html = '<input type="text" class="string right"' +
                            ' name="'+ n +'" id="perm-'+ n +'" ' +
                        '/>';

            } else if ( params.type == 'int' )
            {
                html = '<input type="text" class="int right" ' +
                            'name="'+ n +'" id="perm-'+ n +'" ' +
                       '/>';

            } else if ( params.type == 'group' )
            {
                html = '<input type="text" class="group right" ' +
                            ' name="'+ n +'" id="perm-'+ n +'" ' +
                        '/>';
            } else
            {
                html = '<input type="checkbox" class="right" ' +
                            'name="'+ n +'" id="perm-'+ n +'" ' +
                        '/>';
            }

            label = '<label for="perm-'+ n +'">' +
                        ( params.title || params.name ) +
                    '</label>';

            return new Element('div.qui-permission-entry', {
                html : html + label
            });
        }

    };

    return QUI.controls.permissions.Utils;
});