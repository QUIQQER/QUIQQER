/**
 * Select a site input field
 *
 * @module controls/projects/project/site/Input
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Patrick Müller)
 *
 * @event onSelect [url, this] - fires if the user selects a site
 * @event onRemove [this] - fires if the user removes the selected site
 */
define('controls/projects/project/site/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/projects/Popup',

    'css!controls/projects/project/site/Input.css'

], function (QUIControl, QUIButton, ProjectPopup) {
    "use strict";

    /**
     * @class controls/projects/Input
     *
     * @param {Object} options
     * @param {HTMLElement} [Input] - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/site/Input',

        Binds: [
            '$onCreate',
            '$onImport'
        ],

        options: {
            name    : '',
            styles  : false,
            external: false // external sites allowed?
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input = Input || null;
            this.$SiteButton = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Create the DOMNode
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class'     : 'qui-controls-project-site-input box',
                'data-quiid': this.getId()
            });

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name: this.getAttribute('name')
                }).inject(this.$Elm);
            } else {
                this.$Elm.wraps(this.$Input);

                if (this.$Input.get('data-external')) {
                    this.setAttribute('external', true);
                }
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Input.set('data-quiid', this.getId());

            this.$Input.setStyles({
                'float': 'left'
            });

            if (!this.getAttribute('external')) {
                this.$Input.setStyle('cursor', 'pointer');
            }

            var self = this;

            this.$SiteButton = new QUIButton({
                icon  : 'fa fa-file-o',
                events: {
                    onClick: function () {
                        new ProjectPopup({
                            events: {
                                onSubmit: function (Popup, params) {
                                    self.$Input.value = params.urls[0];
                                    self.fireEvent('select', [params.urls[0], self]);

                                    if ("createEvent" in document) {
                                        var evt = document.createEvent("HTMLEvents");
                                        evt.initEvent("change", false, true);
                                        self.$Input.dispatchEvent(evt);
                                    } else {
                                        self.$Input.fireEvent("onchange");
                                    }
                                }
                            }
                        }).open();
                    }
                }
            }).inject(this.$Elm);

            new QUIButton({
                icon  : 'fa fa-remove',
                alt   : Locale.get('quiqqer/system', 'projects.project.site.input.clear'),
                title : Locale.get('quiqqer/system', 'projects.project.site.input.clear'),
                events: {
                    onClick: function () {
                        self.$Input.value = '';
                        self.fireEvent('remove', [self]);

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


            if (!self.getAttribute('external')) {
                this.$Input.addEvents({
                    focus: function () {
                        self.$SiteButton.click();
                    }
                });
            }

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.$Input = this.$Elm;
            this.create();
        }
    });
});
