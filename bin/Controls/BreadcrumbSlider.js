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
        Type   : 'package/quiqqer/core/bin/Controls/BreadcrumbSlider',

        Binds: [
            '$onImport',
            '$onResize',
            '$onScroll',
            '$refresh',
            '$alignToEnd',
            '$updateMask',
            '$onMouseDown',
            '$onMouseMove',
            '$onMouseUp',
            '$onDragStart',
            '$onClickCapture'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.$Container = null;
            this.$supportsDragNavigation = false;
            this.$tolerance = 1;
            this.$dragThreshold = 6;
            this.$isMouseDown = false;
            this.$isDragging = false;
            this.$preventClick = false;
            this.$startX = 0;
            this.$startScrollLeft = 0;
            this.$resizeHandler = QUIFunctionUtils.debounce(this.$onResize, 100);

            this.addEvents({
                onImport: this.$onImport
            });

            QUI.addEvent('resize', this.$resizeHandler);
            QUI.addEvent('onResize', this.$resizeHandler);
        },

        $onImport: function ()
        {
            this.$Container = this.getElm().getElement('[data-name="container"]');

            if (!this.$Container) {
                return;
            }

            this.$supportsDragNavigation = this.$canUseDragNavigation();

            this.$Container.addEvent(
                'scroll',
                QUIFunctionUtils.debounce(this.$onScroll, 30)
            );

            this.$Container.addEvent('click', this.$onClickCapture);

            if (this.$supportsDragNavigation) {
                this.$Container.addEvent('mousedown', this.$onMouseDown);
                this.$Container.addEvent('dragstart', this.$onDragStart);
                document.addEventListener('mousemove', this.$onMouseMove, {
                    passive: false
                });
                document.addEventListener('mouseup', this.$onMouseUp);
                window.addEventListener('blur', this.$onMouseUp);
                this.$Container.addClass('quiqqer-core-controls-breadcrumb-container--draggable');
            }

            this.$alignToEnd();
            this.$refresh();
        },

        $onResize: function ()
        {
            if (!this.$Container) {
                return;
            }

            this.$alignToEnd();
            this.$refresh();
        },

        $onScroll: function ()
        {
            this.$refresh();
        },

        $canUseDragNavigation: function ()
        {
            if (!window.matchMedia) {
                return true;
            }

            return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        },

        $hasOverflow: function ()
        {
            return this.$Container.scrollWidth > this.$Container.clientWidth + this.$tolerance;
        },

        $getMaxScrollLeft: function ()
        {
            return Math.max(
                this.$Container.scrollWidth - this.$Container.clientWidth,
                0
            );
        },

        $alignToEnd: function ()
        {
            if (!this.$hasOverflow()) {
                this.$Container.scrollLeft = 0;
                return;
            }

            this.$Container.scrollLeft = this.$getMaxScrollLeft();
        },

        $refresh: function ()
        {
            this.$updateMask();
        },

        $updateMask: function ()
        {
            var hasOverflow, isAtStart, isAtEnd;

            if (!this.$Container) {
                return;
            }

            hasOverflow = this.$hasOverflow();
            isAtStart = this.$Container.scrollLeft <= this.$tolerance;
            isAtEnd = this.$Container.scrollLeft + this.$Container.clientWidth >=
                this.$Container.scrollWidth - this.$tolerance;

            this.$Container.style.setProperty(
                '--_mask-start',
                hasOverflow && !isAtStart ? 'var(--_mask-size)' : '0px'
            );

            this.$Container.style.setProperty(
                '--_mask-end',
                hasOverflow && !isAtEnd ? 'var(--_mask-size)' : '0px'
            );
        },

        $onMouseDown: function (event)
        {
            if (!this.$hasOverflow() || event.rightClick || event.which === 3) {
                return;
            }

            event.preventDefault();
            this.$isMouseDown = true;
            this.$isDragging = false;
            this.$preventClick = false;
            this.$startX = event.page.x;
            this.$startScrollLeft = this.$Container.scrollLeft;
        },

        $onMouseMove: function (event)
        {
            var deltaX;

            if (!this.$isMouseDown) {
                return;
            }

            if (typeof event.buttons !== 'undefined' && event.buttons !== 1) {
                this.$onMouseUp();
                return;
            }

            deltaX = event.pageX - this.$startX;

            if (!this.$isDragging && Math.abs(deltaX) < this.$dragThreshold) {
                return;
            }

            if (!this.$isDragging) {
                this.$isDragging = true;
                this.$preventClick = true;
                this.$Container.addClass('is-dragging');
            }

            event.preventDefault();
            this.$Container.scrollLeft = this.$startScrollLeft - deltaX;
            this.$refresh();
        },

        $onMouseUp: function ()
        {
            this.$isMouseDown = false;
            this.$Container.removeClass('is-dragging');

            if (!this.$isDragging) {
                return;
            }

            this.$isDragging = false;

            (function ()
            {
                this.$preventClick = false;
            }).delay(0, this);
        },

        $onDragStart: function (event)
        {
            event.preventDefault();
        },

        $onClickCapture: function (event)
        {
            if (!this.$preventClick) {
                return;
            }

            event.stop();
        }
    });
});
