define('controls/users/attribute/VerifiableUserAttribute', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',

    'css!controls/users/attribute/VerifiableUserAttribute.css'

], function (QUI, QUIControl, QUIAjax) {
    'use strict';

    const VERIFIED = 'VERIFIED';
    const UNVERIFIED = 'UNVERIFIED';

    return new Class({

        Type: 'controls/users/attribute/VerifiableUserAttribute',
        Extends: QUIControl,

        Binds: [
            '$onInject',
            '$onImport',
            '$changeStatus'
        ],

        options: {
            uid: null, // uuid of the current user
            value: null, // attribute value / optional, if null, you have to use input
            input: null, // attribute value input / optional, if null, you have to use value
            attribute: null, // attribute name
            attributeUuid: null, // optional, uuid of the verifiable attribute
            attributeType: null,
            status: null, // status / optional, if null, status will be fetched
            changeable: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Checkbox = null;
            this.$Display = null;
            this.$loaded = false;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        $onImport: function () {
        },

        $onInject: function () {
            const node = this.getElm();
            const prevSibling = node.getPrevious();

            node.classList.add('attribute-verification');

            if (prevSibling && prevSibling.tagName === 'LABEL' && prevSibling.classList.contains('field-container')) {
                node.style.paddingLeft = '210px';
                node.classList.add('attribute-verification-label');
            }

            if (node.getAttribute('data-qui-options-attribute')) {
                this.setAttribute('attribute', node.getAttribute('data-qui-options-attribute'));
            }

            if (node.getAttribute('data-qui-options-input')) {
                this.setAttribute('input', node.getAttribute('data-qui-options-input'));
            }

            if (node.getAttribute('data-qui-options-attribute-type')) {
                this.setAttribute('attributeType', node.getAttribute('data-qui-options-attribute-type'));
            }

            this.getData().then(() => {
                node.set(
                    'html',

                    '<label>' +
                    '   <input type="checkbox"/>' +
                    '   <span></span>' +
                    '</label>'
                );

                this.$Display = node.querySelector('span');

                this.$Checkbox = node.querySelector('input');
                this.$Checkbox.addEventListener('change', this.$changeStatus.bind(this));

                if (
                    node.getAttribute('data-option-value') === VERIFIED
                    || this.getAttribute('status') === VERIFIED
                ) {
                    this.$Checkbox.checked = true;
                }

                this.$changeStatus();
                this.$loaded = true;
            });
        },

        getValue: function () {
            if (this.getAttribute('value')) {
                return this.getAttribute('value');
            }

            if (this.$Input) {
                return this.$Input.value;
            }

            if (this.getAttribute('input')) {
                const form = this.getElm().getParent('form');
                const input = this.getAttribute('input');

                if (form && form.querySelector('[name="' + input + '"]')) {
                    this.$Input = form.querySelector('[name="' + input + '"]');
                    return this.$Input.value;
                }
            }

            return '';
        },

        getData: function () {
            return new Promise((resolve) => {
                QUIAjax.get('ajax_users_attribute_getVerifiedAttribute', (data) => {
                    console.log(data);

                    if (data && typeof data.verification_status !== 'undefined') {
                        this.setAttribute('status', data.verification_status);
                    }

                    resolve();
                }, {
                    'package': 'quiqqer/core',
                    userUuid: this.getAttribute('uid'),
                    value: this.getValue(),
                    type: this.getAttribute('attributeType')
                });
            });
        },

        $changeStatus: function () {
            if (!this.getAttribute('changeable')) {
                return;
            }

            if (this.$Checkbox.checked) {
                this.getElm().classList.remove('attribute-verification--unverified');
                this.getElm().classList.add('attribute-verification--verified');
                this.$Display.set('html', 'verifiziert');
                this.setAttribute('status', VERIFIED);
            } else {
                this.getElm().classList.remove('attribute-verification--verified');
                this.getElm().classList.add('attribute-verification--unverified');
                this.$Display.set('html', 'unverifiziert');
                this.setAttribute('status', UNVERIFIED);
            }

            if (this.$loaded) {
                this.save();
            }
        },

        save: function () {
            if (!this.getAttribute('changeable')) {
                return Promise.resolve();
            }

            if (!this.getAttribute('uid')) {
                QUI.getMessageHandler().then(function (MessageHandler) {
                    MessageHandler.addError('Missing user id at VerifiableUserAttribute');
                });

                return Promise.resolve();
            }

            return new Promise((resolve, reject) => {
                QUIAjax.post('ajax_users_attribute_setVerifiableAttribute', (data) => {
                    if (data && typeof data.verification_status !== 'undefined') {
                        this.setAttribute('status', data.verification_status);
                    }

                    resolve();
                }, {
                    userUuid: this.getAttribute('uid'),
                    value: this.getValue(),
                    type: this.getAttribute('attributeType'),
                    status: this.getAttribute('status'),
                    onError: reject
                });
            });
        }
    });
});
