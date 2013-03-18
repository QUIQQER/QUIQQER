/**
 * Plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('lib/Plugins', ['classes/Plugin'], function(Plgn)
{
    QUI.namespace('lib');

    QUI.lib.Plugins =
    {
        $plugins  : {},

        /**
         * create an Plugin
         *
         * @param plugin - String: Plugin Name
         * @param options - Plugin Params
         *     events
         *     methods
         */
        create : function(plugin, options)
        {
            options      = options || {};
            options.name = plugin;

            this.$plugins[plugin] = new QUI.classes.Plugin(options);
        },

        get : function(plugin, onfinish)
        {
            if (this.$plugins[plugin])
            {
                this.$get(plugin, onfinish);
                return;
            }

            if (typeof QUI.MVC.plugins[plugin] === 'undefined')
            {
                onfinish( false );
                return;
            }

            QUI.MVC.require([plugin], function(plugin, onfinish)
            {
                this.$get(plugin, onfinish);

            }.bind(this, [plugin, onfinish]));
        },

        /**
         * Load ausführen
         */
        $get : function(plugin, onfinish)
        {
            if (this.$plugins[plugin].isLoaded())
            {
                onfinish( this.$plugins[plugin] );
                return;
            }

            this.$plugins[plugin].load(function()
            {
                onfinish( this.$plugins[plugin] );
            }.bind(this, [plugin, onfinish]));
        },

        getTypeName : function(type, onfinish, params)
        {
            params = QUI.lib.Utils.combine(params, {
                sitetype : type,
                onfinish : onfinish
            });

            QUI.Ajax.get('ajax_project_types_get_title', function(result, Ajax)
            {
                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        },

        getTypes : function(project, onfinish, params)
        {
            project = project || QUI.Projects.getName();

            params  = QUI.lib.Utils.combine(params, {
                project  : project,
                onfinish : onfinish
            });


            QUI.Ajax.get('ajax_project_types_get_list', function(result, Ajax)
            {
                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, params);
        }
    };

    return QUI.lib.Plugins;
});