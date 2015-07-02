
/**
 * Dropdown for project selection
 *
 * @module controls/projects/Select
 * @author www.pcsg.de (Patrick Müller)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require qui/controls/loader/Loader
 * @require Projects
 *
 * @event onChange [ value ]
 */

define('controls/projects/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'qui/controls/loader/Loader',

    'Projects'

], function(QUI, QUIControl, QUISelect, QUILoader, Projects)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/Select',

        Binds : [],
        options : {},

        initialize : function(options)
        {
            this.parent( options );

            this.Loader = new QUILoader();

            this.addEvents({
//                onCreate : this.$onCreate
                onIjnect : this.$onInject
            });
        },

        /**
         *  create
         */
        create : function()
        {
            var self = this;

            this.$Elm = new Element('div');

            this.$Select = new QUISelect({
                name : 'projects-select',
                events :
                {
                    onChange : function(value) {
                        self.fireEvent('change', [value]);
                    }
                }
            });

            this.$Select.inject(this.$Elm);
            this.Loader.inject(this.$Elm);

            if (this.getAttribute('styles')) {
                this.$Select.getElm().setStyles(this.getAttribute('styles'));
            }

            this.Loader.show();

            Projects.getList(function(result)
            {
                var i, len, langs, project;

                for (project in result)
                {
                    if (!result.hasOwnProperty(project)) {
                        continue;
                    }

                    langs = result[project].langs.split(',');

                    for (i = 0, len = langs.length; i < len; i++)
                    {
                        self.$Select.appendChild(
                            project +' ( '+ langs[ i ] +' )',
                            project +','+ langs[ i ],
                            'icon-home'
                        );
                    }
                }

                self.$Select.setValue(
                    self.$Select.firstChild().getAttribute('value')
                );

                self.Loader.hide();
            });

            return this.$Elm;
        },

        /**
         *
         * @returns {*}
         */
        getValue : function()
        {
            return this.$Select.getValue();
        }
    });
});
