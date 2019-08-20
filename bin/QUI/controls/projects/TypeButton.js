/**
 * The type button opens a type window for the project
 *
 * @module controls/projects/TypeButton
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/TypeButton', [

    'qui/controls/Control',
    'controls/projects/TypeWindow',
    'qui/controls/buttons/Button',
    'Locale'

], function (QUIControl, TypeWindow, QUIButton, Locale) {
    "use strict";

    /**
     * @class controls/projects/TypeButton
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/TypeButton',

        options: {
            project: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Button = null;
            this.$Elm    = null;
        },

        /**
         * Create the type button
         *
         * @method controls/projects/TypeButton#create
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

            this.$Button = new QUIButton({
                name  : 'project-types',
                image : 'fa fa-magic',
                alt   : Locale.get('quiqqer/system', 'projects.typebutton.title'),
                title : Locale.get('quiqqer/system', 'projects.typebutton.title'),
                events: {
                    click: function () {
                        new TypeWindow({
                            project: self.getAttribute('project'),
                            events : {
                                onSubmit: function (Win, result) {
                                    if (result[0]) {
                                        self.fireEvent('submit', [self, result[0]]);
                                    }
                                },

                                onCancel: function () {
                                    self.fireEvent('cancel');
                                }
                            }
                        }).open();
                    }
                }
            });

            this.$Elm = this.$Button.create();
            this.$Elm.set('data-quiid', this.getId());

            return this.$Elm;
        }
    });
});
