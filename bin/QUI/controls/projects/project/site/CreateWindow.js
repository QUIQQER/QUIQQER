/**
 * @module controls/projects/project/site/CreateWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * opens a window to create a new site
 */
define('controls/projects/project/site/CreateWindow', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'controls/projects/TypeInput',
    'Locale',
    'Mustache',
    'Ajax',

    'text!controls/projects/project/site/CreateWindow.html'

], function (QUI, QUIControl, QUIConfirm, TypeInput, QUILocale, Mustache, QUIAjax, template) {
    "use strict";

    const lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/projects/project/site/CreateWindow',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                title    : QUILocale.get(lg, 'projects.project.site.panel.window.create.title'),
                text     : QUILocale.get(lg, 'projects.project.site.panel.window.create.text'),
                titleicon: 'fa fa-file',
                icon     : 'fa fa-file',
                maxWidth : 600,
                maxHeight: 500,
                autoclose: false,
            });

            this.$Site = this.getAttribute('Site');

            this.addEvents({
                onOpen  : this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        $onOpen: function (Win) {
            Win.Loader.show();

            Win.getContent().set('html', Mustache.render(template, {
                header  : QUILocale.get(lg, 'projects.project.site.window.create.header', {
                    name: this.$Site.getAttribute('name'),
                    id  : this.$Site.getId()
                }),
                title   : QUILocale.get(lg, 'projects.project.site.window.create.inputTitle'),
                seoTitle: QUILocale.get(lg, 'projects.project.site.window.create.seoTitle'),
                siteType: QUILocale.get(lg, 'projects.project.site.panel.information.type'),
                layout  : QUILocale.get(lg, 'projects.project.site.panel.information.layout')
            }));

            const Layouts = Win.getContent().getElement('[name="layout"]');
            const SiteType = Win.getContent().getElement('[name="type"]');

            QUIAjax.get('ajax_project_get_layouts', (layouts) => {
                new Element('option', {
                    html : '',
                    value: ''
                }).inject(Layouts);

                for (let i = 0, len = layouts.length; i < len; i++) {
                    new Element('option', {
                        html : layouts[i].title,
                        value: layouts[i].type
                    }).inject(Layouts);
                }

                new TypeInput({
                    project: this.$Site.getProject().getName()
                }, SiteType).create();

                this.$Site.fireEvent('openCreateChild', [
                    Win,
                    this.$Site
                ]);

                Win.getContent().getElement('[name="title"]').focus();
                Win.resize();
                Win.Loader.hide();
            }, {
                project: this.$Site.getProject().encode()
            });
        },

        $onSubmit: function (Win) {
            Win.Loader.show();

            const Project = this.$Site.getProject();
            const Form = Win.getContent().getElement('form');

            let title = Form.elements.title.value;
            let type = Form.elements.type.value;
            let layout = Form.elements.layout.value;

            if (title === '') {
                Win.Loader.hide();

                if (typeof Form.elements.title.reportValidity !== 'undefined') {
                    Form.elements.title.reportValidity();
                }

                Form.elements.title.focus();

                return;
            }

            this.$Site.fireEvent('openCreateChildSubmit', [
                title,
                Win,
                this.$Site
            ]);

            QUIAjax.post('ajax_site_children_create', (attributes) => {
                const Child = Project.get(attributes.id);
                this.fireEvent('siteCreated', [Child]);

                Project.fireEvent('siteCreate', [
                    Project,
                    this.$Site
                ]);

                Win.close();
            }, {
                project   : this.$Site.getProject().encode(),
                id        : this.$Site.getId(),
                attributes: JSON.encode({
                    title                    : title,
                    type                     : type,
                    layout                   : layout,
                    'quiqqer.meta.site.title': Form.elements.seotitle.value
                }),

                onError: function () {
                    Win.Loader.hide();
                }
            });
        }
    });
});