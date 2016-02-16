/**
 * @module controls/projects/project/media/CreateFolder
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 */
define('controls/projects/project/media/CreateFolder', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'controls/projects/Select',
    'controls/projects/project/media/Sitemap'

], function (QUI, QUIControl, QUILoader, ProjectSelect, MediaSitemap) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'controls/projects/project/media/CreateFolder',

        Binds: [
            '$onInject'
        ],

        options: {
            project: false
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = this.parent();

            this.$Elm.set({
                html  : '<div class="container-sheet"></div>',
                styles: {
                    height  : '100%',
                    position: 'relative',
                    wiidth  : '100%'
                }
            });

            this.$Container = this.$Elm.getElement('.container-sheet');


            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            if (this.getAttribute('project') === false) {
                this.showProjectList();
                return;
            }

            this.showNameInput();
        },

        /**
         * Show the project list
         *
         * @return {Promise}
         */
        showProjectList: function () {
            return this.hideContainer().then(function () {
                var self = this;

                this.$Container.set(
                    'html',
                    '<p>Bitte wählen Sie ein Project aus in dem der neue ' +
                    'Mediaordner erstellt werden soll.</p>'
                );

                new ProjectSelect({
                    langSelect: false,
                    styles    : {
                        'float': 'none',
                        display: 'block',
                        margin : '10px auto'
                    },
                    events    : {
                        onChange: function (value) {
                            if (value === '') {
                                return;
                            }

                            self.setAttribute('project', value);
                            self.showMediaSiteMap();
                        }
                    }
                }).inject(this.$Container);

                return this.showContaner();
            }.bind(this));
        },

        /**
         * Show the media folder sitemap
         *
         * @return {Promise}
         */
        showMediaSiteMap: function () {
            return this.hideContainer().then(function () {
                this.$Container.set(
                    'html',
                    '<p>Bitte wählen Sie den Elternordner aus.</p>'
                );

                var Map = new MediaSitemap({
                    project: this.getAttribute('project')
                }).inject(this.$Container);

                return this.showContaner();
            }.bind(this));
        },

        /**
         * Show the input for the new name
         *
         * @return {Promise}
         */
        showNameInput: function () {

        },

        /**
         * Hide the container -> FX
         *
         * @returns {Promise}
         */
        hideContainer: function () {
            return new Promise(function (resolve) {
                moofx(this.$Container).animate({
                    opacity: 0,
                    top    : -20
                }, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Show the container -> FX
         *
         * @returns {Promise}
         */
        showContaner: function () {
            return new Promise(function (resolve) {
                moofx(this.$Container).animate({
                    opacity: 1,
                    top    : 0
                }, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        }
    });
});
