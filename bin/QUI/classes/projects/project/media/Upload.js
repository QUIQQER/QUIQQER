/**
 * Prepares media uploads and handles filename conflicts before the upload starts.
 */
define('classes/projects/project/media/Upload', [
    'Ajax',
    'Locale'
], function (Ajax, Locale) {
    'use strict';

    const lg = 'quiqqer/core';
    const PAGE_SIZE = 500;
    const CONFLICT_ASK = 'ask';
    const CONFLICT_REPLACE = 'replace';

    const stripMediaName = function (name) {
        name = name
            .replace(/ä/g, 'ae')
            .replace(/ö/g, 'oe')
            .replace(/ü/g, 'ue')
            .replace(/[^0-9_a-zA-Z .-]/g, '');

        const parts = name.split('.');

        if (parts.length > 1) {
            const extension = parts.pop();
            name = parts.join('_') + '.' + extension;
        }

        return name.replace(/_{2,}/g, '_');
    };

    const stripFolderName = function (name) {
        return name
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^0-9a-zA-Z-]/g, '_')
            .replace(/_{2,}/g, '_');
    };

    const getFilePath = function (entry) {
        if (entry.fullPath) {
            return entry.fullPath;
        }

        if (entry.webkitRelativePath) {
            return entry.webkitRelativePath;
        }

        return entry.name;
    };

    const getFile = function (entry) {
        return entry.fileObject || entry;
    };

    const getPathInfo = function (entry) {
        const path = getFilePath(entry).replace(/^\/+/, '').split('/');
        const originalName = path.pop() || getFile(entry).name;

        return {
            folders: path.map(stripFolderName),
            name: stripMediaName(originalName)
        };
    };

    const loadChildren = function (project, folderId) {
        const children = [];

        return new Promise(function (resolve, reject) {
            const loadPage = function (page) {
                Ajax.get('ajax_media_folder_children', function (result) {
                    children.push.apply(children, result.data || []);

                    if (children.length < result.total) {
                        loadPage(page + 1);
                        return;
                    }

                    resolve(children);
                }, {
                    project: project,
                    folderid: folderId,
                    params: JSON.encode({
                        order: 'name ASC',
                        page: page,
                        perPage: PAGE_SIZE,
                        showHiddenFiles: true
                    }),
                    onError: reject
                });
            };

            loadPage(1);
        });
    };

    const getUniqueName = function (name, usedNames) {
        const dot = name.lastIndexOf('.');
        const basename = dot > 0 ? name.substring(0, dot) : name;
        const extension = dot > 0 ? name.substring(dot) : '';
        let counter = 1;
        let candidate = basename + '_' + counter + extension;

        while (usedNames.has(candidate)) {
            counter++;
            candidate = basename + '_' + counter + extension;
        }

        return candidate;
    };

    const escapeHtml = function (value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const renameEntry = function (entry, newName) {
        const source = getFile(entry);
        const renamedFile = new File([source], newName, {
            type: source.type,
            lastModified: source.lastModified
        });

        if (!entry.fileObject && !entry.webkitRelativePath) {
            return renamedFile;
        }

        const renamedEntry = Object.assign({}, entry, {
            fileObject: renamedFile,
            name: newName
        });
        const path = getFilePath(entry).replace(/^\/+/, '').split('/');

        path.pop();
        path.push(newName);
        renamedEntry.fullPath = path.join('/');

        return renamedEntry;
    };

    const showConflictDialog = function (files, conflicts) {
        const list = conflicts.map(function (conflict) {
            return '<li><strong>' + escapeHtml(conflict.oldName) + '</strong> &rarr; ' +
                escapeHtml(conflict.newName) + '</li>';
        }).join('');

        return new Promise(function (resolve, reject) {
            require(['qui/controls/windows/Confirm'], function (QUIConfirm) {
                new QUIConfirm({
                    title: Locale.get(lg, 'projects.project.media.upload.conflict.title'),
                    icon: 'fa fa-files-o',
                    texticon: 'fa fa-files-o',
                    text: Locale.get(lg, 'projects.project.media.upload.conflict.text'),
                    information: Locale.get(lg, 'projects.project.media.upload.conflict.information', {
                        files: '<ul>' + list + '</ul>'
                    }),
                    maxHeight: 500,
                    maxWidth: 650,
                    closeButton: false,
                    cancel_button: {
                        text: Locale.get(lg, 'projects.project.media.upload.conflict.overwrite'),
                        textimage: 'fa fa-refresh'
                    },
                    ok_button: {
                        text: Locale.get(lg, 'projects.project.media.upload.conflict.rename'),
                        textimage: 'fa fa-font'
                    },
                    events: {
                        onCancel: function () {
                            resolve(files);
                        },
                        onSubmit: function () {
                            const renamedFiles = files.slice();

                            conflicts.forEach(function (conflict) {
                                renamedFiles[conflict.index] = renameEntry(
                                    renamedFiles[conflict.index],
                                    conflict.newName
                                );
                            });

                            resolve(renamedFiles);
                        }
                    }
                }).open();
            }, reject);
        });
    };

    return {
        CONFLICT_ASK: CONFLICT_ASK,
        CONFLICT_REPLACE: CONFLICT_REPLACE,

        /**
         * Checks for filename conflicts and asks how they should be handled.
         *
         * @param {Array|FileList} uploadFiles
         * @param {String} project
         * @param {Number|String} parentId
         * @param {String} [conflictBehavior] - "ask" or "replace"; defaults to the current application context
         * @return {Promise<Array>}
         */
        prepare: function (uploadFiles, project, parentId, conflictBehavior) {
            const files = Array.from(uploadFiles);
            const isFrontend = typeof window !== 'undefined' &&
                typeof window.QUIQQER_FRONTEND !== 'undefined' &&
                window.QUIQQER_FRONTEND;

            conflictBehavior = conflictBehavior || (isFrontend ? CONFLICT_REPLACE : CONFLICT_ASK);

            if (conflictBehavior === CONFLICT_REPLACE) {
                return Promise.resolve(files);
            }

            const childrenCache = {};
            const folderCache = {'': Promise.resolve(parentId)};

            const getChildren = function (folderId) {
                if (!childrenCache[folderId]) {
                    childrenCache[folderId] = loadChildren(project, folderId);
                }

                return childrenCache[folderId];
            };

            const resolveFolder = function (folders) {
                const cacheKey = folders.join('/');

                if (folderCache[cacheKey]) {
                    return folderCache[cacheKey];
                }

                const parentFolders = folders.slice(0, -1);
                const folderName = folders[folders.length - 1];

                folderCache[cacheKey] = resolveFolder(parentFolders).then(function (folderId) {
                    if (folderId === null) {
                        return null;
                    }

                    return getChildren(folderId).then(function (children) {
                        const child = children.find(function (item) {
                            return item.type === 'folder' && item.name === folderName;
                        });

                        return child ? child.id : null;
                    });
                });

                return folderCache[cacheKey];
            };

            const checks = files.map(function (entry, index) {
                const info = getPathInfo(entry);

                return resolveFolder(info.folders).then(function (folderId) {
                    if (folderId === null) {
                        return {
                            index: index,
                            info: info,
                            existingNames: []
                        };
                    }

                    return getChildren(folderId).then(function (children) {
                        return {
                            index: index,
                            info: info,
                            existingNames: children.map(function (item) {
                                return item.name;
                            })
                        };
                    });
                });
            });

            return Promise.all(checks).then(function (results) {
                const usedNames = {};
                const conflicts = [];

                results.forEach(function (result) {
                    const folderKey = result.info.folders.join('/');

                    if (!usedNames[folderKey]) {
                        usedNames[folderKey] = new Set(result.existingNames);
                    }

                    const names = usedNames[folderKey];

                    if (names.has(result.info.name)) {
                        const newName = getUniqueName(result.info.name, names);

                        conflicts.push({
                            index: result.index,
                            oldName: result.info.name,
                            newName: newName
                        });
                        names.add(newName);
                        return;
                    }

                    names.add(result.info.name);
                });

                if (!conflicts.length) {
                    return files;
                }

                return showConflictDialog(files, conflicts);
            });
        }
    };
});
