/**
 * Media Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/media/Manager
 * @package com.pcsg.qui.js.controls.projects.media
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/Manager', [

    'controls/desktop/Panel',
    'controls/buttons/Button',

    'css!controls/projects/media/Manager.css'

], function(QUI_Panel)
{
    "use strict";

    QUI.namespace( 'controls.projects.media' );

    /**
     * Media administration
     *
     * @class QUI.controls.projects.media.Manager
     *
     * @param {Object} options
     */
    QUI.controls.projects.media.Manager = new Class({

        Implements : [QUI_Panel],
        Type       : 'QUI.controls.projects.media.Manager',

        Binds : [
            'load',
            '$onCreate'
        ],

        options : {
            icon      : URL_BIN_DIR +'16x16/media.png',
            id        : 'projects-media-manager',
            name      : 'projects-media-manager',
            title     : 'Media Verwaltung',
            container : false,
            fileid    : false
        },

        initialize : function(options)
        {
            this.init( options );
            this.addEvent( 'onCreate', this.$onCreate );
        },

        /**
         * Create the file panel
         *
         * @method QUI.controls.projects.media.Manager#create
         */
        $onCreate : function()
        {
            this.Loader.show();

            this.addCategory({
                title   : 'Allemein',
                text    : 'Allemein',
                image   : URL_BIN_DIR +'48x48/media.png',
                body    : '&nbsp;',
                Control : this,
                events  : {
                    onActive : this.load
                }
            });

            this.getCategoryBar().firstChild().onclick();
        },

        /**
         * Load the standard media administration
         */
        load : function()
        {
            this.Loader.show();

            QUI.Template.get('project_media_manager', function(result, Request)
            {
                var Body  = Request.getAttribute( 'Body' ),
                    Panel = Request.getAttribute( 'Panel' );

                Body.set( 'html', result );

                var MD5Parent  = Body.getElement( '.md5hash' ),
                    SHA1Parent = Body.getElement('.sha1hash');


                Panel.$MD5 = new QUI.controls.buttons.Button({
                    name      : 'calcmd5',
                    text      : 'Projekt für MD5 Berechnung auswählen...',
                    textimage : URL_BIN_DIR +'images/loader.gif'
                }).inject( MD5Parent );

                Panel.$MD5Start = new QUI.controls.buttons.Button({
                    name    : 'calcmd5',
                    image   : URL_BIN_DIR +'16x16/play.png',
                    title   : 'Berechnung starten',
                    alt     : 'Berechnung starten',
                    Manager : Panel,
                    events  :
                    {
                        onClick : function(Btn)
                        {
                            var Manager = Btn.getAttribute('Manager');

                            Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

                            Manager.calcMD5(
                                Manager.$MD5.getAttribute('value'),
                                function() {
                                    Btn.setAttribute('image', URL_BIN_DIR +'16x16/play.png');
                                }.bind( Btn )
                            );
                        }
                    }
                }).inject( MD5Parent );


                Panel.$SHA1 = new QUI.controls.buttons.Button({
                    name   : 'calcsha1',
                    text   : 'Projekt für SHA1 Berechnung auswählen...',
                    textimage : URL_BIN_DIR +'images/loader.gif'
                }).inject( SHA1Parent );

                Panel.$SHA1Start = new QUI.controls.buttons.Button({
                    name    : 'calcmd5',
                    image   : URL_BIN_DIR +'16x16/play.png',
                    title   : 'Berechnung starten',
                    alt     : 'Berechnung starten',
                    Manager : this,
                    events  :
                    {
                        onClick : function(Btn)
                        {
                            var Manager = Btn.getAttribute('Manager');

                            Btn.setAttribute('image', URL_BIN_DIR +'images/loader.gif');

                            Manager.calcSHA1(
                                Manager.$SHA1.getAttribute('value'),
                                function() {
                                    Btn.setAttribute('image', URL_BIN_DIR +'16x16/play.png');
                                }.bind( Btn )
                            );
                        }
                    }
                }).inject( SHA1Parent );


                Panel.$MD5.disable();
                Panel.$MD5Start.disable();

                Panel.$SHA1.disable();
                Panel.$SHA1Start.disable();


                QUI.Projects.getList(function(result)
                {
                    var event_click = function(Itm, event)
                    {
                        var Menu   = Itm.getParent(),
                            Button = Menu.getParent();

                        Button.setAttribute('text', Itm.getAttribute('text'));
                        Button.setAttribute('value', Itm.getAttribute('value'));

                        if ( Button.getAttribute('name') == 'calcmd5' ) {
                            this.$MD5Start.enable();
                        }

                        if ( Button.getAttribute('name') == 'calcsha1' ) {
                            this.$SHA1Start.enable();
                        }

                    }.bind( this );

                    for ( var project in result )
                    {
                        this.$MD5.appendChild(
                            new QUI.controls.contextmenu.Item({
                                icon   : URL_BIN_DIR +'16x16/media.png',
                                text   : project,
                                value  : project,
                                events : {
                                    onMouseDown : event_click
                                }
                            })
                        );

                        this.$SHA1.appendChild(
                            new QUI.controls.contextmenu.Item({
                                icon   : URL_BIN_DIR +'16x16/media.png',
                                text   : project,
                                value  : project,
                                events : {
                                    onMouseDown : event_click
                                }
                            })
                        );
                    }

                    this.$MD5.enable();
                    this.$MD5.setAttribute('textimage', URL_BIN_DIR +'16x16/media.png');

                    this.$SHA1.enable();
                    this.$SHA1.setAttribute('textimage', URL_BIN_DIR +'16x16/media.png');

                }.bind( Panel ));

                Panel.Loader.hide();

            }, {
                Panel : this,
                Body  : this.getBody()
            });
        },

        /**
         * Starts the MD5 calculation for the specific media
         *
         * @param {String} project - Project name of the media
         */
        calcMD5 : function(project, oncomplete)
        {
            QUI.Ajax.post('ajax_media_create_md5', oncomplete, {
                project : project
            });
        },

        /**
         * Starts the SHA1 calculation for the specific media
         *
         * @param {String} project - Project name of the media
         */
        calcSHA1 : function(project, oncomplete)
        {
            QUI.Ajax.post('ajax_media_create_sha1', oncomplete, {
                project : project
            });
        }
    });

    return QUI.controls.projects.media.Manager;
});
