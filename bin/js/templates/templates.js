/**
 * JavaScript onLoad Event Handling für Plugins
 *
 * @depricated
 */

window.loads = [];

window.addOnLoad = function(func) {
    window.loads.push( func );
};

window.execLoad = function()
{
    for (var i = 0, len = window.loads.length; i < len; i++) {
        window.loads[i]();
    }
};
