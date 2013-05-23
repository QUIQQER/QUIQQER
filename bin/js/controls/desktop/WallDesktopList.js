/**
 * The desktop list for the desktop wall control
 * List all available desktops of the session user
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/WallDesktopList
 * @package com.pcsg.qui.js.controls.desktop
 * @namespace QUI.controls.desktop
 */

define('controls/desktop/WallDesktopList', [

    'controls/Control',
    'controls/loader/Loader',
    'controls/desktop/Widget',

    'css!controls/desktop/WallDesktopList.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.desktop' );

    /**
     * @class QUI.controls.desktop.widget.List
     *
     * @param {QUI.controls.desktop.Wall} Wall
     * @param {Option} options
     *
     * @memberof! <global>
     */
    QUI.controls.desktop.WallDesktopList = new Class({

        Extends : Control,
        Type    : 'QUI.controls.desktop.WallDesktopList',

        Binds : [
            'hide',
            'show',
            '$fxComplete',
            '$onEntryClick',
            'openAddDesktop'
        ],

        initialize: function(Wall, options)
        {
            this.init( options );

            this.$Elm     = null;
            this.$Wall    = Wall || null;
            this.$Loader  = new QUI.controls.loader.Loader();
            this.$FX      = null;
        },

        /**
         * Return the Main DomNode Element
         *
         * @return {DOMNode} DIV
         */
        create : function()
        {
            if ( this.$Elm ) {
                return this.$Elm;
            }

            this.$Elm = new Element('div', {
                'class' : 'qui-desktop-list box',
                html    : '<div class="qui-desktop-list-content box">'+
                              '<div class="qui-desktop-list-close box smooth">' +
                                  '<span class="icon-remove-sign"></span> schließen'+
                              '</div>' +
                          '</div>',
                'data-qui' : this.getId()
            });

            this.$Loader.inject( this.$Elm );
            this.$FX = moofx( this.$Elm );


            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.$Elm.getElement( '.qui-desktop-list-close' ).addEvent(
                'click',
                this.hide
            );

            return this.$Elm;
        },

        /**
         * Return the binded wall
         *
         * @return {QUI.controls.desktop.Wall}
         */
        getWall : function()
        {
            return this.$Wall;
        },

        /**
         * Show the desktop list
         *
         * @return {this} self
         */
        show : function()
        {
            var Elm     = this.create(),
                Parent  = Elm.getParent(),
                size    = Parent.getSize();

            Elm.setStyles({
                visibility : null,
                left       : (size.x + 50) * -1,
                height     : size.y
            });

            Elm.setStyle( 'display', null );

            this.getWall().Loader.hide();

            this.$FX.animate({
                left : 0
            }, {
                callback : this.$fxComplete
            });

            return this;
        },

        /**
         * Hide the desktop list
         *
         * @return {this} self
         */
        hide : function()
        {
            if ( !this.getElm() ) {
                return;
            }

            var Elm    = this.getElm(),
                Parent = Elm.getParent(),
                size   = Parent.getSize();

            this.$FX.animate({
                left : (size.x + 50) * -1
            }, {
                callback : this.$fxComplete
            });

            return this;
        },

        /**
         * Opens the desktop add input field
         */
        openAddDesktop : function()
        {
            this.hide();

            var Control = this,

                Elm = new Element('div', {
                    'class' : 'qui-desktop-list box',
                    html    : '<div class="qui-desktop-add-content box">' +
                                  '<h3>Bitte geben Sie einen Namen für Ihren neuen Desktop</h3>' +
                                  '<form>'+
                                      '<input type="text" value="" name="" placeholder="Desktopnamen" class="box" />' +
                                  '</form>' +
                              '</div>',
                    styles : {
                        left : '-110%'
                    }
                }).inject( document.body );

            // form submit - desktop creation
            var submit_form = function(event)
            {
                if ( typeOf( event ) == 'domevent' ) {
                    event.stop();
                }

                moofx( Elm ).animate({
                    left : document.body.getSize().x * -1
                });

                Control.getWall().createDesktop(
                    Elm.getElement( 'input' ).value
                );
            };

            Elm.getElement( 'form' ).addEvent( 'submit', submit_form );

            // buttons
            new QUI.controls.buttons.Button({
                text    : 'anlegen',
                'class' : 'btn-green',
                styles  : {
                    width: 150,
                    padding: 10
                },
                events  : {
                    onClick : submit_form
                }
            }).inject( Elm.getElement( '.qui-desktop-add-content' ) );

            new QUI.controls.buttons.Button({
                text    : 'abbrechen',
                'class' : 'btn-red',
                styles  : {
                    width: 150,
                    padding: 10,
                    'float' : 'right'
                },
                events  :
                {
                    onClick : function()
                    {
                        moofx( Elm ).animate({
                            left : document.body.getSize().x * -1
                        }, {
                            callback : function()
                            {
                                //Elm.destroy();
                                Control.show();
                            }
                        });
                    }
                }
            }).inject( Elm.getElement( '.qui-desktop-add-content' ) );

            // show the popup
            moofx( Elm ).animate({
                left : 0
            }, {
                callback : function() {
                    Elm.getElement( 'input' ).focus();
                }
            });
        },

        /**
         * fx complete action
         * if list is closed or opened
         */
        $fxComplete : function()
        {
            if ( this.getElm().getStyle('left').toInt() >= 0 )
            {
                var Control = this,
                    Content = Control.getElm().getElement( '.qui-desktop-list-content' );

                Content.getElements( '.qui-desktop-list-entry' ).destroy();


                QUI.Ajax.get('ajax_desktop_get', function(result, Request)
                {
                    new Element('div', {
                        'class' : 'qui-desktop-list-entry box smooth',
                        html    : '<span>Desktop hinzufügen</span>' +
                                  '<span class="icon icon-check-empty"></span>',
                        events  : {
                            click : Control.openAddDesktop
                        }
                    }).inject( Content );


                    for ( var i = 0, len = result.length; i < len; i++ )
                    {
                        new Element('div', {
                            'class'    : 'qui-desktop-list-entry box smooth',
                            'data-did' : result[ i ].id,

                            html    : '<span>'+ result[ i ].title +'</span>' +
                                      '<span class="icon icon-chevron-sign-right"></span>',

                            events  : {
                                click : Control.$onEntryClick
                            }

                        }).inject( Content );
                    }
                });

                return;
            }

            this.fireEvent( 'close', [ this ] );
            //this.destroy();
        },

        /**
         * Click to open the desktop
         *
         * @param {DOMEvent} event
         */
        $onEntryClick : function(event)
        {
            this.hide();

            var Target = event.target;

            if ( !Target.hasClass( '.qui-desktop-list-entry' ) ) {
                Target = Target.getParent( '.qui-desktop-list-entry' );
            }

            if ( !Target ) {
                return;
            }

            this.getWall().openDesktop( Target.get( 'data-did' ) );
        }
    });

    return QUI.controls.desktop.WallDesktopList;
});