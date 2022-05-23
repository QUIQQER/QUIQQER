/**
 * @module controls/editors/CodeEditor
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/editors/CodeEditor', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        
        Extends: QUIControl,
        Type   : 'controls/editors/Editor',

        Binds: [
            '$onImport',
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });

            this.fireEvent('init', [this]);
        },

        $onImport: function () {

        },

        $onInject: function () {

        }
    });
});