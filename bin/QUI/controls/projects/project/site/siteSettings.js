/**
 *
 */

define('controls/projects/project/site/siteSettings', [

    'qui/QUI'

], function(QUI)
{
    "use strict";

    return {

        /**
         * event onload settings
         *
         * @param {qui/controls/buttons/Button} Category
         * @param {qui/controls/desktop/Panel} Panel
         */
        onload : function(Category, Panel)
        {
            console.log('load settings');

            Panel.Loader.hide();
        },

        /**
         * event onunload settings
         *
         * @param {qui/controls/buttons/Button} Category
         * @param {qui/controls/desktop/Panel} Panel
         */
        onunload : function(Category, Panel)
        {

        }
    };

});