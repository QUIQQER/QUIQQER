
/**
 * The type input set the type control to an input field
 *
 * @module controls/projects/TypeInput
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require controls/projects/TypeButton
 * @require controls/projects/TypeWindow
 * @require Plugins
 */
define('controls/projects/TypeInput', [

    'qui/controls/Control',
    'controls/projects/TypeButton',
    'controls/projects/TypeWindow',
    'Plugins',
    'css!controls/projects/TypeInput.css'

], function (QUIControl, TypeButton, TypeWindow, Plugins) {
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

        Extends : QUIControl,
        Type    : 'controls/projects/TypeInput',

        options : {
            project : false,
            name    : ''
        },

        initialize : function (options, Input) {
            this.parent(options);

            this.$Input = Input || null;
            this.$Elm   = null;
            this.$Text  = null;

            this.$TypeButton = null;
        },

        /**
         * Create the button and the type field
         *
         * @method controls/projects/TypeInput#create
         * @return {HTMLElement}
         */
        create : function () {
            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name : this.getAttribute('name')
                });
            }

            var self = this;

            this.$Input.type = 'hidden';

            this.$Elm = new Element('div', {
                'class'      : 'qui-projects-type-input',
                'data-quiid' : this.getId()
            });

            this.$Elm.wraps(this.$Input);

            // create the type button
            this.$TypeButton = new TypeButton({
                project : this.getAttribute('project'),
                events  :
                {
                    onSubmit : function (Btn, result) {
                        self.$Input.value = result;
                        self.loadTypeName();
                    }
                }
            }).inject(this.$Elm) ;


            this.$Text = new Element('div.qui-projects-type-input-text');
            this.$Text.inject(this.$Elm);

            // load the user type name
            this.loadTypeName();

            return this.$Elm;
        },

        /**
         * Set the project to the control
         *
         * @param {Object} Project - classes/projects/Project
         */
        setProject : function (Project) {
            this.setAttribute('project', Project.getName());

            if (this.$TypeButton) {
                this.$TypeButton.setAttribute('project', Project.getName());
            }
        },

        /**
         * Load the user-type-name to the control
         *
         * @method controls/projects/TypeInput#loadTypeName
         */
        loadTypeName : function () {
            var self = this;

            this.$Text.set({
                html  : '<span class="fa fa-spinner fa-spin"></span>',
                title : '...'
            });

            Plugins.getTypeName(this.$Input.value, function (result) {
                if (self.$Text) {
                    self.$Text.set({
                        html  : result,
                        title : result
                    });
                }
            }, {
                onError : function () {
                    if (self.$Text) {
                        self.$Text.set({
                            html  : '<span style="color: red">#unknown</span>',
                            title : null
                        });
                    }
                }
            });
        }
    });
});
