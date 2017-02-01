/**
 * QUIQQER Breadcrumb Control
 *
 * @author www.pcsg.de (Michael Danielczok)
 * @module Controls\Contact
 */

define('Controls/Breadcrumb', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/Functions'
], function (QUI, QUIControl)
{
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'Controls/Breadcrumb',

        Binds: [
            '$onImport',
            'checkWidth',
            'setMobile',
            'unsetMobile',
            'trigger'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.container = null;
            this.title = null;
            this.breadcrumb = null;
            this.button = null;
            this.elmNumber = null;
            this.height = null;

            this.isOpen = false;
            this.breadcrumbWidth = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },


        /**
         * event : on import
         */
        $onImport: function ()
        {
            this.container = document.getElement('.quiqqer-breadcrumb-container');
            this.title = document.getElement('.quiqqer-breadcrumb-title');
            this.breadcrumb = document.getElement('.quiqqer-breadcrumb');
            this.button = document.getElement('.quiqqer-breadcrumb-link-icon');
            this.elmNumber = document.getElements('.quiqqer-breadcrumb-list li').length;
            this.height = parseInt(this.breadcrumb.getStyle('line-height'));

            this.isOpen = false;
            this.breadcrumbWidth = 0;

            this.checkWidth();

            // button click
            this.button.addEvent('click', this.trigger);

            QUI.addEvent('onResize', function ()
            {
                this.checkWidth();
            }.bind(this));
        },

        /**
         * check if container is shorter than breadcrumb
         */
        checkWidth: function ()
        {
            if (this.isOpen) {
                this.trigger();
            }

            var containerWidth       = parseInt(this.container.getSize().x),
                containerWidthScroll = parseInt(this.container.getScrollSize().x);


            // scroll width or window size 768px
            if (containerWidth < containerWidthScroll || parseInt(window.getSize().x) < 768) {
                // mobile
                this.setMobile();
                this.breadcrumbWidth = containerWidthScroll;
                return;
            }

            if (containerWidth >= this.breadcrumbWidth) {
                // desktop
                this.unsetMobile();
            }
        },

        /**
         * set mobile classes
         */
        setMobile: function ()
        {
            this.title.setStyle('display', 'none');
            this.breadcrumb.addClass('quiqqer-breadcrumb-mobile');
        },

        /**
         * remove mobile classes
         */
        unsetMobile: function ()
        {
            this.title.setStyle('display', 'inline');
            this.breadcrumb.removeClass('quiqqer-breadcrumb-mobile');
        },

        /**
         * open or close the breadcrumb
         */
        trigger: function ()
        {
            // jeweils +1px, weil border; -1px, weil letzter border fehlt
            var height = (this.height +1) * this.elmNumber -1;

            if (this.isOpen === false) {
                // open
                this.container.setStyle('height', height);
                this.isOpen = true;

                moofx(this.button).animate({
                    transform: 'rotate(180deg)'
                }, {
                    duration: 300
                });
                return;
            }

            // close
            this.container.setStyle('height', this.height);
            this.isOpen = false;

            moofx(this.button).animate({
                transform: 'rotate(0)'
            }, {
                duration: 300
            });
        }

    });
});
