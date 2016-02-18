/**
 * Media-Settings for a project
 *
 * @module controls/projects/project/settings/Media
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/projects/project/settings/Media', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',
    'qui/utils/Form',
    'utils/Template',
    'utils/Controls',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUIButton, QUILoader, QUIFormUtils, UtilsTemplate, UtilsControls, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/settings/Media',

        Binds: [
            '$onInject'
        ],

        options: {
            Project: false,
            config : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Project = this.getAttribute('Project');

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm   = this.parent();
            this.Loader = new QUILoader().inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.Loader.show();

            UtilsTemplate.get('project/settingsMedia', function (result) {
                var Form;
                var Elm = self.getElm();

                Elm.set('html', result);

                Form = Elm.getElement('Form');

                if (self.$Project) {
                    for (var i = 0, len = Form.elements.length; i < len; i++) {
                        Form.elements[i].set('data-project', self.$Project.getName());
                    }
                }

                QUIFormUtils.setDataToForm(self.getAttribute('config'), Form);

                new QUIButton({
                    text     : QUILocale.get(lg, 'projects.project.site.media.manager.calcmd5.start.text'),
                    alt      : QUILocale.get(lg, 'projects.project.site.media.manager.calcmd5.start.alt'),
                    textimage: 'fa fa-picture-o',
                    events   : {
                        onClick: function (Btn) {
                            Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                            self.calcMD5(function () {
                                Btn.setAttribute('textimage', 'fa fa-picture-o');
                            });
                        }
                    }
                }).inject(Elm.getElement('.md5hash'));

                new QUIButton({
                    text     : QUILocale.get(lg, 'projects.project.site.media.manager.calcsha1.start.text'),
                    alt      : QUILocale.get(lg, 'projects.project.site.media.manager.calcsha1.start.alt'),
                    textimage: 'fa fa-picture-o',
                    events   : {
                        onClick: function (Btn) {
                            Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                            self.calcSHA1(function () {
                                Btn.setAttribute('textimage', 'fa fa-picture-o');
                            });
                        }
                    }
                }).inject(Elm.getElement('.sha1hash'));


                UtilsControls.parse(Form, function () {
                    self.Loader.hide();
                    self.fireEvent('load');
                });
            });
        },

        /**
         * Set the project
         *
         * @param {Object} Project - classes/projects/Project
         */
        setProject: function (Project) {
            this.$Project = Project;

            var Form = this.getElm().getElement('form');

            if (Form) {
                for (var i = 0, len = Form.elements.length; i < len; i++) {
                    Form.elements[i].set('data-project', this.$Project.getName());
                }
            }
        },

        /**
         * Starts the MD5 calculation for the specific media
         *
         * @param {Function} oncomplete
         */
        calcMD5: function (oncomplete) {
            QUIAjax.post('ajax_media_create_md5', oncomplete, {
                project: this.$Project.encode()
            });
        },

        /**
         * Starts the SHA1 calculation for the specific media
         *
         * @param {Function} oncomplete
         */
        calcSHA1: function (oncomplete) {
            QUIAjax.post('ajax_media_create_sha1', oncomplete, {
                project: this.$Project.encode()
            });
        }
    });
});
