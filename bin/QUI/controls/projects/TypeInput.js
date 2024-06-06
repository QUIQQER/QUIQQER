/**
 * The type input set the type control to an input field
 *
 * @module controls/projects/TypeInput
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/TypeInput', [

    'qui/controls/Control',
    'controls/projects/TypeButton',
    'controls/projects/TypeWindow',
    'Plugins',
    'Locale',

    'css!controls/projects/TypeInput.css'

], function (QUIControl, TypeButton, TypeWindow, Plugins, QUILocale) {
    "use strict";

    /**
     * @class controls/projects/TypeInput
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input] - (optional), Input field
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/TypeInput',

        options: {
            project: false,
            name   : '',
            value  : false
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input = Input || null;
            this.$Elm = null;
            this.$Text = null;

            this.$TypeButton = null;
        },

        /**
         * Create the button and the type field
         *
         * @method controls/projects/TypeInput#create
         * @return {HTMLElement}
         */
        create: function () {
            const self = this;

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name: this.getAttribute('name')
                });
            }

            this.$Input.type = 'hidden';

            if (this.getAttribute('value')) {
                this.$Input.value = this.getAttribute('value');
            }

            this.$Elm = new Element('div', {
                'class'     : 'qui-projects-type-input',
                'data-quiid': this.getId(),
                'data-qui'  : 'controls/projects/TypeInput'
            });

            this.$Elm.wraps(this.$Input);

            // create the type button
            this.$TypeButton = new TypeButton({
                project: this.getAttribute('project'),
                events : {
                    onSubmit: function (Btn, result) {
                        self.$Input.value = result;
                        self.loadTypeName();

                        if ("createEvent" in document) {
                            var evt = document.createEvent("HTMLEvents");
                            evt.initEvent("change", false, true);
                            self.$Input.dispatchEvent(evt);
                        } else {
                            self.$Input.fireEvent("onchange");
                        }
                    }
                }
            }).inject(this.$Elm);

            this.$Text = new Element('div.qui-projects-type-input-text');
            this.$Text.inject(this.$Elm);

            if (this.$Input.hasClass('field-container-field')) {
                this.$Input.removeClass('field-container-field');
                this.$Elm.addClass('field-container-field');
                this.$TypeButton.getElm().inject(this.$Text, 'after');
            }

            // load the user type name
            this.loadTypeName();

            return this.$Elm;
        },

        /**
         * Set the project to the control
         *
         * @param {Object} Project - classes/projects/Project
         */
        setProject: function (Project) {
            this.setAttribute('project', Project.getName());

            if (this.$TypeButton) {
                this.$TypeButton.setAttribute('project', Project.getName());
            }
        },

        /**
         * disable the control
         */
        disable: function () {
            if (this.$TypeButton) {
                this.$TypeButton.disable();
            }
        },

        /**
         * enable the control
         */
        enable: function () {
            if (this.$TypeButton) {
                this.$TypeButton.enable();
            }
        },

        /**
         * Load the user-type-name to the control
         *
         * @method controls/projects/TypeInput#loadTypeName
         */
        loadTypeName: function () {
            const self = this;

            this.$Text.set({
                html : '<span class="fa fa-spinner fa-spin"></span>',
                title: '...'
            });

            let value = this.$Input.value;


            if (!value || value === '' || value === 'standard') {
                self.$Text.set({
                    html : QUILocale.get('quiqqer/core', 'site.type.standard'),
                    title: QUILocale.get('quiqqer/core', 'site.type.standard')
                });
                return;
            }

            Plugins.getTypeName(value, function (result) {
                if (self.$Text) {
                    self.$Text.set({
                        html : result,
                        title: result
                    });
                }
            }, {
                onError: function () {
                    if (self.$Text) {
                        self.$Text.set({
                            html : '<span style="color: red">#unknown</span>',
                            title: null
                        });
                    }
                }
            });
        }
    });
});
