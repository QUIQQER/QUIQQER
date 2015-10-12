
/**
 * Media for a Project
 *
 * @module classes/projects/project/Media
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require qui/utils/Object
 * @require Ajax
 * @require classes/projects/project/media/Image
 * @require classes/projects/project/media/File
 * @require classes/projects/project/media/Folder
 * @require classes/projects/project/media/Trash
 *
 * @event onItemRename [ self, Item ]
 * @event onItemRefresh [ self, Item ]
 * @event onItemSave [ self, Item ]
 * @event onItemDelete [ self, Item ]
 * @event onItemActivate [ self, Item ]
 * @event onItemDeactivate [ self, Item ]
 * @event onItemRename [ self, Item ]
 */

define('classes/projects/project/Media', [

    'qui/classes/DOM',
    'qui/utils/Object',

    'Ajax',
    'classes/projects/project/media/Image',
    'classes/projects/project/media/File',
    'classes/projects/project/media/Folder',
    'classes/projects/project/media/Trash'

], function(DOM, ObjectUtils, Ajax, MediaImage, MediaFile, MediaFolder, MediaTrash)
{
    "use strict";

    /**
     * @class classes/projects/project/Media
     *
     * @param {Object} Project - classes/projects/Project
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/projects/project/Media',

        initialize : function(Project)
        {
            this.$Project = Project;
            this.$Panel   = null;
            this.$items   = {};
        },

        /**
         * Return the Project from the Media
         *
         * @method classes/projects/project/Media#getProject
         * @return {Object} classes/projects/Project
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Return the Trash from the Media
         *
         * @method classes/projects/project/Media#getTrash
         * @return {Object} classes/projects/project/media/Trash
         */
        getTrash : function()
        {
            return new MediaTrash( this );
        },

        /**
         * Get a file object from the media
         *
         * @method classes/projects/project/Media#get
         *
         * @param {Number|Array} id       - ID of the file or an id list
         * @param {Function|Array} params - Item params or a callback function
         *
         * @return {Object} Promise
         */
        get : function(id, params)
        {
            var self = this;

            return new Promise(function(resolve, reject)
            {
                // id list
                if (typeOf(id) == 'array')
                {
                    var i, len, itemId;
                    var result = [];

                    for (i = 0, len = id.length; i < len; i++)
                    {
                        itemId = id[i];

                        if (self.$items[itemId]) {
                            result.push(self.$items[itemId]);
                        }
                    }

                    if (result.length === len)
                    {
                        if (typeOf(params) === 'function') {
                            params(result);
                        }

                        resolve(result);
                        return;
                    }
                }

                // one id
                if (id in self.$items)
                {
                    if (typeOf(params) === 'function') {
                        params(self.$items[id]);
                    }

                    resolve(self.$items[id]);
                    return;
                }

                if (typeOf(params) === 'object') {
                    resolve(self.$parseResultToItem(params));
                    return;
                }

                Ajax.get('ajax_media_details', function(result)
                {
                    var children = self.$parseResultToItem(result);

                    if (typeOf(children) == 'array')
                    {
                        for (var i = 0, len = children.length; i < len; i++) {
                            self.$items[children[i].getId()] = children[i];
                        }

                    } else
                    {
                        self.$items[children.getId()] = children;
                    }

                    if (typeOf(params) === 'function') {
                        params(children);
                    }

                    resolve(children);

                }, {
                    fileid  : JSON.encode(id),
                    project : self.getProject().getName(),
                    onError : function(Exception)
                    {
                        reject( Exception );
                    }
                });
            });
        },

        /**
         * Return thr file / files array, not the objects
         *
         * @method classes/projects/project/Media#getData
         *
         * @param {Number|Array} id  - ID of the file or an id list
         * @param {Function} [onfinish] - (optional), callback function
         *
         * @return {Object} Promise
         */
        getData : function(id, onfinish)
        {
            var self = this;

            return new Promise(function(resolve, reject)
            {
                Ajax.get('ajax_media_details', function(result)
                {
                    if (typeOf(onfinish) === 'function') {
                        onfinish(result);
                    }

                    resolve(result);
                }, {
                    fileid  : JSON.encode(id),
                    project : self.getProject().getName(),
                    onError : function(Exception)
                    {
                        reject( Exception );
                    }
                });
            });
        },

        /**
         * get the first child of the media
         *
         * @method classes/projects/project/Media#get
         * @return {Object} Promise
         */
        firstChild : function(callback)
        {
            return this.get(1, callback);
        },

        /**
         * Replace the file
         *
         * @method classes/projects/project/Media#download
         *
         * @param {Number} childid   - the Mediafile ID
         * @param {File} File         - Browser File Object
         * @param {Function} onfinish - callback function after the upload is finish
         *                              onfinish( {controls/upload/File} )
         *
         * @return Promise
         */
        replace : function(childid, File, onfinish)
        {
            return new Promise(function(resolve, reject)
            {
                // upload file
                require(['UploadManager'], function(UploadManager)
                {
                    UploadManager.uploadFiles([File], 'ajax_media_replace', {
                        project    : this.getProject().getName(),
                        fileid     : childid,
                        phponstart : 'ajax_media_checkreplace',
                        events     : {
                            onComplete : function() {
                                if (typeof onfinish === 'function') {
                                    onfinish();
                                }
                                resolve();
                            }
                        }
                    });
                }.bind(this), reject);

            }.bind(this));
        },

        /**
         * Activate one ore more items
         *
         * @method classes/projects/project/Media#activate
         *
         * @param {Number|Array} id       - Item list or an Item id
         * @param {Function} [oncomplete] - (optional), callback Function
         * @param {Object} [params]       - (optional), parameters that are linked to the request object
         *
         * @return Promise
         */
        activate : function(id, oncomplete, params)
        {
            return new Promise(function(resolve, reject)
            {
                params = ObjectUtils.combine(params, {
                    project : this.getProject().getName(),
                    fileid  : JSON.encode( id ),
                    onError : reject
                });

                Ajax.post('ajax_media_activate', function(result)
                {
                    if (typeOf(id) !== 'array') {
                        if (typeof this.$items[id] !== 'undefined') {
                            this.$items[id].setAttribute('active', result);
                        }
                    } else
                    {
                        id.each(function(id) {
                            if (id in this.$items) {
                                this.$items[id].setAttribute('active', result[id]);
                            }
                        }.bind(this));
                    }

                    if (typeof oncomplete === 'function') {
                        oncomplete(result);
                    }

                    resolve(result);

                }.bind(this), params);
            }.bind(this));
        },

        /**
         * Deactivate one ore more items
         *
         * @method classes/projects/project/Media#deactivate
         *
         * @param {Number|Array} id       - Item list or an Item id
         * @param {Function} [oncomplete] - (optional), callback Function
         * @param {Object} [params]       - (optional), parameters that are linked to the request object
         *
         * @return Promise
         */
        deactivate : function(id, oncomplete, params)
        {
            return new Promise(function(resolve, reject)
            {
                params = ObjectUtils.combine(params, {
                    project : this.getProject().getName(),
                    fileid  : JSON.encode(id),
                    onError : reject
                });

                Ajax.post('ajax_media_deactivate', function (result)
                {
                    if (typeOf(id) !== 'array') {
                        if (typeof this.$items[id] !== 'undefined') {
                            this.$items[id].setAttribute('active', result);
                        }
                    } else
                    {
                        id.each(function(id) {
                            if (id in this.$items) {
                                this.$items[id].setAttribute('active', result[id]);
                            }
                        }.bind(this));
                    }

                    if (typeof oncomplete === 'function') {
                        oncomplete(result);
                    }

                    resolve(result);

                }.bind(this), params);

            }.bind(this));
        },

        /**
         * Delete one ore more items
         *
         * @method classes/projects/project/Media#del
         *
         * @param {Number|Array} id       - Item list or an Item id
         * @param {Function} [oncomplete] - (optional), callback Function
         * @param {Object} [params]       - (optional), parameters that are linked to the request object
         *
         * @return Promise
         */
        del : function(id, oncomplete, params)
        {
            return new Promise(function(resolve, reject)
            {
                if (!id.length) {
                    if (typeof oncomplete === 'function') {
                        oncomplete(false);
                    }

                    resolve(false);
                    return;
                }

                params = ObjectUtils.combine(params, {
                    project: this.getProject().getName(),
                    fileid: JSON.encode(id),
                    onError: reject
                });

                Ajax.post('ajax_media_delete', function (result)
                {
                    if (typeof oncomplete === 'function') {
                        oncomplete(result);
                    }

                    resolve(false);
                }, params);

            }.bind(this));
        },

        /**
         * Parse the get result to a file object
         *
         * @return {Object|Array} classes/projects/project/media/Item
         */
        $parseResultToItem : function(result)
        {
            if (!result) {
                return [];
            }

            if (typeOf(result) == 'array' && result.length)
            {
                var list = [];

                for (var i = 0, len = result.length; i < len; i++)
                {
                    list.push(
                        this.$parseResultToItem(result[i])
                    );
                }

                return list;
            }

            if (result.id in this.$items) {
                return this.$items[result.id];
            }

            var Item;
            var self = this;

            switch (result.type)
            {
                case "image":
                    Item = new MediaImage(result, this);
                break;

                case "folder":
                    Item = new MediaFolder(result, this);
                break;

                default:
                    Item = new MediaFile(result, this);
                break;
            }

            Item.addEvents({
                onRename : function(Item) {
                    self.fireEvent('itemRename', [self, Item]);
                },
                onActivate : function(Item) {
                    self.fireEvent('itemActivate', [self, Item]);
                },
                onDeactivate : function(Item) {
                    self.fireEvent('itemDeactivate', [self, Item]);
                },
                onRefresh : function(Item) {
                    self.fireEvent('itemRefresh', [self, Item]);
                },
                onSave : function(Item) {
                    self.fireEvent('itemSave', [self, Item]);
                },
                onDelete : function(Item) {
                    self.fireEvent('itemDelete', [self, Item]);
                }
            });

            return Item;
        }
    });
});
