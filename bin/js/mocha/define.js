/**
 * Mocha UI defines
 */

requirejs.config({
    shim: {
        'mocha/Core/create': ['mocha/Core/core'],
        'mocha/Core/require' : ['mocha/Core/core'],
        'mocha/Core/canvas' : ['mocha/Core/core'],
        'mocha/Core/content' : ['mocha/Core/core'],
        'mocha/Core/persist' : ['mocha/Core/core'],
        'mocha/Core/themes' : ['mocha/Core/core']
    }
});

define('mochaui', [
    'mocha/Core/core',
    'mocha/Core/create',
    'mocha/Core/canvas',

    'css!'+ URL_BIN_DIR +'js/mocha/Themes/default/css/core.css',
    'css!'+ URL_BIN_DIR +'js/mocha/Themes/default/css/desktop.css',
    'css!'+ URL_BIN_DIR +'js/mocha/Themes/default/css/window.css'

], function()
{

});
