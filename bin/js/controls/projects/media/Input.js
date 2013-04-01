/**
 * A media input field
 * Set the media control on an input field
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/projects/media/Button
 *
 * @module controls/projects/media/Input
 * @package com.pcsg.qui.jscontrols.projects.media
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/Input', [

    'controls/Control',
    'controls/projects/media/Button',

    'css!controls/projects/media/Input.css'

], function(Control, QUI_MediaButton)
{
    "use strict";

    QUI.namespace( 'controls.projects.media' );

    /**
     * @class QUI.controls.projects.media.Input
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
     *
     * @param {Object} options
     * @param {DOMNode} Input - Input field [optional]
     *
     * @memberof! <global>
     */
    QUI.controls.projects.media.Input = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.projects.media.Input',

        Binds : [
            '$onChange',
            '$onDrop'
        ],

        options : {
            project : false,
            name    : '',
            type    : 'all' // folder, file, image, all
        },

        initialize : function(options, Input)
        {
            this.init( options );

            this.$Input = Input || null;
            this.$Elm   = null;
            this.$Text  = null;

            this.addEvent( 'onDrop', this.$onDrop );
            this.addEvent( 'onDragEnter', this.$onDragEnter );
            this.addEvent( 'onDragLeave', this.$onDragLeave );
        },

        /**
         * Create the button
         *
         * @method QUI.controls.projects.media.Input#create
         * @return {DOMNode}
         */
        create : function()
        {
            if ( !this.$Input )
            {
                this.$Input = new Element('input', {
                    name : this.getAttribute( 'name' )
                });
            }

            this.$Input.type = 'hidden';
            this.$Input.addEvents({
                change : this.$onChange
            });

            this.$Elm = new Element('div', {
                'class'      : 'qui-meda-input media-drop',
                'data-quiid' : this.getId()
            });

            this.$Elm.wraps( this.$Input );

            // create the media button
            new QUI.controls.projects.media.Button({
                events :
                {
                    onSubmit : function(result, Btn)
                    {
                        this.$Input.value = result;
                        this.load();
                    }.bind( this )
                }
            }).inject( this.$Elm ) ;

            this.$Text = new Element('div.media-btn-text', {
                styles : {
                    margin  : 5,
                    'float' : 'left'
                }
            });

            this.$Text.inject( this.$Elm );

            // load the user type name
            this.load();

            return this.$Elm;
        },

        /**
         * Load the user-type-name to the control
         *
         * @method QUI.controls.projects.media.Input#loadTypeName
         */
        load : function()
        {
            if ( this.$Input.value === '' )
            {
                this.$Text.set( 'html', '---' );
                return;
            }

            this.$Text.set(
                'html',
                '<img src="'+ URL_BIN_DIR +'images/loader.gif" />'
            );

            var val = this.$Input.value.toString();

            if ( !val.match( 'image.php' ) )
            {
                this.$Text.set( 'html', val );
                return;
            }

            QUI.Ajax.get('ajax_media_url_rewrited', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                if ( Control.$Text ) {
                    Control.$Text.set( 'html', result );
                }
            }, {
                fileurl : val,
                Control : this
            });
        },

        /**
         * Return the value
         *
         * @method QUI.controls.projects.media.Input#getValue
         * @return {String}
         */
        getValue : function()
        {
            return this.$Input.value;
        },

        /**
         * Set the value
         *
         * @method QUI.controls.projects.media.Input#setValue
         * @param {String|Object} param - image.php?**** || {project:'', id: ''}
         */
        setValue : function(param)
        {
            if ( typeOf( param ) == 'string' )
            {
                this.$Input.value = param;
                this.load();

                return;
            }

            // no string

        },

        /**
         * event: if the input value change
         *
         * @event {DOMEvent} event - the onChange event
         */
        $onChange : function(event)
        {
            this.setValue( event.target.value );
        },

        /**
         * event: if something is droped from the media center
         *
         * @param {Array} list - list of draged media elements
         */
        $onDrop : function(list)
        {
            this.$Elm.removeClass( 'qui-media-drag' );

            if ( !list.length ) {
                return;
            }

            this.setValue( list[ 0 ].url );
        },

        /**
         * event: if something is draged over the element
         * onDragEnter
         */
        $onDragEnter : function()
        {
            this.$Elm.addClass( 'qui-media-drag' );
        },

        /**
         * event: if something is draged out the element
         * onDragLeave
         */
        $onDragLeave : function()
        {
            this.$Elm.removeClass( 'qui-media-drag' );
        }
    });

    return QUI.controls.projects.media.Input;
});