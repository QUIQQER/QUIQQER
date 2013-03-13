/**
 * A QUIQQER project
 *
 * @events onSiteStatusEditBegin
 * @events onSiteStatusEditEnd
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires
 *
 * @module classes/Project
 * @package com.pcsg.qui.js.classes
 * @namespace QUI.classes
 */

define('classes/Project', [

    'classes/DOM',
    'classes/project/Site',
    'classes/project/Media',
    'classes/project/Trash'

], function(QDOM, Site)
{
    /**
     * A project
     *
     * @class QUI.classes.Project
     *
     * @param {Object} options
     */
    QUI.classes.Project = new Class({

        Implements: [ QDOM ],

        options : {
            name : '',
            lang : 'de'
        },

        $ids   : {},
        $Media : null,
        $Trash : null,

        initialize : function(options)
        {
            this.init( options );
        },

        /**
         * Get a site from the project
         *
         * @method QUI.classes.Project#get
         *
         * @param {Integer} id - ID of the site
         * @return {QUI.classes.project.Site}
         */
        get : function(id)
        {
            if ( typeof this.$ids[ id ] === 'undefined' )
            {
                this.$ids[ id ] = new QUI.classes.project.Site( this, id );

                this.$ids[ id ].addEvent( 'onDelete', function(Site)
                {
                    this.deleteChild( Site.getId() );
                }.bind( this ) );
            }

            return this.$ids[ id ];
        },

        /**
         * Delete the child entry
         *
         * @method QUI.classes.Project#deleteChild
         *
         * @param {Integer} id - ID of the site
         * @return {this}
         */
        deleteChild : function(id)
        {
            if ( this.$ids[ id ] ) {
                delete this.$ids[ id ];
            }

            return this;
        },

        /**
         * Return the Media Object for the Project
         *
         * @method QUI.classes.Project#getMedia
         *
         * @return {QUI.classes.project.Media}
         */
        getMedia : function()
        {
            if ( !this.$Media ) {
                this.$Media = new QUI.classes.project.Media( this );
            }

            return this.$Media;
        },

        /**
         * Return the Trash Object for the Project
         *
         * @method QUI.classes.Project#getTrash
         *
         * @return {QUI.classes.project.Trash}
         */
        getTrash : function()
        {
            if ( !this.$Trash ) {
                this.$Trash = new QUI.classes.project.Trash( this );
            }

            return this.$Trash;
        },

        /**
         * Return the Project name
         *
         * @method QUI.classes.Project#getName
         *
         * @return {String}
         */
        getName : function()
        {
            if ( this.getAttribute( 'project' ) ) {
                return this.getAttribute( 'project' );
            }

            return this.getAttribute( 'name' );
        },

        /**
         * Return the Project lang
         *
         * @method QUI.classes.Project#getName
         *
         * @return {String}
         */
        getLang : function()
        {
            return this.getAttribute( 'lang' );
        }
    });

    return QUI.classes.Project;
});
