/**
 * Permissions Utils
 * Helper for the permissions controls
 *
 * @module utils/permissions/Utils
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require Locale
 * @require css!utils/permissions/Utils.css
 */

define('utils/permissions/Utils', [

    'Locale',
    'classes/permissions/Permissions',
    'css!utils/permissions/Utils.css'

], function(QUILocale, Permissions)
{
    "use strict";

    var Perm = new Permissions();

    return {

        Permissions : Perm,

        /**
         * Parse a permission param to a DOMNode
         *
         * @param {Object} params
         * @return {HTMLElement}
         */
        parse : function(params)
        {
            if (!params.hasOwnProperty('name')) {
                return new Element('div');
            }

            var title      = params.title.split(' '),
                permission = params.name;

            var Entry = new Element('div.qui-permission-entry');

            var Input = new Element('input.right', {
                type : 'text',
                name : permission,
                id   : 'perm-'+ permission,

                'data-area' : params.area
            });

            Input.addClass( params.type );

            if (params.type == 'bool') {
                Input.type = 'checkbox';
            }

            var Label = new Element('label', {
                'for' : 'perm-'+ name,
                html  : QUILocale.get(title[0], title[1])
            });

            Input.inject( Entry );
            Label.inject( Entry );

            if ("desc" in params)
            {
                var desc = params.desc.split(' ');

                Label.set(
                    'data-desc',
                    QUILocale.get(desc[0], desc[1])
                );
            }

            return Entry;
        }
    };
});
