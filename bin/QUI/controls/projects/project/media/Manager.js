/**
 * Media Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/media/Manager
 */

define('controls/projects/project/media/Manager', [

    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/contextmenu/Item',
    'Ajax',
    'Projects',
    'utils/Template',

    'css!controls/projects/project/media/Manager.css'

], function(QUIPanel, QUIButton, QUIContextmenuItem, Ajax, Projects, UtilsTemplate)
{
    "use strict";

    /**
     * Media administration
     *
     * @class controls/projects/project/media/Manager
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/media/Manager',

        Binds : [
            'load',
            '$onCreate'
        ],

        options : {
            icon      : 'icon-picture',
            id        : 'projects-media-manager',
            name      : 'projects-media-manager',
            title     : 'Media Verwaltung',
            container : false,
            fileid    : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$MD5       = null;
            this.$MD5Start  = null;
            this.$SHA1      = null;
            this.$SHA1Start = null;

            this.addEvent( 'onCreate', this.$onCreate );
        },

        /**
         * Create the file panel
         *
         * @method controls/projects/project/media/Manager#create
         */
        $onCreate : function()
        {
            this.Loader.show();

            this.addCategory({
                title   : 'Allemein',
                text    : 'Allemein',
                image   : 'icon-picture',
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
            var self = this;

            this.Loader.show();

            UtilsTemplate.get('project_media_manager', function(result)
            {
                var Body = self.getContent();

                Body.set( 'html', result );

                var MD5Parent  = Body.getElement( '.md5hash' ),
                    SHA1Parent = Body.getElement( '.sha1hash' );


                self.$MD5 = new QUIButton({
                    name      : 'calcmd5',
                    text      : 'Projekt für MD5 Berechnung auswählen...',
                    textimage : 'icon-picture'
                }).inject( MD5Parent );

                self.$MD5Start = new QUIButton({
                    name    : 'calcmd5',
                    image   : 'icon-play',
                    title   : 'Berechnung starten',
                    alt     : 'Berechnung starten',
                    events  :
                    {
                        onClick : function(Btn)
                        {
                            Btn.setAttribute('image', 'icon-refresh icon-spin');

                            self.calcMD5(
                                self.$MD5.getAttribute('value'),
                                function() {
                                    Btn.setAttribute('image', 'icon-play');
                                }
                            );
                        }
                    }
                }).inject( MD5Parent );


                self.$SHA1 = new QUIButton({
                    name   : 'calcsha1',
                    text   : 'Projekt für SHA1 Berechnung auswählen...',
                    textimage : 'icon-picture'
                }).inject( SHA1Parent );

                self.$SHA1Start = new QUIButton({
                    name    : 'calcmd5',
                    image   : 'icon-play',
                    title   : 'Berechnung starten',
                    alt     : 'Berechnung starten',
                    Manager : this,
                    events  :
                    {
                        onClick : function(Btn)
                        {
                            Btn.setAttribute('image', 'icon-refresh icon-spin');

                            self.calcSHA1(
                                self.$SHA1.getAttribute('value'),
                                function() {
                                    Btn.setAttribute('image', 'icon-play');
                                }
                            );
                        }
                    }
                }).inject( SHA1Parent );


                self.$MD5.disable();
                self.$MD5Start.disable();

                self.$SHA1.disable();
                self.$SHA1Start.disable();


                Projects.getList(function(result)
                {
                    var event_click = function(Itm, event)
                    {
                        var Menu   = Itm.getParent(),
                            Button = Menu.getParent();

                        Button.setAttribute('text', Itm.getAttribute('text'));
                        Button.setAttribute('value', Itm.getAttribute('value'));

                        if ( Button.getAttribute('name') == 'calcmd5' ) {
                            self.$MD5Start.enable();
                        }

                        if ( Button.getAttribute('name') == 'calcsha1' ) {
                            self.$SHA1Start.enable();
                        }
                    };

                    for ( var project in result )
                    {
                        self.$MD5.appendChild(
                            new QUIContextmenuItem({
                                icon   : 'icon-picture',
                                text   : project,
                                value  : project,
                                events : {
                                    onMouseDown : event_click
                                }
                            })
                        );

                        self.$SHA1.appendChild(
                            new QUIContextmenuItem({
                                icon   : 'icon-picture',
                                text   : project,
                                value  : project,
                                events : {
                                    onMouseDown : event_click
                                }
                            })
                        );
                    }

                    self.$MD5.enable();
                    self.$MD5.setAttribute('textimage', 'icon-picture');

                    self.$SHA1.enable();
                    self.$SHA1.setAttribute('textimage', 'icon-picture');

                });

                self.Loader.hide();
            });
        },

        /**
         * Starts the MD5 calculation for the specific media
         *
         * @param {String} project - Project name of the media
         */
        calcMD5 : function(project, oncomplete)
        {
            Ajax.post('ajax_media_create_md5', oncomplete, {
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
            Ajax.post('ajax_media_create_sha1', oncomplete, {
                project : project
            });
        }
    });
});
