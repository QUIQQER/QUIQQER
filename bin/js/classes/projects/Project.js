/**
 * A QUIQQER project
 *
 * @events onSiteStatusEditBegin
 * @events onSiteStatusEditEnd
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires classes/projects/Site
 * @requires classes/projects/Media
 * @requires classes/projects/Trash
 *
 * @module classes/projects/Project
 * @package com.pcsg.qui.js.classes.projects
 * @namespace QUI.classes.projects
 *
 * @events onSiteDelete [this, {Integer}]
 * @events onSiteSave [this, {QUI.classes.projects.Site}]
 */

define('classes/projects/Project', [

    'classes/DOM',
    'classes/projects/Site',
    'classes/projects/Media',
    'classes/projects/Trash'

], function(QDOM, Site)
{
    "use strict";

    QUI.namespace( 'classes.projects' );

    /**
     * A project
     *
     * @class QUI.classes.Project
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.classes.projects.Project = new Class({

        Implements : [ QDOM ],
        Type       : 'QUI.classes.projects.Project',

        Binds : [
            '$onChildDelete',
            '$onSiteSave',
            '$onSiteCreate'
        ],

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
         * @param {Integer} id - ID of the site
         * @return {QUI.classes.projects.Site}
         */
        get : function(id)
        {
            var Site = this.$ids[ id ];

            if ( typeof Site !== 'undefined' ) {
                return Site;
            }

            Site = new QUI.classes.projects.Site( this, id );
            Site.addEvents({
                'onDelete' : this.$onSiteDelete,
                'onSave'   : this.$onSiteSave,
                'onActivate'    : this.$onSiteSave,
                'onDeActivate'  : this.$onSiteSave,
                'onCreateChild' : this.$onCreateChild
            });

            this.$ids[ id ] = Site;

            return this.$ids[ id ];
        },

        /**
         * Return the Media Object for the Project
         *
         * @method QUI.classes.Project#getMedia
         * @return {QUI.classes.projects.Media}
         */
        getMedia : function()
        {
            if ( !this.$Media ) {
                this.$Media = new QUI.classes.projects.Media( this );
            }

            return this.$Media;
        },

        /**
         * Return the Trash Object for the Project
         *
         * @method QUI.classes.Project#getTrash
         * @return {QUI.classes.projects.Trash}
         */
        getTrash : function()
        {
            if ( !this.$Trash ) {
                this.$Trash = new QUI.classes.projects.Trash( this );
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
         * @return {String}
         */
        getLang : function()
        {
            return this.getAttribute( 'lang' );
        },

        /**
         * event : on Site deletion
         *
         * @method QUI.classes.Project#$onChildDelete
         * @param {QUI.classes.projects.Site} Site
         * @return {this}
         * @fires siteDelete
         */
        $onSiteDelete : function(Site)
        {
            var id = Site.getId();

            if ( this.$ids[ id ] ) {
                delete this.$ids[ id ];
            }

            this.fireEvent( 'siteDelete', [ this, id ] );

            return this;
        },

        /**
         * event : on Site saving
         *
         * @param {QUI.classes.projects.Site} Site
         * @fires siteSave
         */
        $onSiteSave : function(Site)
        {
            this.fireEvent( 'siteSave', [ this, Site ] );
        },

        /**
         * event : on Site create
         *
         * @param {QUI.classes.projects.Site} Site
         * @fires siteCreate
         */
        $onSiteCreate : function(Site)
        {
            this.fireEvent( 'siteCreate', [ this, Site ] );
        }
    });

    return QUI.classes.projects.Project;
});
