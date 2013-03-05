/**
 * QUI.Bookmarks
 *
 * provides shortcuts to dispose
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Bookmarks
 * @package com.pcsg.qui.js.bookmars
 * @namespace QUi
 */

define('Bookmarks', function()
{
    QUI.Bookmarks = {

        /**
         * Opens the users panel
         */
        openUsers : function()
        {
            require(['controls/users/Panel'], function()
            {
                var Parent = QUI.Controls.get( 'content-panel' )[0];

                Parent.appendChild(
                    new QUI.controls.users.Panel({
                        name : 'user-panel'
                    })
                );
            });
        },

        /**
         * Opens the groups panel
         */
        openGroups : function()
        {
            require(['controls/groups/Panel'], function()
            {
                var Parent = QUI.Controls.get( 'content-panel' )[0];

                Parent.appendChild(
                    new QUI.controls.groups.Panel({
                        name : 'groups-panel'
                    })
                );
            });
        },

        /**
         * Opens the trash panel
         */
        openTrash : function()
        {
            require(['controls/trash/Panel'], function(Trash)
            {
                var Parent = QUI.Controls.get( 'content-panel' )[0];

                Parent.appendChild(
                    new Trash({
                        name : 'trash-panel'
                    })
                );
            });
        }
    };

    return QUI.Bookmarks;
});