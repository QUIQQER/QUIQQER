/**
 * System user profile
 *
 * @module controls/system/Profile
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/system/Profile', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'utils/Controls',
    'Ajax',
    'Locale',
    'Editors',

    'css!controls/system/Profile.css'

], function (QUI, QUIConfirm, FormUtils, ControlUtils, Ajax, Locale, Editors) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/system/Profile',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        options: {
            title        : Locale.get('quiqqer/system', 'profile'),
            icon         : 'fa fa-user',
            maxHeight    : 500,
            maxWidth     : 750,
            autoclose    : false,
            ok_button    : {
                text: Locale.get('quiqqer/system', 'save')
            },
            cancel_button: {
                text: Locale.get('quiqqer/system', 'cancel')
            }
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onOpen  : this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            var self    = this,
                Content = this.getContent();

            Content.set('html', '');

            this.Loader.show();

            Ajax.get([
                'ajax_users_get',
                'ajax_user_profileTemplate'
            ], function (data, profileTemplate) {
                if (!Content) {
                    return;
                }

                Content.set(
                    'html',
                    '<form class="qui-control-profil">' + profileTemplate + '</form>'
                );

                var Toolbars = Content.getElement('[name="toolbar"]');

                Editors.getToolbarsFromUser(USER.id).then(function (toolbars) {

                    for (var i = 0, len = toolbars.length; i < len; i++) {
                        new Element('option', {
                            value: toolbars[i],
                            html : toolbars[i].replace('\.xml', '')
                        }).inject(Toolbars);
                    }

                    FormUtils.setDataToForm(data, Content.getElement('form'));

                    return ControlUtils.parse(Content);
                }).then(function () {
                    return QUI.parse(Content);
                }).then(function () {
                    self.Loader.hide();
                });
            }, {
                uid: USER.id
            });
        },

        /**
         * event : on submit
         */
        $onSubmit: function () {
            this.Loader.show();

            var self    = this,
                Content = this.getContent(),
                Form    = Content.getElement('form');

            var data = FormUtils.getFormData(Form);

            Ajax.post('ajax_users_save', function (data) {
                // reload if lang not the current lang
                if (Locale.getCurrent() !== data.lang) {
                    window.location.reload();
                    return;
                }

                self.close();
            }, {
                uid       : USER.id,
                attributes: JSON.encode(data)
            });
        }
    });
});
