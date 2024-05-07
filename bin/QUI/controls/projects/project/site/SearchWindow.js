/**
 * @module controls/projects/project/site/Search
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/project/site/SearchWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'controls/projects/project/site/Search',
    'Locale',

    'css!controls/projects/project/site/SearchWindow.css'

], function (QUI, QUIConfirm, Search, QUILocale) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/projects/project/site/SearchWindow',

        options: {
            icon     : 'fa fa-search',
            title    : QUILocale.get(lg, 'projects.project.site.search.title'),
            maxWidth : 1100,
            maxHeight: 700,

            multiple: true
        },

        Binds: [
            '$onOpen',
            '$onResize',
            '$onGridClick'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Search = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onResize: this.$onResize
            });
        },

        $onOpen: function () {
            this.getContent().set('html', '');
            this.getContent().addClass('project-search-window');
            this.getContent().setStyle('padding', 0);

            this.Loader.show();

            this.$Search = new Search({
                onClick: this.$onGridClick

            }).inject(this.getContent());

            this.$Search.$Grid.setAttribute('multipleSelection', this.getAttribute('multiple'));

            this.$onResize();
            this.Loader.hide();
        },

        $onGridClick: function () {
            this.submit();
        },

        $onResize: function () {
            if (!this.$Search) {
                return;
            }

            const Content = this.getContent();
            const size = Content.getSize();

            this.$Search.$Grid.setSize(
                size.x - 40,
                size.y - 40 - 75
            );
        },

        submit: function () {
            const data = this.$Search.$Grid.getSelectedData();
            const result = data.map(function (entry) {
                let projectData = entry.project;
                projectData = projectData.replace('(', '').replace(')', '').split(' ');


                return {
                    id     : entry.id,
                    project: projectData[0],
                    lang   : projectData[1]
                };
            });


            this.fireEvent('submit', [
                this,
                result
            ]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
