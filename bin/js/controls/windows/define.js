/**
 * Window Manager
 * With the Window Manager you can easily create Windows
 *
 * @module controls/windows
 * @package com.pcsg.qui.js.controls.windows
 * @namespace QUI
 *
 * @requires controls/windows/Window
 * @requires controls/windows/Submit
 * @requires controls/windows/Alert
 * @requires controls/windows/Prompt
 *
 * @example

QUI.Windows.get('submit', {
    name  : '',
    title : '',
    icon  : ''
});

QUI.Windows.create('window', {
    name  : '',
    title : '',
    icon  : ''
});

  @author www.pcsg.de (Henning Leutz)

 */

define('controls/windows', [

    'controls/windows/Window',
    'controls/windows/Submit',
    'controls/windows/Alert',
    'controls/windows/Prompt',
    'controls/windows/Upload'

], function(Win, Submit, Alert, Prompt)
{
    QUI.Windows =
    {
        /**
         * Get a window
         *
         * @method QUI.Windows#get
         * @param {String} TYPE     - alert, prompt, submit (default is a normal window)
         * @param {Object} params     - Window params
         *
         * @return QUI.controls.windows.{Window}
         */
        get : function(TYPE, params)
        {
            TYPE = TYPE.toLowerCase();

            switch (TYPE)
            {
                case 'alert':
                    return new QUI.controls.windows.Alert(params);

                case 'prompt':
                    return new QUI.controls.windows.Prompt(params);

                case 'submit':
                    return new QUI.controls.windows.Submit(params);

                default:
                    return new QUI.controls.windows.Window(params);
            }
        },

        /**
         * Create a window
         *
         * @method QUI.Windows#create
         * @param {String} TYPE     - alert, prompt, submit (default is a normal window)
         * @param {Object} params     - Window params
         *
         * @return QUI.controls.windows.{Window}
         */
        create : function(TYPE, params)
        {
            var Win = this.get(TYPE, params);
                Win.create();

            return Win;
        }
    };

    return QUI.Windows;
});
