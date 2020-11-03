/**
 * Makes a group input field to a field selection field
 *
 * @module controls/projects/project/media/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onAddGroup [ this, id ]
 * @event onChange [ this ]
 */
define('controls/projects/project/media/Select', [

    'qui/QUI',
    'qui/controls/elements/Select',
    'Locale',
    'Projects',

    'css!controls/projects/project/media/Select.css'

], function (QUIControl, QUIElementSelect, QUILocale, Projects) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    /**
     * @class controls/projects/project/media/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIElementSelect,
        Type   : 'controls/projects/project/media/Select',

        Binds: [
            '$onSearchButtonClick',
            'mediaSearch'
        ],

        options: {
            project: false
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('Search', this.mediaSearch);
            this.setAttribute('icon', 'fa fa-picture-o');
            this.setAttribute('child', 'controls/projects/project/media/Entry');

            if (this.getAttribute('project')) {
                this.$Project = Projects.get(this.getAttribute('project'));
            } else {
                this.$Project = Projects.get(Projects.Standard.getName());
            }

            this.$Media = null;

            this.setAttribute(
                'placeholder',
                QUILocale.get(lg, 'control.media.select.search.field.placeholder')
            );

            this.addEvents({
                onSearchButtonClick: this.$onSearchButtonClick
            });
        },

        /**
         * Return the media object
         *
         * @return {Object}
         */
        getMedia: function () {
            return this.$Project.getMedia();
        },

        /**
         * Execute the search
         *
         * @param {String} value
         * @returns {Promise}
         */
        mediaSearch: function (value) {
            var self = this;

            return new Promise(function (resolve) {
                self.getMedia().search(value, {
                    order: 'ASC',
                    limit: 5
                }).then(function (result) {
                    var data = [];

                    for (var i = 0, len = result.data.length; i < len; i++) {
                        data.push({
                            id   : result.data[i].id,
                            title: result.data[i].name,
                            icon : 'fa fa-image-o'
                        });
                    }

                    resolve(data);
                });
            });
        },

        /**
         * event : on search click
         *
         * @param {Object} Select
         * @param {Object} Btn
         */
        $onSearchButtonClick: function (Select, Btn) {
            var self    = this,
                oldIcon = Btn.getAttribute('icon');

            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');
            Btn.disable();

            require(['controls/projects/project/media/Popup'], function (Window) {
                new Window({
                    autoclose: true,
                    events   : {
                        onSubmit: function (Win, mediaFile) {
                            self.addItem(mediaFile.id);
                        }
                    }
                }).open();

                Btn.setAttribute('icon', oldIcon);
                Btn.enable();
            });
        }
    });
});
