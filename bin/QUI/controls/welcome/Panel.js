/**
 * The Welcome Quiqqer panel
 *
 * @author www.namerobot.com (Henning Leutz)
 * @module controls/welcome/Panel
 *
 * @requires qui/controls/desktop/Panel
 */

define('controls/welcome/Panel', [

    'qui/controls/desktop/Panel',
    'controls/projects/Manager',
    'utils/Panels'

], function(QUIPanel, ProjectManager, PanelUtils)
{
    "use strict";

    /**
     * @class controls/welcome/Panel
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/welcome/Panel',

        Binds : [
            '$onCreate'
        ],

        options : {
            icon  : 'icon-thumbs-up',
            title : 'Willkommen bei QUIQQER'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * Create the project panel body
         *
         * @method controls/welcome/Panel#$onCreate
         */
        $onCreate : function()
        {
            var Content = this.getContent();

            Content.set(
                'html',

                '<h1>Willkommen bei QUIQQER</h1>'+
                '<p>Ihre Installation war erfolgreich und Sie können QUIQQER nun erfolgreich nutzen.</p>'+

                '<h2>Wie geht es weiter?</h2>'+
                '<ul>'+
                    '<li>'+
                        '<a href="" class="create-project">'+
                            'Webseiten Projekt starten'+
                        '</a>'+
                    '</li>'+
                    '<li>'+
                        '<a href="http://doc.quiqqer.com/" target="_blank">'+
                            'Online Dokumentation öffnen'+
                        '</a>'+
                    '</li>'+
                '</ul>'+

                '<p>Wenn Sie Fragen haben oder Hilfe benötigen stehen Ihnen verschiedenen Möglichkeiten zur Verfügung.</p>'+

                '<h3>Hilfe für Benutzer</h3>'+
                '<ul>'+
                    '<li>'+
                        '<a href="http://doc.quiqqer.com/" target="_blank">'+
                            'Online Benutzer-Dokumentation öffnen'+
                        '</a>'+
                    '</li>'+
                '</ul>'+

                '<h3>Hilfe für Entwickler</h3>'+
                '<ul>'+
                    '<li>'+
                        '<a href="https://dev.quiqqer.com/quiqqer/quiqqer/wikis/home" target="_blank">'+
                            'Wiki'+
                        '</a>'+
                    '</li>'+
                    '<li>'+
                        '<a href="http://doc.quiqqer.com/api/dev/php/doc/" target="_blank">'+
                            'PHP Api'+
                        '</a>'+
                    '</li>'+
                    '<li>'+
                        '<a href="http://doc.quiqqer.com/qui/doc/" target="_blank">'+
                            'QUI API (QUIQQER User Interface)'+
                        '</a>'+
                    '</li>'+
                '</ul>'+

                '<h3>Kontakt aufnehmen</h3>'+
                '<ul>'+
                    '<li>Mail: support@pcsg.de</li>'+
                    '<li>IRC: #quiqqer on freenode</li>'+
                '</ul>'
            );

            Content.getElement( '.create-project' ).addEvent('click', function(event)
            {
                event.stop();

                PanelUtils.openPanelInTasks( new ProjectManager() );
            });

        }
    });
});