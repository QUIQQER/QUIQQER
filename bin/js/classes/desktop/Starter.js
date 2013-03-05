/**
 * Desktop Starter Widget
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires classes/desktop/WidgetSettings
 *
 * @module classes/desktop/Starter
 * @package com.pcsg.qui.js.classes.desktop
 * @namespace QUI.classes.desktop
 */

define('classes/desktop/Starter', [

    'classes/DOM',
    'classes/desktop/WidgetSettings',

    'css!classes/desktop/Starter.css'

], function(DOM, WidgetSettings)
{
    QUI.namespace( 'classes.desktop' );

    /**
     * @class QUI.classes.desktop.Starter
     *
     * @fires onDrawEnd
     * @fires onDrawBegin
     * @fires onClose
     * @fires onClick
     * @fires onBlur
     *
     * @param {Object} options
     * @param {QUI.classes.desktop.Desktop} Desktop
     */
    QUI.classes.desktop.Starter = new Class({

        Implements: [ DOM ],

        options : {
            title     : '',
            resizable : true,
            icon      : URL_BIN_DIR +'32x32/home.png'
        },

        $Desktop   : null,
        $Settings  : null,
        $mouseover : false,
        $FX        : null,


        initialize : function(options, Desktop)
        {
            this.init( options );
            this.el = {};

            this.$Settings = new QUI.classes.desktop.WidgetSettings(this, {
                size : 10
            });

            this.$Desktop  = Desktop || null;
            this.addEvent('openSettings', function()
            {
                this.openSettings();
            }.bind(this));

            this.addEvent('setAttribute', function(k, v)
            {
                if (typeof this.el.Elm === 'undefined') {
                    return;
                }

                if (k === 'title') {
                    this.el.Elm.getElement('.desktop-starter-text').set('html', v);
                }

            }.bind(this));
        },

        /**
         * Destroy the Starter Object
         *
         * @method QUI.classes.desktop.Starter#destroy
         */
        destroy : function()
        {
            this.$Settings.destroy();

            this.el.Elm.set('html', '');

            new Fx.Morph(this.el.Elm,
            {
                onComplete : function()
                {
                    for (var i in this.el) {
                        this.el[i].destroy();
                    }
                }.bind(this)
            }).start({
                width  : 0,
                height : 0
            });
        },

        /**
         * Create the Starter DOMNode Object
         *
         * @method QUI.classes.desktop.Starter#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.fireEvent('drawBegin', [this]);

            var Elm = new Element('div', {
                'class' : 'desktop-widget desktop-starter radius10',
                html    : '<span class="desktop-starter-text">'+
                            this.getAttribute('title') +
                        '</span>',
                tabindex : -1,
                styles  : {
                    width   : 64,
                    height  : 64,
                    display : 'none',
                    opacity : 0,
                    backgroundImage : 'url("'+ this.getAttribute('icon') +'")',
                    outline : 0
                },
                events :
                {
                    mouseenter : function() {
                        this.mouseenter();
                    }.bind(this),

                    mouseleave : function() {
                        this.mouseleave();
                    }.bind(this),

                    click : function() {
                        this.fireEvent('click', [this]);
                    }.bind(this),

                    blur : function() {
                        this.fireEvent('blur', [this]);
                    }.bind(this),

                    focus : function() {
                        this.fireEvent('focus', [this]);
                    }.bind(this)
                }
            });

            this.el.Elm = Elm;

            this.$FX = new Fx.Morph( Elm );
            this.$Settings.draw();

            this.fireEvent('drawEnd', [this]);

            return this.el.Elm;
        },

        /**
         * Get the DOMNode Object
         *
         * @method QUI.classes.desktop.Starter#getElm
         * @return {DOMNode}
         */
        getElm : function()
        {
            return this.el.Elm;
        },

        /**
         * The Parent Desktop Objekt
         *
         * @method QUI.classes.desktop.Starter#getDesktop
         * @return {QUI.classes.desktop.Desktop}
         */
        getDesktop : function()
        {
            return this.$Desktop;
        },

        /**
         * Show the Starter
         *
         * @method QUI.classes.desktop.Starter#show
         * @return {this}
         */
        show : function()
        {
            if (this.$FX) {
                this.$FX.cancel();
            }

            this.$FX.start({
                display : 'inline',
                opacity : 1
            });

            return this;
        },

        /**
         * Hide the Starter
         *
         * @method QUI.classes.desktop.Starter#hide
         * @return {this}
         */
        hide : function()
        {
            this.$FX.start({
                display : 'none',
                opacity : 1
            });

            return this;
        },

        /**
         * Close the Starter
         *
         * @method QUI.classes.desktop.Starter#close
         * @return {this}
         */
        close : function()
        {
            this.fireEvent('close', [this]);
            this.destroy();

            return this;
        },

        /**
         * Set the Focus to the Starter Object
         *
         * @method QUI.classes.desktop.Starter#focus
         * @return {this}
         */
        focus : function()
        {
            this.getElm().focus();

            return this;
        },

        /**
         * Opens the Settings of the Starter Object
         * @method QUI.classes.desktop.Starter#openSettings
         */
        openSettings : function()
        {
            new QUI.classes.Windows.Submit({
                title  : this.getAttribute('title') +' - Starter Einstellungen',
                icon   : URL_BIN_DIR +'16x16/settings.png',
                height : 220,
                width  : 450,
                Widget : this,
                events :
                {
                    onDrawEnd : function(Win, MuiWin)
                    {
                        var i, len;
                        var Widget = Win.getAttribute('Widget');

                        Win.getBody().set('html',
                            '<form name="starter-settings" action="">' +
                                '<table class="data-table">' +
                                    '<tr>' +
                                        '<td><label for="title">Titel</label></td>' +
                                        '<td><input type="text" name="title" value="'+ Widget.getAttribute('title') +'" /></td>' +
                                    '</tr>' +
                                '</table>' +
                            '</form>'
                        );

                        var id     = Win.getId(),
                            Body   = Win.getBody(),
                            Frm    = Body.getElement('form'),
                            labels = Body.getElements('label'),
                            elms   = Body.getElements('input');

                        Frm.addEvent('submit', function(event) {
                            event.stop();
                        });

                        for (i = 0, len = elms.length; i < len; i++) {
                            elms[i].set('id', elms[i].get('name') +'-'+ id);
                        }

                        for (i = 0, len = labels.length; i < len; i++) {
                            labels[i].set('for', labels[i].get('for') +'-'+ id);
                        }

                        Frm.elements.title.focus();
                    },

                    onSubmit : function(Win)
                    {
                        var Frm    = Win.getBody().getElement('form'),
                            Widget = Win.getAttribute('Widget');

                        if ( Frm.elements.title.value !== '' ) {
                            Widget.setAttribute('title', Frm.elements.title.value);
                        }
                    }
                }
            }).create();
        },

        /**
         * Mouseenter - Hover Effekt
         *
         * @method QUI.classes.desktop.Starter#mouseenter
         * @return {this}
         */
        mouseenter : function()
        {
            this.$mouseover = true;
            this.getElm().addClass('desktop-starter-hover');

            if (this.$Desktop &&
                this.$Desktop.getAttribute('lock-widget-settings') === true)
            {
                return this;
            }

            (function()
            {
                if (!this.$mouseover) {
                    return;
                }

                this.$Settings.show();
                this.focus();

            }).delay(500, this);

            return this;
        },

        /**
         * Mouseleave - Hover Effekt
         *
         * @method QUI.classes.desktop.Starter#mouseleave
         * @return {this}
         */
        mouseleave : function()
        {
            this.$mouseover = false;
            this.getElm().removeClass('desktop-starter-hover');

            return this;
        }
    });

    return QUI.classes.desktop.Starter;
});