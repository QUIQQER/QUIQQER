/**
 * control utils - helper for all controls
 *
 * @module utils/Controls
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/lib/polyfills/Promise
 */

define('utils/Controls', ['qui/lib/polyfills/Promise'], function () {
    "use strict";

    return {
        /**
         * Parse an DOM Node Element
         *
         * Search all control elements in the node element
         * and parse it to the specific control
         *
         * @return Promise
         */
        parse: function (Elm, callback) {
            var Form = false;

            if (Elm.nodeName == 'FORM') {
                Form = Elm;
            }

            if (!Form) {
                Form = Elm.getElement('form');
            }

            if (Form) {
                // ist that good?
                Form.addEvent('submit', function (event) {
                    if (typeOf(event) === 'domevent') {
                        event.stop();
                    }
                });
            }

            var needles = [];

            // Button
            if (Elm.getElement('.btn-button')) {
                needles.push(this.parseButtons(Elm));
            }

            // Date
            if (Elm.getElement('input[type="date"],input[type="datetime"]')) {
                needles.push(this.parseDate(Elm));
            }

            // color
            if (Elm.getElement('input[type="color"]')) {
                needles.push(this.parseColor(Elm));
            }


            // Groups
            if (Elm.getElement('input.groups,input.group')) {
                needles.push(this.parseGroups(Elm));
            }

            // Media Types
            if (Elm.getElement('input.media-image,input.media-folder')) {
                needles.push(this.parseMediaInput(Elm));
            }

            // User And Groups
            if (Elm.getElement('input.users_and_groups')) {
                needles.push(this.parseUserAndGroups(Elm));
            }

            // User And Groups
            if (Elm.getElement('input.user')) {
                needles.push(this.parseUser(Elm));
            }

            // projects
            if (Elm.getElement('input.project')) {
                needles.push(this.parseProject(Elm));
            }

            // Project Types
            if (Elm.getElement('input.project-types')) {
                needles.push(this.parseProjectTypes(Elm));
            }

            // project site
            if (Elm.getElement('input.project-site')) {
                needles.push(this.parseProjectSite(Elm));
            }

            // data table
            if (Elm.getElement('.data-table')) {
                needles.push(this.parseDataTables(Elm));
            }

            if (!needles.length) {
                return Promise.resolve();
            }

            return new Promise(function (resolve, reject) {
                Promise.all(needles).then(function () {
                    if (typeof callback === 'function') {
                        callback();
                    }

                    resolve();

                }).catch(reject);
            });
        },

        /**
         * Search all Elements with .btn-button and convert it to a button
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseButtons: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['qui/controls/buttons/Button'], function (QUIButton) {
                    // buttons
                    var i, len, Child, elements;

                    elements = Elm.getElements('.btn-button');

                    for (i = 0, len = elements.length; i < len; i++) {
                        Child = elements[i];

                        new QUIButton({
                            text : Child.get('data-text'),
                            image: Child.get('data-image'),
                            click: Child.get('data-click')
                        }).inject(Child);
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         *
         * @param Elm
         * @returns {*}
         */
        parseColor: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['qui/controls/elements/ColorPicker'], function (QUIColorPicker) {

                    var i, len;
                    var elements = Elm.getElements('input[type="color"]');

                    for (i = 0, len = elements.length; i < len; i++) {
                        new QUIColorPicker().imports(elements[i]);
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Search all .data-tables and make it flexible
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseDataTables: function (Elm) {
            return new Promise(function (resolve) {
                var i, len, Header, TableText;
                var theaders = Elm.getElements('.data-table tr ^ th');

                var dataTableOpen = function () {
                    var Table  = this.getParent('table'),
                        TBody  = Table.getElement('tbody'),
                        Toggle = Table.getElement('.data-table-toggle');

                    Toggle.set('html', '<span class="fa fa-minus"></span>');

                    moofx(Table).animate({
                        height: Table.getScrollSize().y
                    }, {
                        equation: 'ease-out',
                        duration: 250,
                        callback: function () {
                            Table.setStyles({
                                display : null,
                                overflow: null
                            });

                            moofx(TBody).animate({
                                opacity: 1
                            }, {
                                duration: 250,
                                callback: function () {
                                    Table.removeClass('data-table-closed');
                                }
                            });
                        }
                    });
                };

                var dataTableClose = function () {
                    var Table  = this.getParent('table'),
                        THead  = Table.getElement('thead'),
                        TBody  = Table.getElement('tbody'),
                        Toggle = Table.getElement('.data-table-toggle');

                    Toggle.set('html', '<span class="fa fa-plus"></span>');
                    Table.addClass('data-table-closed');

                    moofx(TBody).animate({
                        opacity: 0
                    }, {
                        duration: 250,
                        callback: function () {
                            Table.setStyles({
                                display : 'block',
                                overflow: 'hidden'
                            });

                            if (!THead) {
                                return;
                            }

                            moofx(Table).animate({
                                height: THead.getSize().y
                            }, {
                                equation: 'ease-out',
                                duration: 250
                            });
                        }
                    });
                };

                var dataTableClick = function () {
                    var Table  = this.getParent('table'),
                        Toggle = Table.getElement('.data-table-toggle');

                    if (Toggle.getElement('.fa-minus')) {
                        dataTableClose.call(this);
                    } else {
                        dataTableOpen.call(this);
                    }
                };

                for (i = 0, len = theaders.length; i < len; i++) {
                    Header = theaders[i];

                    Header.addEvent('click', dataTableClick);
                    Header.setStyle('cursor', 'pointer');

                    TableText = new Element('div', {
                        'class': 'data-table-text',
                        html   : Header.get('html')
                    });

                    Header.set('html', '');

                    new Element('div', {
                        'class': 'data-table-toggle',
                        html   : '<span class="fa fa-minus"></span>',
                        styles : {}
                    }).inject(Header, 'top');

                    TableText.inject(Header);

                    if (Header.getParent('table').hasClass('data-table-closed')) {
                        dataTableClick.call(Header);
                    }
                }

                // hasCheckboxLabels
                var checkboxList = Elm.getElements(
                    '.data-table label [type="checkbox"]'
                );

                for (i = 0, len = checkboxList.length; i < len; i++) {
                    checkboxList[i].getParent('label').addClass('hasCheckbox');
                }


                resolve();
            });
        },

        /**
         * Search all input[type="date"] and make a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseDate: function (Elm) {
            return new Promise(function (resolve, reject) {
                require([

                    'package/quiqqer/calendar-controls/bin/Calendar',
                    'qui/controls/buttons/Button',
                    'qui/utils/Elements'

                ], function (DatePicker, QUIButton, ElementUtils) {
                    var i, len, htmlStr, elements, datetime, fieldcontainer,
                        Child, Cancel, Parent, Picker;

                    elements = Elm.getElements(
                        'input[type="date"],input[type="datetime"]'
                    );

                    // Date Buttons
                    for (i = 0, len = elements.length; i < len; i++) {
                        Child          = elements[i];
                        fieldcontainer = false;

                        if (Child.getParent().hasClass('field-container')) {
                            fieldcontainer = true;
                            Parent         = Child;
                        } else {
                            Parent = new Element('div', {
                                styles: {
                                    'float': 'left'
                                }
                            }).wraps(Child);
                        }

                        htmlStr = new Element('div').adopt(
                            Child.cloneNode(true)
                        ).innerHTML;

                        datetime = htmlStr.indexOf('datetime');

                        if (datetime) {
                            Child.placeholder = 'YYYY-MM-DD HH:MM:SS';
                            Child.set('data-type', 'datetime');

                        } else {
                            Child.placeholder = 'YYYY-MM-DD';
                            Child.set('data-type', 'date');
                        }


                        Child.autocomplete = 'off';

                        Child.setStyles({
                            float   : 'left',
                            cursor  : 'pointer',
                            maxWidth: 'calc(100% - 31px)',
                            width   : datetime ? 180 : 105
                        });

                        Picker = new DatePicker(Child, {
                            timePicker    : datetime ? true : false,
                            datetime      : datetime,
                            positionOffset: {
                                x: 5,
                                y: 0
                            },
                            pickerClass   : 'datepicker_dashboard',
                            onSelect      : function (UserDate, elmList) {
                                var i, len;

                                if (typeOf(elmList) === 'array') {
                                    for (i = 0, len = elmList.length; i < len; i++) {
                                        if (elmList[i].get('data-type') == 'date') {
                                            elmList[i].value = UserDate.format('%Y-%m-%d');
                                        } else {
                                            elmList[i].value = UserDate.format('db');
                                        }
                                    }

                                } else if (typeOf(elmList) === 'element') {
                                    if (elmList.get('data-type') == 'date') {
                                        elmList.value = UserDate.format('%Y-%m-%d');
                                    } else {
                                        elmList.value = UserDate.format('db');
                                    }
                                }
                            }
                        });

                        Picker.picker.setStyles({
                            zIndex: ElementUtils.getComputedZIndex(Child)
                        });

                        Cancel = new QUIButton({
                            image : 'fa fa-remove',
                            alt   : 'Datum leeren', // #locale
                            title : 'Datum leeren', // #locale
                            Input : Child,
                            events: {
                                onClick: function (Btn) {
                                    Btn.getAttribute('Input').value = '';
                                }
                            },
                            styles: {
                                top: 1
                            }
                        }).inject(Child.getParent());

                        if (fieldcontainer) {
                            Cancel.getElm()
                                .addClass('field-container-item')
                                .setStyles({
                                    border   : '1px solid rgba(147, 128, 108, 0.25)',
                                    boxShadow: 'none',
                                    top      : 0,
                                    width    : 50
                                });
                        }
                    }

                    resolve();

                }, function (err) {
                    require(['qui/QUI'], function (QUI) {
                        QUI.getMessageHandler(function (MH) {
                            // #locale
                            MH.addAttention(
                                'Das Kalender Paket konnte nicht gefunden werden.' +
                                'Bitte installieren Sie quiqqer/calendar'
                            );
                        });
                    });

                    reject(err);
                });
            });
        },

        /**
         * Search all input[class="groups"] and convert it to a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseGroups: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['controls/groups/Input'], function (GroupInput) {
                    var i, len, elements;

                    elements = Elm.getElements('input.groups,input.group');

                    for (i = 0, len = elements.length; i < len; i++) {
                        new GroupInput(null, elements[i]).create();
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Search all input[class="media-image"] and convert it to a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseMediaInput: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['controls/projects/project/media/Input'], function (ProjectMediaInput) {

                    var i, len,

                        mediaImages = Elm.getElements('input.media-image'),
                        mediaFolder = Elm.getElements('input.media-folder');

                    for (i = 0, len = mediaImages.length; i < len; i++) {
                        new ProjectMediaInput({
                            selectable_types: ['image', 'file'],
                            cssclasses      : mediaImages[i].get('data-qui-options-cssclasses')
                        }, mediaImages[i]).create();
                    }

                    for (i = 0, len = mediaFolder.length; i < len; i++) {
                        new ProjectMediaInput({
                            selectable_types: ['folder'],
                            cssclasses      : mediaFolder[i].get('data-qui-options-cssclasses')
                        }, mediaFolder[i]).create();
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Search all input[class="project"] and convert it to a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseProject: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['controls/projects/Input'], function (ProjectInput) {
                    var i, len, elements;

                    elements = Elm.getElements('input.project');

                    for (i = 0, len = elements.length; i < len; i++) {
                        new ProjectInput({
                            multible: false
                        }, elements[i]).create();
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Search all input[class="project-types"] and convert it to a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseProjectTypes: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['controls/projects/TypeInput'], function (TypeInput) {
                    var i, len, elements;

                    elements = Elm.getElements('input.project-types');

                    for (i = 0, len = elements.length; i < len; i++) {
                        new TypeInput(null, elements[i]).create();
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Search all input[class="project-site"] and convert it to a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseProjectSite: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['controls/projects/project/site/Input'], function (SiteInput) {
                    var i, len, elements;

                    elements = Elm.getElements('input.project-site');

                    for (i = 0, len = elements.length; i < len; i++) {
                        new SiteInput(null, elements[i]).create();
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Search all Elements with the class users_and_groups and convert it to a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseUserAndGroups: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['controls/usersAndGroups/Input'], function (UserAndGroup) {
                    var elements, Label, Control;

                    elements = Elm.getElements('.users_and_groups');

                    for (var i = 0, len = elements.length; i < len; i++) {
                        Control = new UserAndGroup(null, elements[i]);

                        if (elements[i].id) {
                            Label = document.getElement('label[for="' + elements[i].id + '"]');

                            if (Label) {
                                Control.setAttribute('label', Label);
                            }
                        }

                        Control.create();
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Search all Elements with the class user and convert it to a control
         *
         * @param {HTMLElement} Elm - parent node, this element in which is searched for
         * @return Promise
         */
        parseUser: function (Elm) {
            return new Promise(function (resolve, reject) {
                require(['controls/users/Input'], function (UserInput) {
                    var i, len, elements, Label, Control;

                    elements = Elm.getElements('.user');

                    for (i = 0, len = elements.length; i < len; i++) {
                        Control = new UserInput({
                            max: 1
                        }, elements[i]);

                        if (elements[i].id) {
                            Label = document.getElement('label[for="' + elements[i].id + '"]');

                            if (Label) {
                                Control.setAttribute('label', Label);
                            }
                        }

                        Control.create();
                    }

                    resolve();

                }, reject);
            });
        }
    };
});
