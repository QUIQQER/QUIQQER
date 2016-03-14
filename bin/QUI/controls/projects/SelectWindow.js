/**
 * Projects Select Popup
 *
 * In this Popup you can select a project
 *
 * @module controls/projects/SelectWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Confirm
 * @require controls/projects/Select
 * @require Locale
 *
 * @event onSubmit [ {this}, {Object} result ];
 */
define('controls/projects/SelectWindow', [

    'qui/controls/windows/Confirm',
    'controls/projects/Select',
    'Locale'

], function (QUIConfirm, ProjectSelect, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/projects/SelectWindow',

        options: {
            maxWidth  : 450,
            maxHeight : 300,
            langSelect: true
        },

        Binds: [
            '$onOpen'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Select = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         * create the content
         */
        $onOpen: function () {
            this.setAttributes({
                title: QUILocale.get('quiqqer/system', 'projects.project.windowselect.title'),
                icon : 'fa fa-home'
            });

            this.refresh();

            var Content = this.getContent();

            Content.set('html', '');

            new Element('div', {
                html: QUILocale.get('quiqqer/system', 'projects.project.windowselect.text')
            }).inject(Content);

            this.$Select = new ProjectSelect({
                langSelect: this.getAttribute('langSelect'),
                styles    : {
                    'float': 'none',
                    margin : '20px auto 0'
                }
            }).inject(Content);
        },

        /**
         * Submit the window
         */
        submit: function () {
            if (!this.$Select) {
                if (this.getAttribute('autoclose')) {
                    this.close();
                }

                return;
            }

            var value = this.$Select.getValue().split(',');

            var result = {
                project: value[0],
                lang   : value[1]
            };

            this.fireEvent('submit', [this, result]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
