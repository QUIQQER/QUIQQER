/**
 * QUIQQER Breadcrumb Control
 *
 * @author www.pcsg.de (Michael Danielczok)
 * @module package/quiqqer/core/bin/Controls/BreadcrumbSlider
 */

define('package/quiqqer/core/bin/Controls/BreadcrumbSlider', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/Functions'
], function (QUI, QUIControl, QUIFunctionUtils)
{
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/core/bin/Controls/BreadcrumbDropDown',

        Binds: [
            '$onImport',
            '$onScroll',
            'prev',
            'next',
            'resize'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.$SlideFX = null;
            this.$Prev = null;
            this.$Next = null;
            this.$Inner = null;
            this.$InnerScrollSize = null;
            this.$InnerSize = null;
            this.$IconWidth = 0;

            this.$scrollMax = 0;

            this.nextButton = false;
            this.prevButton = false;

            this.addEvents({
                onImport: this.$onImport
            });

            QUI.addEvent('resize', this.resize);
        },

        /**
         * resize the control and recalc the $Inner width
         */
        resize: function ()
        {
            var offset = 0;

            if ((this.prevButton === true) && (this.nextButton === true)) {
                offset = this.$IconWidth * 2;
            } else {
                if (this.prevButton === true || this.nextButton === true) {
                    offset = this.$IconWidth;
                }
            }

            moofx(this.$Inner).animate({
                width: document.getElement('.quiqqer-breadcrumb-wrapper').getSize().x - offset
            }, {
                duration: 300,
                callback: function ()
                {
                    this.$InnerSize = this.$Inner.getSize().x + offset;
                    this.$onScroll();
                }.bind(this)
            });
        },

        /**
         * event : on import
         */
        $onImport: function ()
        {
            var Elm  = this.getElm(),
                size = Elm.getSize();

            // next button
            this.$Next = new Element('div', {
                'class': 'quiqqer-breadcrumb-slider-next',
                html   : '<span class="fa fa-arrow-circle-right"></span>',
                styles : {
                    display   : 'none',
                    lineHeight: size.y,
                    opacity   : 0,
                    right     : -this.$IconWidth
                },
                events : {
                    click: this.next
                }
            }).inject(Elm.getElement('.quiqqer-breadcrumb-wrapper'));

            // prev button
            this.$Prev = new Element('div', {
                'class': 'quiqqer-breadcrumb-slider-prev',
                html   : '<span class="fa fa-arrow-circle-left"></span>',
                styles : {
                    display   : 'none',
                    left      : 0,
                    lineHeight: size.y,
                    opacity   : 0
                },
                events : {
                    click: this.prev
                }
            }).inject(Elm.getElement('.quiqqer-breadcrumb-wrapper'));


            this.$IconWidth = Elm.getElement('.quiqqer-breadcrumb-slider-next').getDimensions().x;
            this.$Inner = Elm.getElement('.quiqqer-breadcrumb-container');
            this.$InnerSize = this.$Inner.getSize().x;
            this.$InnerScrollSize = this.$Inner.getScrollSize().x;

            this.$SlideFX = new Fx.Scroll(this.$Inner);

            var scrollSpy = QUIFunctionUtils.debounce(this.$onScroll, 200);

            this.$Inner.addEvent('scroll', scrollSpy);

            this.$NextFX = moofx(this.$Next);
            this.$PrevFX = moofx(this.$Prev);

            if (this.$InnerScrollSize > this.$InnerSize) {
                this.showNextButton.delay(200, this);
            }
        },

        /**
         * scroll to preview
         *
         * @return {Promise}
         */
        prev: function ()
        {
            return new Promise(function (resolve)
            {
                var left = this.$Inner.getScroll().x - this.$InnerSize;

                if (left < 0) {
                    left = 0;
                }

                this.$SlideFX.start(left, 0).chain(resolve);

            }.bind(this));
        },

        /**
         * scroll to next
         *
         * @return {Promise}
         */
        next: function ()
        {
            return new Promise(function (resolve)
            {
                var left = this.$Inner.getScroll().x + this.$InnerSize;
                this.$SlideFX.start(left, 0).chain(resolve);
            }.bind(this));
        },

        /**
         * Show the next button
         * @returns {Promise}
         */
        showNextButton: function ()
        {
            return new Promise(function (resolve)
            {
                if (this.nextButton) {
                    return;
                }

                this.nextButton = true;
                this.$Next.setStyle('display', null);

                var right   = 0,
                    opacity = 1,
                    offset  = this.$IconWidth;

                if (this.prevButton === true) {
                    offset = this.$IconWidth * 2;
                }

                moofx(this.$Inner).animate({
                    width: this.$InnerSize - offset
                }, {
                    duration: 300
                });

                this.$NextFX.animate({
                    right  : right,
                    opacity: opacity
                }, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Show the previous button
         * @returns {Promise}
         */
        showPrevButton: function ()
        {
            return new Promise(function (resolve)
            {
                if (this.prevButton) {
                    return;
                }

                this.prevButton = true;
                this.$Prev.setStyle('display', null);

                var left    = 0,
                    opacity = 1,
                    offset  = this.$IconWidth;

                if (this.nextButton === true) {
                    offset = this.$IconWidth * 2;
                }

                moofx(this.$Inner).animate({
                    left : this.$IconWidth,
                    width: this.$InnerSize - offset
                }, {
                    duration: 300
                });

                this.$PrevFX.animate({
                    left   : left,
                    opacity: opacity
                }, {
                    duration: 300,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Hide the next button
         * @returns {Promise}
         */
        hideNextButton: function ()
        {
            return new Promise(function (resolve)
            {
                if (!this.nextButton) {
                    return;
                }

                this.nextButton = false;

                this.$NextFX.animate({
                    right  : -this.$IconWidth,
                    opacity: 0
                }, {
                    duration: 300,
                    callback: function ()
                    {
                        this.$Next.setStyle('display', 'none');

                        resolve();
                    }.bind(this)
                });
                
                moofx(this.$Inner).animate({
                    width: this.$InnerSize - this.$IconWidth
                }, {
                    duration: 300
                });

            }.bind(this));
        },

        /**
         * Hide the prev button
         * @returns {Promise}
         */
        hidePrevButton: function ()
        {
            return new Promise(function (resolve)
            {
                if (!this.prevButton) {
                    return;
                }

                this.prevButton = false;

                this.$PrevFX.animate({
                    left   : 0,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function ()
                    {
                        this.$Prev.setStyle('display', 'none');
                        resolve();
                    }.bind(this)
                });

                moofx(this.$Inner).animate({
                    left : 0,
                    width: this.$Inner.getSize().x + this.$IconWidth
                }, {
                    duration: 300
                });

            }.bind(this));
        },

        /**
         * event : on scroll
         * look for the prev and next button
         */
        $onScroll: function ()
        {
            var left = this.$Inner.getScroll().x;

            if (left === 0) {
                this.hidePrevButton();
            } else {
                this.showPrevButton();
            }

            if (left === (this.$Inner.getScrollSize().x - this.$Inner.getSize().x)) {
                this.hideNextButton();
            } else {
                this.showNextButton();
            }
        }
    });
});
