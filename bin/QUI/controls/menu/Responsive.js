/**
 *
 */

define('controls/menu/Responsive', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/utils/Background',
    'qui/controls/loader/Loader',
    'Ajax',

    'css!controls/menu/Responsive.css'

], function(QUIControl, QUIButton, QUIBackground, QUILoader, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/menu/Responsive',

        options : {
            project : false,
            lang    : false,
            id      : false
        },

        initialize : function(options)
        {
            var self = this;

            this.parent( options );

            this.$Loader = new QUILoader();

            this.$Background = new QUIBackground({
                events :
                {
                    onClick : function() {
                        self.hide();
                    }
                }
            });

            this.$Back     = null;
            this.$Close    = null;
            this.$Content  = null;
            this.$parentId = 1;

            this.$show = false;
            this.$FX   = null;
        },

        /**
         * Create the DOMNode
         *
         * @return {DOMNode}
         */
        create : function()
        {
            var self = this;

            this.$Elm = new Element('div', {
                'class' : 'qui-controls-menu-responsive',
                html    : '<div class="qcm-responsive-title">'+
                              '<div class="qcm-responsive-title-home">' +
                                  '<span class="icon-home"></span>' +
                                  '<span>Menü</span>' +
                              '</div>' +
                              '<div class="qcm-responsive-title-close icon-remove"></div>' +
                          '</div>' +
                          '<div class="qcm-responsive-buttons"></div>' +
                          '<div class="qcm-responsive-content"></div>',
                styles : {
                    left : -500,
                    position : 'fixed',
                    width : 400
                }
            });

            this.$Buttons = this.$Elm.getElement( '.qcm-responsive-buttons' );
            this.$Close   = this.$Elm.getElement( '.qcm-responsive-title-close' );
            this.$Content = this.$Elm.getElement( '.qcm-responsive-content' );

            // home button
            this.$Elm.getElement( '.qcm-responsive-title-home' ).addEvents({
                click : function() {
                    self.showChildren( 1 );
                }
            });


            this.$Back = new QUIButton({
                text : 'Zurück',
                textimage : 'icon-double-angle-left',
                events :
                {
                    onClick : function() {
                        self.back();
                    }
                }
            }).inject( this.$Buttons );

            new QUIButton({
                text : 'Suche',
                textimage : 'icon-search'
            }).inject( this.$Buttons );

            this.$Close.addEvents({
                click : function() {
                    self.hide();
                }
            });

            this.$Loader.inject( this.$Content );

            this.$FX = moofx( this.$Elm );

            return this.$Elm;
        },

        /**
         * toggle the menu
         */
        toggle : function()
        {
            if ( this.$show )
            {
                this.hide();
                return this;
            }

            this.show();
            return this;
        },

        /**
         * Show the menu
         */
        show : function()
        {
            this.$show = true;

            var self  = this,
                size  = document.body.getSize(),
                width = 400;

            if ( width > size.x * 0.9 ) {
                width = size.x * 0.9;
            }

            if ( !this.$Elm ) {
                this.inject( document.body );
            }

            if ( this.$Elm.getParent() == document.body )
            {
                this.$Elm.addClass( 'shadow' );

                if ( !this.$Background.getElm() ) {
                    this.$Background.inject( document.body );
                }

                this.$Background.show();
            }

            this.$Elm.setStyles({
                width : width
            });

            this.$Content.setStyles({
                height : size.y - 100
            });

            this.$Loader.show();

            this.$FX.animate({
                left : 0
            }, {
                callback : function() {
                    self.showChildren( self.getAttribute('id') );
                }
            });
        },

        /**
         * hide the menu
         */
        hide : function()
        {
            this.$show = false;
            this.$Background.hide();

            this.$FX.animate({
                left : -500
            });
        },

        /**
         * Display the children
         *
         * @param {Integer} siteid - ID of the parent site
         */
        showChildren : function(siteid)
        {
            var self = this;

            this.$Loader.show();

            Ajax.get([
                'ajax_project_sites_navigation',
                'ajax_project_parent'
            ], function(result, parentId)
            {
                self.$parentId = parentId;

                if ( siteid == 1 )
                {
                    self.$Back.disable();
                } else
                {
                    self.$Back.enable();
                }

                var i, len, entry, Text, Container;
                var size = self.$Content.getSize();

                self.$Content.set('html', '');

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    entry = result[ i ];

                    Container = new Element('div', {
                        'class'    : 'qcm-responsive-content-entry',
                        'data-id'  : entry.id,
                        'data-url' : entry.url
                    }).inject( self.$Content );

                    Text = new Element('div', {
                        'class' : 'qcm-responsive-content-entry-text smooth box',
                        html    : entry.title,
                        styles  : {
                            width : size.x - 60
                        },
                        events :
                        {
                            click : function()
                            {
                                var Parent = this.getParent(
                                    '.qcm-responsive-content-entry'
                                );

                                self.hide();

                                window.location = Parent.get( 'data-url' );
                            }
                        }
                    }).inject( Container );

                    if ( !( entry.hasChildren ).toInt() )
                    {
                        Text.setStyle( 'width', size.x );
                        continue;
                    }

                    new Element('div', {
                        'class' : 'qcm-responsive-content-entry-children smooth box',
                        html    : '<span class="icon-caret-right"></span>',
                        events  :
                        {
                            click : function()
                            {
                                var Parent = this.getParent(
                                    '.qcm-responsive-content-entry'
                                );

                                self.showChildren( Parent.get('data-id') );
                            }
                        }
                    }).inject( Container );
                }

                self.$Loader.hide();

            }, {
                project : self.getAttribute( 'project' ),
                lang    : self.getAttribute( 'lang' ),
                id      : siteid
            });
        },

        /**
         * opens the parent site
         */
        back : function()
        {
            this.showChildren( this.$parentId );
        }
    });
});