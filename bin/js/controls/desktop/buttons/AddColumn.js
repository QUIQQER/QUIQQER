
/**
 * Button - Add a Column
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/buttons/AddColumn
 * @package com.pcsg.qui.js.controls.desktop
 * @namespace QUI.controls.desktop.buttons
 */

define('controls/desktop/buttons/AddColumn', [

    'controls/buttons/Button',
    'controls/desktop/Workspace',
    'controls/desktop/Column'

], function(QUI_Button)
{
    QUI.namespace( 'controls.desktop.buttons' );

    /**
     * @class QUI.controls.desktop.buttons.AddColumn
     *
     * @param {QUI.controls.desktop.Workspace} Workspace
     * @param {Object} options - buttn options
     *
     * @memberof! <global>
     */
    QUI.controls.desktop.buttons.AddColumn = new Class({

        Extends : QUI_Button,
        Type    : 'QUI.controls.desktop.buttons.AddColumn',

        Binds : [
            '$onClick'
        ],

        initialize : function(Workspace, options)
        {
            this.setAttributes({
                alt    : 'Bearbeitungsspalte hinzufügen',
                title  : 'Bearbeitungsspalte hinzufügen',
                image  : URL_BIN_DIR +'22x22/add_column.png',
                styles : {
                    'float' : 'right',
                    margin  : 5
                }
            });

            this.parent( options );
            this.$Workspace = Workspace;

            this.addEvents({
                onClick : this.$onClick
            });
        },

        /**
         * event: button click
         */
        $onClick : function()
        {
            if ( this.$Workspace ) {
                this.addColumnToWorkspace( this.$Workspace );
            }
        },

        /**
         * Add a column to a workspace
         *
         * @param {QUI.controls.desktop.Workspace} Workspace
         */
        addColumnToWorkspace : function(Workspace)
        {
            var content_width = Workspace.getElm().getSize().x;

            Workspace.appendChild(
                new QUI.controls.desktop.Column({
                    name        : 'colum',
                    width       : 300,
                    resizeLimit : [200, content_width - 210],
                    closable    : true,
                    events      :
                    {
                        onCreate : function(Column)
                        {
                            require(['controls/desktop/Tasks'], function(Taskpanel)
                            {
                                this.appendChild(
                                    new Taskpanel({
                                        name : 'task-panel'
                                    })
                                );
                            }.bind( Column ));
                        }
                    }
                })
            );
        }
    });

    return QUI.controls.desktop.buttons.AddColumn;
});