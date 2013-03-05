
/**
 * Apppanel
 *
 * @events onResize
 * @events onDrawEnd
 * @events onHide
 * @events onShow
 * @events onContextMenu [Menu, this]
 * @events onDestroy [this]
 *
 *
 * Appanel-Sheet
 *
 * Events:
 * @events onClose
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require classes/DOM
 * @require mocha/Controls/panel/panel
 * @require controls/loader/Loader
 */

define('mocha/Controls/apppanel/apppanel', [

    'classes/DOM',
    'mocha/Controls/panel/panel',
    'controls/loader/Loader'

], function()
{
    if ( typeof MUI.Apppanels === 'undefined' )
    {
        QUI.css( QUI.config('dir') +'mocha/Controls/apppanel/Apppanel.css' );

        MUI.Apppanels = Object.append((MUI.Apppanels || {}),
        {
            panels   : {},
            focusing : false,

            get : function(id)
            {
                if ( this.panels[ id ] ) {
                    return this.panels[ id ];
                }

                return false;
            },

            destroy : function(id)
            {
                if ( this.panels[ id ] )
                {
                    this.panels[ id ].destroy();
                    this.panels[ id ] = null;
                }
            },

            // create an apppanel if not exist
            create : function(options)
            {
                var Panel = this.get( options.id );

                if ( Panel )
                {
                    Panel.setAttributes( options );
                    Panel.removeEvents( 'onDrawEnd' );

                    if ( options.onDrawEnd ) {
                        Panel.addEvent( 'onDrawEnd', options.onDrawEnd );
                    }

                    Panel.clear();
                    Panel.refresh();
                    Panel.fireEvent( 'onDrawEnd', [Panel] );

                    return;
                }

                new MUI.Apppanel( options );
            },

            getPanelByTab : function(Tab)
            {
                return MUI.Apppanels.get(
                    Tab.id.replace('_taskbarTab', '')
                );
            }
        });
    }


    MUI.Apppanel = new NamedClass('MUI.Apppanel', {

        Implements: [QUI.classes.DOM],

        options: {
            id          : '',    // id of the primary element, and id os control that is registered with mocha
            title       : '',
            icon        : false,
            container   : null,  // the parent control in the document to add the control to
            cssClass    : false, // css tag to add to control
            minimizable : true,
            tabbar      : false,
            breadcrumb  : false,
            buttons     : true,
            params      : {},
            closeable   : true,
            dragable    : true,

            'tabbar-width'     : false,
            'buttonbar-width'  : false,
            'breadcrumb-width' : false
        },

        initialize: function(options)
        {
            this.init( options );
            //this.setAttributes(options);

            this.el     = {};
            this.btns   = [];
            this.$Morph = null;

            // If Apppanel has no ID, give it one.
            this.options.id = this.options.id || 'apppanel' + (++MUI.idCount);
            this.id         = this.options.id;

            MUI.set( this.id, this );
            MUI.Apppanels.panels[ this.id ] = this;

            var Container = MUI.get( this.options.container );

            if ( Container.isTypeOf( 'MUI.Panel' ) ) {
                Container.addEvent('onResize', this.resize.bind( this ));
            }

            if ( options.onDrawEnd ) {
                this.addEvent( 'onDrawEnd', options.onDrawEnd );
            }

            // Loader
            this.Loader = new QUI.controls.loader.Loader();

            this.draw();
        },

        destroy : function()
        {
            for ( var e in this.el )
            {
                if ( this.el[ e ] ) {
                    this.el[ e ].destroy();
                }
            }

            if ( this.$ContextMenu ) {
                this.$ContextMenu.destroy();
            }

            if ( this.Buttons ) {
                this.Buttons.destroy();
            }

            if ( this.Tabs ) {
                this.Tabs.destroy();
            }

            if ( this.Breadcrumb ) {
                this.Breadcrumb.destroy();
            }

            this.fireEvent('destroy', [this]);
        },

        refresh: function()
        {
            if ( !this.el.element ) {
                return;
            }

            this.el.title.set('html', this.options.title);

            if ( this.getAttribute('icon') )
            {
                this.el.header.getElement('.title').setStyles({
                    background  : 'url('+ this.getAttribute('icon') +') no-repeat left center',
                    paddingLeft : 20
                });

                /*
                new Element('img.taskbarImage', {
                    src : this.getAttribute('icon')
                }).inject(this.el.title, 'top');
                */
            }

            if ( this.taskbar ) {
                this.taskbar.refreshTab( this );
            }
        },

        clear: function()
        {
            this.btns = [];

            if ( this.Buttons ) {
                this.Buttons.clear();
            }

            if ( this.Tabs ) {
                this.Tabs.clear();
            }
        },

        draw: function()
        {
            if ( typeof this.el.element !== 'undefined' ) {
                return;
            }

            require([
                'controls/buttons/Button', 'controls/buttons/Seperator',
                'controls/toolbar/Bar', 'controls/toolbar/Tab',
                'controls/breadcrumb/Bar', 'controls/breadcrumb/Item'
            ], function()
            {
                this._draw();
            }.bind(this));
        },

        _draw: function(container)
        {
            this.fireEvent('drawBegin', [this]);

            var Container;
            var o = this.options;

            if ( !container ) {
                container = o.container;
            }

            if ( MUI.desktop && MUI.desktop.taskbar ) {
                this.taskbar = MUI.desktop.taskbar;
            }

            MUI.Windows.indexLevel++;

            this.isMinimized = true;
            this.el.element  = new Element('div', {
                'class' : this.cssClass ? this.cssClass : '',
                id      : o.id,
                styles  : {
                    '-webkit-box-sizing': 'border-box',
                       '-moz-box-sizing': 'border-box',
                            'box-sizing': 'border-box',
                    position : 'absolute',
                    width    : '100%',
                    height   : '100%',
                    display  : 'float',
                    top      : 0,
                    left     : document.body.getSize().x * -1,
                    zIndex   : 1
                }
            });

            this.el.header = new Element('div', {
                'class' : 'panel-header toolbardock',
                'html'  : '' +
                    '<div class="btns toolbar left"></div>' +
                    '<div class="title" style="position: absolute; right: 0; top: 0;">' +
                        '<h2>'+ o.title +'</h2>' +
                    '</div>'
            });

            this.el.tabs = new Element('div.mui-apppanel-tabs', {
                styles : {
                    margin : '10px 5px 0',
                    height : 20,
                    'border-bottom' : '1px solid #C9C8C5'
                }
            });

            this.el.breadcrumb = new Element('div', {
                'class' : 'mui-apppanel-breadcrumb box',
                styles : {
                    margin : '0 0 0 10px',
                    'border-bottom' : '1px solid #C9C8C5',
                    'float' : 'left',
                    clear   : 'both'
                }
            });

            this.el.content = new Element('div.mui-apppanel-content', {
                styles : {
                    overflow  : 'hidden',
                    overflowY : 'auto',
                    position  : 'relative',
                    clear     : 'both'
                },
                events :
                {
                    mousedown : function(event)
                    {
                        // @todo please delete mousedown event

                        if ( !event.target )
                        {
                            event.stop();
                            return;
                        }

                        var nodeName = event.target.nodeName.toLowerCase();

                        if ( nodeName === 'input' ||
                             nodeName === 'textarea' ||
                             nodeName === 'select' )
                        {
                            return;
                        }

                        event.target.focus();

                        if ( document.activeElement != event.target ) {
                            document.body.focus();
                        }

                        event.stop();
                    },

                    contextmenu : function(event)
                    {
                        event.stop();

                        var Menu = this.getContextMenu();

                        Menu.setPosition(
                            event.page.x,
                            event.page.y
                        );

                        Menu.show();
                        Menu.focus();

                        this.fireEvent('contextMenu', [Menu, this]);

                    }.bind( this )
                }
            });

            this.el.title    = this.el.header.getElement('h2');
            this.el.btns     = this.el.header.getElement('.btns');
            this.el.windowEl = this.el.element;
            this.el.windowEl.store('instance', this);

            if ( this.getAttribute('icon') )
            {
                this.el.header.getElement('.title').setStyles({
                    background  : 'url('+ this.getAttribute('icon') +') no-repeat left center',
                    paddingLeft : 20
                });

                /*
                new Element('img.taskbarImage', {
                    src : this.getAttribute('icon')
                }).inject(this.el.title, 'top');
                */
            }

            this.el.header.inject( this.el.element );
            this.el.tabs.inject( this.el.element );
            this.el.breadcrumb.inject( this.el.element );
            this.el.content.inject( this.el.element );
            this.el.element.inject( container );

            this.Loader.create().inject( this.el.element );

            // Tabbar
            this.Tabs = new QUI.controls.toolbar.Bar({
                width : this.el.element.getSize().x - 50,
                type  : 'tabbar'
            });

            this.Tabs.inject(
                this.el.tabs
            );

            // breadcrumb
            this.Breadcrumb = new QUI.controls.breadcrumb.Bar({
                width : this.el.element.getSize().x - 50
            });

            this.Breadcrumb.inject(
                this.el.breadcrumb
            );

            // Buttons
            this.Buttons = new QUI.controls.toolbar.Bar({
                width : this.el.element.getSize().x - 50,
                slide : false,
                type  : 'buttons',
                'menu-button' : false
            });

            this.Buttons.inject(
                this.el.btns
            );

            // Tab in der Taskbar erstellen
            if ( this.taskbar )
            {
                if ( !this.taskbar.getTab(this) ) {
                    this.taskbar.createTask( this );
                }

                if ( this.getAttribute( 'closeable' ) )
                {
                    this.taskbar.getTab( this ).addEvent('tabClose', function(Tab)
                    {
                        this.close();
                    }.bind(this));
                }
            }

            if ( o.tabbar === false ) {
                this.el.tabs.setStyle('display', 'none');
            }

            if ( o.breadcrumb === false ) {
                this.el.breadcrumb.setStyle('display', 'none');
            }

            if ( o.buttons === false ) {
                this.el.btns.setStyle('display', 'none');
            }

            this.resize();
            this.fireEvent('drawEnd', [this]);
            return this;
        },

        resize: function()
        {
            if (!this.el.element) {
                return;
            }

            if (!this.el.element.getParent()) {
                return;
            }

            var Parent;
            var Container = this.el.element.getParent(),
                size      = Container.getSize(),
                height    = size.y,
                width     = size.x,
                cheight   = 0;

            if ( height === 0 )
            {
                Parent = Container.getParent('.column');

                if (Parent) {
                    height = Parent.getSize().y;
                }
            }

            var Wrapper = Container.getElement('.taskbarWrapper');

            if ( Wrapper )
            {
                height = height - Wrapper.getSize().y;
                Wrapper.setStyle('zIndex', 2);
            }

            cheight = height - 30;

            if ( this.el.tabs.getStyle('display') != 'none' ) {
                cheight = cheight - this.el.tabs.getSize().y - 25;
            }

            if ( this.el.breadcrumb.getStyle('display') != 'none' ) {
                cheight = cheight - this.el.tabs.getSize().y - 40;
            }

            this.el.content.setStyle('height', cheight);
            this.el.element.setStyle('height', height);

            // tabbar resize
            if ( this.getAttribute('tabbar-width') )
            {
                this.Tabs.setAttribute('width', this.getAttribute('tabbar-width'));
            } else
            {
                this.Tabs.setAttribute('width', width - 50);
            }

            this.Tabs.resize();

            // breadcrumb resize
            if ( this.getAttribute('breadcrumb-width') )
            {
                this.Breadcrumb.setAttribute('width', this.getAttribute('breadcrumb-width'));
            } else
            {
                this.Breadcrumb.setAttribute('width', width - 50);
            }

            this.Breadcrumb.resize();

            // button resize
            if ( this.getAttribute('buttonbar-width') )
            {
                this.Buttons.setAttribute('width', this.getAttribute('buttonbar-width'));
            } else
            {
                this.Buttons.setAttribute('width', width - 50);
            }

            this.Buttons.resize();

            this.fireEvent('onResize', [this]);
        },

        hide: function(onfinish)
        {
            if ( this.$Morph ) {
                return;
            }

            this.fireEvent('onHide', [this]);

            this.$Morph = new Fx.Morph(this.el.element, {
                onComplete : (onfinish || function(onfinish)
                {
                    if ( MUI.Apppanels.focusing == this ) {
                        MUI.Apppanels.focusing = false;
                    }

                    this.isMinimized = true;
                    this.$Morph      = null;
                    this.el.element.removeClass('isFocused');

                    this.el.element.setStyles({
                        visibility : 'hidden',
                        left       : document.body.getSize().x * -10
                    });

                }).bind(this)
            });

            this.$Morph.start({
                left : document.body.getSize().x * -1
            });

            return this;
        },

        show: function(onfinish)
        {
            if ( MUI.Apppanels.focusing &&
                 MUI.Apppanels.focusing.getId() == this.getId() )
            {
                if ( typeOf(onfinish) === 'function' ) {
                    onfinish(this);
                }

                this.fireEvent('onShow', [this]);

                return this;
            }

            if ( MUI.Apppanels.focusing ) {
                MUI.Apppanels.focusing.hide();
            }

            if ( this.$Morph ) {
                return this;
            }

            this.fireEvent('onShow', [this]);

            if ( typeof this.el.element === 'undefined' ) {
                return this;
            }

            this.el.element.setStyles({
                visibility : 'visible',
                left       : document.body.getSize().x * -1
            });

            this.$Morph = new Fx.Morph(this.el.element, {
                onComplete : function()
                {
                    MUI.Apppanels.focusing = this;

                    this.isMinimized = false;
                    this.$Morph      = null;
                    this.el.element.addClass('isFocused');

                    if ( this.taskbar ) {
                        this.taskbar.makeTabActive( this );
                    }

                    if ( typeOf(onfinish) === 'function' ) {
                        onfinish(this);
                    }
                }.bind( this )
            });

            this.$Morph.start({
                left : 0
            });

            return this;
        },

        minimize: function()
        {
            this.hide();
        },

        _restoreMinimized: function()
        {
            this.show();
        },

        focus : function()
        {
            this.show();
        },

        close : function()
        {
            MUI.Apppanels.destroy( this.id );
        },

        getBody : function()
        {
            return this.el.content;
        },

        /**
         * Button Methoden
         */
        addButton : function(btn)
        {
            var Btn;

            if ( btn.type == 'QUI.controls.buttons.Seperator' || btn.type == 'seperator' )
            {
                Btn = new QUI.controls.buttons.Seperator( btn );
            } else
            {
                Btn = new QUI.controls.buttons.Button( btn );
            }

            Btn.setAttribute('Panel', this);

            this.Buttons.appendChild( Btn );

            return this;
        },

        clearButtons : function()
        {
            this.Buttons.clear();

            return this;
        },

        getButtonBar : function()
        {
            return this.Buttons;
        },

        /**
         * Get the context menu
         *
         * @method MUI.Apppanel#getContextMenu
         * @return {QUI.controls.contextmenu.Menu}
         */
        getContextMenu : function()
        {
            if ( typeof this.$ContextMenu !== 'undefined' ) {
                return this.$ContextMenu;
            }

            // context menu
            this.$ContextMenu = new QUI.controls.contextmenu.Menu({
                title  : this.options.title,
                events :
                {
                    blur : function(Menu) {
                        Menu.hide();
                    }
                }
            });

            this.$ContextMenu.inject( document.body );

            return this.$ContextMenu;
        },

        /**
         * Ein einzelnes Blatt erstellen und anzeigen lassen
         *
         * @param {Function} onfinish -> [Sheet, Content, Buttons]
         * @param {Function} onsubmit -> [params]
         */
        openSheet : function(onfinish, onsubmit)
        {
            var Buttons, Content;

            var Parent = this.el.content,
                width  = Parent.getSize().x,
                height = Parent.getSize().y,

                Sheet  = new Element('div.pannelsheet', {
                    html : '<div class="pannelsheet-content"></div>' +
                           '<div class="pannelsheet-buttons">' +
                                '<div></div>' +
                           '</div>',

                    styles : {
                        width    : width - 2,
                        height   : height -2,
                        position : 'absolute',
                        top      : 0,
                        left     : -1 * (Parent.getSize().y + 50),
                        zIndex   : 5,
                        background : 'rgba(0,0,0, 0.5)',
                        border: 'none'
                    }
                });

            Sheet.inject( Parent );
            Sheet.addEvent('close', function()
            {
                new Fx.Morph(this, {
                    onComplete : function(Sheet) {
                        Sheet.destroy();
                    }
                }).start({
                    left : this.getSize().x * -1
                });
            });

            Sheet.close = function() {
                this.fireEvent('close');
            }.bind( Sheet );

            Buttons = Sheet.getElement('.pannelsheet-buttons');
            Content = Sheet.getElement('.pannelsheet-content');

            Buttons.setStyles({
                borderTop : '1px solid #ddd',
                height    : 49,
                width     : width - 2,
                background: '#666',
                'float'   : 'left'
            });

            Buttons.getElement('div').setStyles({
                margin : '0 auto',
                width  : 200
            });

            Content.setStyles({
                height : Parent.getSize().y - 50,
                width  : width - 2,
                border : 'none',
                background: '#F8F8F8',
                'float' : 'left'
            });

            new QUI.controls.buttons.Button({
                text      : 'schließen / abbrechen',
                textimage : URL_BIN_DIR +'16x16/cancel.png',
                Sheet     : Sheet,
                Content   : Content,
                Buttons   : Buttons,
                onsubmit  : onsubmit,
                styles    : {
                    margin : '10px auto 0',
                    width  : 200
                },
                onclick  : function(Btn)
                {
                    var Sheet = Btn.getAttribute('Sheet');

                    if ( Btn.getAttribute('onsubmit') === false )
                    {
                        Sheet.fireEvent('close');
                        return;
                    }

                    var params = {
                        Sheet   : Sheet,
                        Content : Btn.getAttribute('Content'),
                        Buttons : Btn.getAttribute('Buttons')
                    };

                    if ( Btn.getAttribute('onsubmit')( params ) ) {
                        Sheet.fireEvent('close');
                    }
                }
            }).create().inject( Buttons.getElement('div') );

            new Fx.Morph(Sheet, {
                onComplete : function(Sheet)
                {
                    if (typeOf(onfinish) === 'function') {
                        onfinish(Sheet, Content, Buttons);
                    }
                }.bind(this)
            }).start({
                left : 0
            });
        }
    });

    return MUI.Apppanel;
});