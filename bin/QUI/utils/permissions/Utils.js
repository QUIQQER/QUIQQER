/**
 * Permissions Utils
 * Helper for the permissions controls
 *
 * @module utils/permissions/Utils
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require Locale
 * @require classes/permissions/Permissions
 * @require css!utils/permissions/Utils.css
 */
define('utils/permissions/Utils', [

    'Locale',
    'classes/permissions/Permissions',
    'css!utils/permissions/Utils.css'

], function (QUILocale, Permissions) {
    "use strict";

    var Perm = new Permissions();

    return {

        Permissions: Perm,

        /**
         * Parse a permission param to a DOMNode
         *
         * @param {Object} params
         * @return {HTMLElement}
         */
        parse: function (params) {
            if (!params.hasOwnProperty('name')) {
                return new Element('div');
            }

            var title      = params.title.split(' '),
                permission = params.name,

                Entry      = new Element('div.qui-permission-entry'),

                Input      = new Element('input.right', {
                    type: 'text',
                    name: permission,
                    id  : 'perm-' + permission,

                    'data-area': params.area
                });

            Input.addClass(params.type);

            if (params.type == 'bool') {
                Input.type = 'checkbox';
            }

            var text = title[0];

            if (title.length == 2) {
                text = QUILocale.get(title[0], title[1]);
            }


            var Label = new Element('label', {
                'for': 'perm-' + name,
                html : text
            });

            Input.inject(Entry);
            Label.inject(Entry);

            if ("desc" in params) {
                var desc = params.desc.split(' ');

                if (QUILocale.exists(desc[0], desc[1])) {
                    Label.set(
                        'data-desc',
                        QUILocale.get(desc[0], desc[1])
                    );
                }
            }

            return Entry;
        }
    };
});
