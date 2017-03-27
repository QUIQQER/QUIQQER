/**
 * Parent class for all media items like file, image, folder
 *
 * @module classes/projects/project/media/Item
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onRefresh [ {self} ]
 * @event onSave [ {self} ]
 * @event onDelete [ {self} ]
 * @event onActivate [ {self} ]
 * @event onDeactivate [ {self} ]
 * @event onRename [ {self} ]
 *
 * @require qui/classes/DOM
 * @require Ajax
 * @require qui/utils/Object
 */

define('classes/projects/project/media/Item', [

    'qui/classes/DOM',
    'Ajax',
    'qui/utils/Object'

], function (DOM, Ajax, Utils) {
    "use strict";

    /**
     * @class classes/projects/project/media/Item
     *
     * @event onSave [this]
     * @event onDelete [this]
     * @event onActivate [this]
     * @event onDeactivate [this]
     *
     * @param {Object} Media (classes/projects/project/Media)
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: DOM,
        Type   : 'classes/projects/project/media/Item',

        options: {
            id      : 0,
            name    : '',
            title   : '',
            alt     : '',
            'short' : '',
            active  : '',
            order   : '',
            priority: '',
            file    : '',

            c_date    : '',
            c_user    : '',
            c_username: '',

            e_date    : '',
            e_user    : '',
            e_username: '',

            watermark   : false,
            roundcorners: false,

            mime_type   : '',
            image_height: '',
            image_width : '',
            cache_url   : ''
        },

        initialize: function (params, Media) {
            this.$Media   = Media;
            this.$Panel   = null;
            this.$effects = null;

            this.parent(params);
        },

        /**
         * Refresh the params from the database to the file
         *
         * @param {Function} oncomplete - (optional) callback function
         * @return {Promise}
         */
        refresh: function (oncomplete) {
            var self = this;

            return this.getMedia().getData(this.getId()).then(function (result) {
                self.setAttributes(result);
                self.fireEvent('refresh', [self]);

                if (typeOf(oncomplete) === 'function') {
                    oncomplete(self);
                }
            });
        },

        /**
         * Returns the Media Object of the item
         *
         * @method classes/projects/project/media/Item#getMedia
         * @return {Object} classes/projects/project/Media
         */
        getMedia: function () {
            return this.$Media;
        },

        /**
         * Returns the ID of the item
         *
         * @method classes/projects/project/media/Item#getId
         * @return {Number}
         */
        getId: function () {
            return this.getAttribute('id');
        },

        /**
         * Return the image.php path
         *
         * @return {String}
         */
        getUrl: function () {
            var project = this.getMedia().getProject().getName(),
                id      = this.getId();

            return 'image.php?project=' + project + '&id=' + id;
        },

        /**
         * Return the ID of the Parent
         *
         * @method classes/projects/project/media/Item#getParentId
         * @returns {Promise}
         */
        getParentId: function () {
            return new Promise(function (resolve, reject) {

                Ajax.get('ajax_media_file_getParentId', resolve, {
                    project: this.getMedia().getProject().getName(),
                    fileid : this.getId(),
                    onError: reject
                });

            }.bind(this));
        },

        /**
         * Return the Parent Object
         *
         * @method classes/projects/project/media/Item#getParent
         * @returns {Promise}
         */
        getParent: function () {
            return this.getParentId().then(function (parentId) {
                return this.getMedia().get(parentId);
            }.bind(this));
        },

        /**
         * Returns the breadcrumb entries (parent path)
         *
         * @method classes/projects/project/media/Item#getBreadcrumb
         * @param {Function} [oncomplete] - callback Function
         *
         * @return Promise
         */
        getBreadcrumb: function (oncomplete) {
            return new Promise(function (resolve, reject) {

                Ajax.get('ajax_media_breadcrumb', function (result) {
                    if (typeof oncomplete == 'function') {
                        oncomplete(result);
                    }

                    resolve(result);
                }, {
                    project: this.getMedia().getProject().getName(),
                    fileid : this.getId(),
                    onError: reject
                });

            }.bind(this));
        },

        /**
         * Save the File attributes to the database
         *
         * @method classes/projects/project/media/Item#save
         *
         * @fires onSave [this]
         *
         * @param {Function} [oncomplete] - (optional) callback Function
         * @param {Object} [params]       - (optional), parameters that are linked to the request object
         *
         * @return Promise
         */
        save: function (oncomplete, params) {
            var self = this;

            return new Promise(function (resolve, reject) {

                var attributes = self.getAttributes();

                attributes.image_effects = self.getEffects();

                params = Utils.combine(params, {
                    project   : self.getMedia().getProject().getName(),
                    fileid    : self.getId(),
                    attributes: JSON.encode(self.getAttributes()),
                    onError   : reject
                });


                Ajax.post('ajax_media_file_save', function (result) {
                    self.setAttributes(result);
                    self.fireEvent('save', [self]);

                    if (typeOf(oncomplete) === 'function') {
                        oncomplete(result);
                    }

                    resolve(result);

                }, params);
            });
        },

        /**
         * Delete the item
         *
         * @method classes/projects/project/media/Item#del
         *
         * @fires onDelete [this]
         *
         * @param {Function} [oncomplete] - (optional) callback Function
         * @param {Object} [params]       - (optional), parameters that are linked to the request object
         */
        del: function (oncomplete, params) {
            this.fireEvent('delete', [this]);
            this.getMedia().del(this.getId(), oncomplete, params);
        },

        /**
         * Activate the File
         *
         * @method classes/projects/project/media/Item#activate
         *
         * @fires onActivate [this]
         *
         * @param {Function} [oncomplete] - (optional) callback Function
         * @param {Object} [params]      - (optional), parameters that are linked to the request object
         *
         * @return Promise
         */
        activate: function (oncomplete, params) {
            var Media  = this.getMedia(),
                Result = Media.activate(this.getId(), oncomplete, params);

            return Result.then(function (result) {
                this.setAttribute('active', result);
                this.fireEvent('activate', [this]);
            }.bind(this));
        },

        /**
         * Deactivate the File
         *
         * @method classes/projects/project/media/Item#deactivate
         *
         * @fires onDeactivate [this]
         *
         * @param {Function} [oncomplete] - (optional) callback Function
         * @param {Object} [params]      - (optional), parameters that are linked to the request object
         *
         * @return Promise
         */
        deactivate: function (oncomplete, params) {
            var Media  = this.getMedia(),
                Result = Media.deactivate(this.getId(), oncomplete, params);

            return Result.then(function (result) {
                this.setAttribute('active', result);
                this.fireEvent('deactivate', [this]);
            }.bind(this));
        },

        /**
         * Download the image
         *
         * @method classes/projects/project/media/Item#download
         */
        download: function () {
            if (this.getType() === 'classes/projects/project/media/Folder') {
                return;
            }

            var url = Ajax.$url + '?' + Ajax.parseParams('ajax_media_file_download', {
                    project: this.getMedia().getProject().getName(),
                    fileid : this.getId()
                });

            // create a iframe
            if (!document.id('download-frame')) {
                new Element('iframe#download-frame', {
                    styles: {
                        position: 'absolute',
                        width   : 100,
                        height  : 100,
                        left    : -400,
                        top     : -400
                    }
                }).inject(document.body);
            }

            document.id('download-frame').set('src', url);
        },

        /**
         * Replace the file
         *
         * @method classes/projects/project/media/Item#download
         *
         * @param {File} File
         * @param {Function} onfinish - callback function after the upload is finish
         *                              onfinish( {classes/projects/project/media/Item} )
         *
         * @return Promise
         */
        replace: function (File, onfinish) {
            return this.$Media.replace(this.getId(), onfinish);
        },

        /**
         * Returns if the File is active or not
         *
         * @method classes/projects/project/media/Item#isActive
         * @return {Boolean}
         */
        isActive: function () {
            var active = this.getAttribute('active');

            if (typeOf(active) === 'boolean') {
                return active;
            }

            return !!parseInt(active);
        },

        /**
         * Rename the folder
         *
         * @method classes/projects/project/media/Folder#rename
         *
         * @param {String} newname        - New folder name
         * @param {Function} [oncomplete] - callback() function
         * @param {Object} [params]       - (optional), parameters that are linked to the request object
         *
         * @return Promise
         */
        rename: function (newname, oncomplete, params) {
            return new Promise(function (resolve, reject) {

                params = Utils.combine(params, {
                    project: this.getMedia().getProject().getName(),
                    id     : this.getId(),
                    newname: newname,
                    onError: reject
                });

                Ajax.post('ajax_media_rename', function (result) {
                    this.setAttribute('name', result);

                    if (typeof oncomplete === 'function') {
                        oncomplete(result);
                    }

                    resolve(result);

                    this.fireEvent('rename', [this]);

                }.bind(this), params);

            }.bind(this));
        },

        /**
         * Return the own image effects for the immage
         * @returns {Object}
         */
        getEffects: function () {
            if (this.$effects) {
                return this.$effects;
            }

            if (!this.getAttribute('image_effects')) {
                this.$effects = {};
                return this.$effects;
            }

            this.$effects = JSON.decode(this.getAttribute('image_effects'));

            if (!this.$effects) {
                this.$effects = {};
            }

            return this.$effects;
        },

        /**
         * Get a effect value
         *
         * @param {String} effect
         */
        getEffect: function (effect) {
            var effects = this.getEffects();

            return effect in effects ? effects[effect] : false;
        },

        /**
         * Set a effect
         *
         * @param {String} effect
         * @param {String|Number|null} value - if value is null, effect would be deleted
         */
        setEffect: function (effect, value) {
            this.getEffects();

            if (value === null) {
                delete this.$effects[effect];
                return;
            }

            if (typeOf(this.$effects) !== 'object') {
                this.$effects = {};
            }

            this.$effects[effect] = value;
        }
    });
});
