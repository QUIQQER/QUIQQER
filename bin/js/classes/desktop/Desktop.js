/**
 * Desktop
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/desktop/Desktop
 * @package com.pcsg.qui.js.classes.desktop
 * @namespace QUI.classes.desktop.Desktop
 */

define('classes/desktop/Desktop', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace('classes.desktop');

    /**
     * @class QUI.classes.desktop.Desktop
     *
     * @param {DOMNode} Parent
     * @param {Object} options
     */
    QUI.classes.desktop.Desktop = new Class({

        Implements: [DOM],

        options : {
            'lock-widget-settings' : true,
            'title' : 'Desktop',
            'icon'  : URL_BIN_DIR +'16x16/desktop.png'
        },

        $widgets   : [],
        $sortables : null,

        initialize : function(Parent, options)
        {
            this.init( options );

            this.$sortables = new Sortables([], {
                opacity   : 0.5,
                constrain : true,
                clone     : function(event)
                {
                    var cords;
                    var Target = event.target;

                    if (!Target.hasClass('desktop-widget')) {
                        Target = Target.getParent('.desktop-widget');
                    }

                    cords = Target.getCoordinates( Target.getParent() );

                    return new Element('div', {
                        'class' : 'desktop-widget-dragdrop',
                        styles : {
                            top    : cords.top,
                            left   : cords.left,
                            width  : cords.width,
                            height : cords.height,
                            position   : 'absolute',
                            zIndex     : 1000,
                            background : 'rgba(0,0,0, 0.5)'
                        }
                    });
                }.bind(this),
                revert : true
            });

            Parent = Parent || MUI.get('content-panel');

            MUI.Apppanels.create({
                title     : this.getAttribute('title'),
                control   : 'MUI.Apppanel',
                id        : 'desktop-panel',
                container : Parent.id,
                icon      : this.getAttribute('icon'),
                closeable : false,
                dragable  : false,
                onDrawEnd : function(Panel)
                {
                    this.$Panel = Panel;

                    this.$Panel.addButton({
                        textimage : URL_BIN_DIR +'16x16/lock.png',
                        title     : 'Miniprogramm entsperren',
                        Desktop   : this,
                        events    :
                        {
                            onclick : function(Btn)
                            {
                                if (Btn.isActive())
                                {
                                    Btn.setNormal();
                                    return;
                                }

                                Btn.setActive();
                            },

                            oncreate : function(Btn) {
                                Btn.setActive();
                            },
                            onSetActive : function(Btn) {
                                Btn.getAttribute('Desktop').lock();
                            },
                            onSetNormal : function(Btn) {
                                Btn.getAttribute('Desktop').unlock();
                            }
                        }
                    });

                    this.$Panel.addButton({
                        textimage : URL_BIN_DIR +'16x16/add.png',
                        title     : 'Miniprogramm hinzufügen'
                    });

                    this.load(function()
                    {
                        this.show();
                    }.bind(this));

                }.bind(this)
            });
        },

        /**
         * Unlock the Desktop, if the Desktop is locked
         *
         * @method QUI.classes.desktop.Desktop#unlock
         * @return {this}
         */
        unlock : function()
        {
            this.setAttribute('lock-widget-settings', false);

            for (var i = 0, len = this.$widgets.length; i < len; i++)
            {
                if (this.$widgets[i].getElm()) {
                    this.$sortables.addItems( this.$widgets[i].getElm() );
                }
            }

            return this;
        },

        /**
         * Lock the Desktop
         * No Settings and Resize options are shown
         *
         * @method QUI.classes.desktop.Desktop#lock
         * @return {this}
         */
        lock : function()
        {
            this.setAttribute('lock-widget-settings', true);

            for (var i = 0, len = this.$widgets.length; i < len; i++)
            {
                if (this.$widgets[i].getElm()) {
                    this.$sortables.removeItems( this.$widgets[i].getElm() );
                }
            }

            return this;
        },

        /**
         * Destroy the desktio and all widgets
         *
         * @method QUI.classes.desktop.Desktop#destroy
         * @return {this}
         *
         * @todo Destroy the desktio and all widgets
         */
        destroy : function()
        {

        },

        /**
         * Load the Desktop Widgets
         *
         * @method QUI.classes.desktop.Desktop#load
         * @param {Function} onfinish - callback function if the widgets are loaded
         * @return {this}
         */
        load : function(onfinish)
        {
            QUI.Ajax.get('ajax_desktop_load', function(result, Ajax)
            {
                console.info( 'ajax_desktop_load' );
                console.info( result );

                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, {
                onfinish : onfinish
            });

            return this;
        },

        /**
         * Save the Desktop Widgets
         *
         * @method QUI.classes.desktop.Desktop#save
         * @param {Function} onfinish - callback function if the widgets are saved
         * @return {this}
         */
        save: function(onfinish)
        {
            var i, len;
            var params = [];

            for (i = 0, len = this.$widgets.length; i < len; i++)
            {
                params.push(
                    this.$widgets[i].getAttributes()
                );
            }

            QUI.Ajax.post('ajax_desktop_save', function(result, Ajax)
            {
                if (Ajax.getAttribute('onfinish')) {
                    Ajax.getAttribute('onfinish')(result, Ajax);
                }
            }, {
                onfinish : onfinish,
                widgets  : JSON.encode( params )
            });

            return this;
        },

        /**
         * Show Desktop in the Panel and create all Widgets
         *
         * @method QUI.classes.desktop.Desktop#show
         * @return {this}
         */
        show : function()
        {
            if (!this.$Panel) {
                return this;
            }

            this.$Panel.show(function()
            {
                var Body = this.$Panel.getBody();

                for (var i = 0, len = this.$widgets.length; i < len; i++)
                {
                    this.$widgets[i].create().inject( Body );
                    this.$widgets[i].show();
                }

            }.bind(this));

            return this;
        },

        /**
         * Hide Desktop
         *
         * @method QUI.classes.desktop.Desktop#hide
         * @return {this}
         *
         * @todo not implemented
         */
        hide : function()
        {
            return this;
        },

        /**
         * Add a Widget to the Desktop
         *
         * @method QUI.classes.desktop.Desktop#addWidget
         * @param {Option} options - Widget options
         * @return {this}
         *
         * @example

var Desktop = QUI.classes.desktop.Desktop();

Desktop.addWidget({
    title  : 'test',
    events :
    {
        onDrawEnd : function(Widget) {
            Widget.getBody().set('html', 'A Widget');
        },

        onClick : function() {

        }
    }
});
         */
        addWidget : function(options)
        {
            options = options || {};

            if (typeof options.events === 'undefined') {
                options.events = {};
            }

            options.events.onClose = function(Widget)
            {
                this.removeWidget( Widget );
            }.bind(this);

            if (options.type === 'starter')
            {
                this.$widgets.push(
                    new QUI.classes.desktop.Starter(options, this)
                );

                return this;
            }

            this.$widgets.push(
                new QUI.classes.desktop.Widget(options, this)
            );

            return this;
        },

        /**
         * Remove the Widget from the Desktop
         *
         * @method QUI.classes.desktop.Desktop#removeWidget
         * @param {QUI.classes.desktop.Widget} Widget - Widget options
         * @return {this}
         */
        removeWidget : function(Widget)
        {
            var id      = Widget.getId(),
                widgets = [];

            for (var i = 0, len = this.$widgets.length; i < len; i++)
            {
                if (id === this.$widgets[i].getId()) {
                    continue;
                }

                widgets.push( this.$widgets[i] );
            }

            this.$widgets = widgets;

            return this;
        }
    });

    return QUI.classes.desktop.Desktop;
});
