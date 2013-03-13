/**
 * PCSG System Check
 *
 * @author www.pcsg.de (Henning Leutz)
 * @depricated
 */

define('extras/system/update/System', [

    'controls/buttons/Button'

], function()
{
    QUI.namespace('extras.system.Update');

    QUI.extras.system.Update.System =
    {
        setup : function(onfinish)
        {
            QUI.Ajax.post('ajax_update_systemsetup', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish
            });
        },

        optimize : function(onfinish)
        {
            QUI.Ajax.post('ajax_update_systemoptimize', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                onfinish : onfinish
            });
        }
    };

    return QUI.extras.system.Update.System;
});
