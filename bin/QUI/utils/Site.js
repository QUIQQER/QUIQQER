/**
 * Helper for site operations
 *
 * @module utils/Site
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require utils/Panels
 * @require Locale
 * @require Ajax
 *
 * @event onBeforeOpenCreateChild [ ParentSite ]
 * @event onOpenCreateChild [ Win, ParentSite ]
 * @event onOpenCreateChildSubmit [ value, Win, ParentSite ]
 */

define('utils/Site', [

    'qui/QUI',
    'utils/Panels',
    'Locale',
    'Ajax'

], function (QUI, PanelUtils, Locale, Ajax) {
    "use strict";

    return {

        /**
         * Return the not allowed signs list for an url
         *
         * @return Object
         */
        notAllowedUrlSigns: function () {
            return {
                '.' : true,
                ',' : true,
                ':' : true,
                ';' : true,
                '#' : true,
                '`' : true,
                '!' : true,
                '§' : true,
                '$' : true,
                '%' : true,
                '&' : true,
                '?' : true,
                '<' : true,
                '>' : true,
                '=' : true,
                '\'': true,
                '"' : true,
                '@' : true,
                '_' : true,
                ']' : true,
                '[' : true,
                '+' : true,
                '/' : true
            };
        },

        /**
         * similar function as \QUI\Projects\Site\Utils::clearUrl
         *
         * @param {String} url
         * @return {String}
         */
        clearUrl: function (url) {
            var signs = Object.keys(this.notAllowedUrlSigns()).join("");

            url = url.replace(new RegExp(signs, 'g'), '');


            // doppelte leerzeichen löschen
            // $url = preg_replace('/([ ]){2,}/', "$1", $url);


            return url;
        },

        /**
         * Create a child site, opens the confirm window
         *
         * @param {Object} ParentSite - classes/projects/Site
         * @param {String} [value] - new name of the site, if no newname was passed, a window would be open
         */
        openCreateChild: function (ParentSite, value) {
            var self    = this,
                lg      = 'quiqqer/system',
                Site    = ParentSite,
                Project = Site.getProject();

            if (typeof value === 'undefined') {
                value = '';
            }

            ParentSite.fireEvent('beforeOpenCreateChild', [ParentSite]);


            require(['qui/controls/windows/Prompt'], function (Prompt) {

                new Prompt({
                    title        : Locale.get(lg, 'projects.project.site.panel.window.create.title'),
                    text         : Locale.get(lg, 'projects.project.site.panel.window.create.text'),
                    titleicon    : 'fa fa-file',
                    icon         : 'fa fa-file',
                    information  : Locale.get(lg, 'projects.project.site.panel.window.create.information', {
                        name: Site.getAttribute('name'),
                        id  : Site.getId()
                    }),
                    cancel_button: {
                        text     : Locale.get(lg, 'cancel'),
                        textimage: 'fa fa-remove'
                    },
                    ok_button    : {
                        text     : Locale.get(lg, 'projects.project.site.panel.window.create.button.submit'),
                        textimage: 'fa fa-file'
                    },
                    maxWidth     : 450,
                    maxHeight    : 300,
                    value        : value,
                    autoclose    : false,
                    events       : {
                        onOpen: function (Win) {
                            ParentSite.fireEvent('openCreateChild', [Win, ParentSite]);
                            Win.resize();
                        },

                        onSubmit: function (value, Win) {
                            ParentSite.fireEvent(
                                'openCreateChildSubmit',
                                [value, Win, ParentSite]
                            );

                            Site.createChild(value, function (result) {
                                Win.close();

                                PanelUtils.openSitePanel(
                                    Project.getName(),
                                    Project.getLang(),
                                    result.id
                                );

                            }, function (Exception) {
                                // on error
                                if (Exception.getCode() == 702) {
                                    Ajax.get('ajax_site_clear', function (newName) {
                                        Win.close();

                                        require(['qui/controls/windows/Confirm'], function (QUIConfirm) {
                                            new QUIConfirm({
                                                title      : Locale.get(lg, 'projects.project.site.panel.window.create.clear.title'),
                                                text       : Locale.get(lg, 'projects.project.site.panel.window.create.clear.text'),
                                                icon       : 'fa fa-warning',
                                                maxWidth   : 600,
                                                maxHeight  : 400,
                                                autoclose  : false,
                                                information: Locale.get(lg, 'projects.project.site.panel.window.create.clear.information', {
                                                    newName: newName,
                                                    value  : value
                                                }),
                                                events     : {
                                                    onSubmit: function (Win) {
                                                        Win.Loader.show();

                                                        Site.createChild({
                                                            name : newName,
                                                            title: value
                                                        }, function (result) {
                                                            Win.close();

                                                            // open new site
                                                            PanelUtils.openSitePanel(
                                                                Project.getName(),
                                                                Project.getLang(),
                                                                result.id
                                                            );

                                                        }, function (Exception) {
                                                            Win.close();

                                                            self.openCreateChild(newName);

                                                            QUI.getMessageHandler(function (MH) {
                                                                MH.addError(Exception.getMessage());
                                                            });
                                                        });
                                                    }
                                                }
                                            }).open();
                                        });

                                    }, {
                                        project: Project.encode(),
                                        name   : value
                                    });

                                    return;
                                }

                                QUI.getMessageHandler(function (MH) {
                                    MH.addError(Exception.getMessage());
                                });

                                Win.Loader.hide();
                            });
                        }
                    }
                }).open();
            });
        }
    };
});
