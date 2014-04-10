/**
 * A QUIQQER project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/classes/DOM
 * @requires classes/projects/Site
 * @requires classes/projects/Media
 * @requires classes/projects/Trash
 *
 * @module classes/projects/Project
 *
 * @events onSiteDelete [this, {Integer}]
 * @events onSiteSave [this, {classes/projects/project/Site}]
 * @events onSiteCreate [this, {classes/projects/project/Site}]
 * @events onSiteActivate [this, {classes/projects/project/Site}]
 * @events onSiteDeactivate [this, {classes/projects/project/Site}]
 *
 * @todo Trash
 */

define('classes/projects/Project', [

    'qui/classes/DOM',
    'Ajax',
    'classes/projects/project/Site',
    'classes/projects/project/Media'

], function(QDOM, Ajax, ProjectSite, Media)
{
    "use strict";

    /**
     * A project
     *
     * @class classes/projects/project/Project
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QDOM,
        Type    : 'classes/projects/project/Project',

        Binds : [
            '$onChildDelete',
            '$onSiteSave',
            '$onSiteCreate',
            '$onSiteActivate',
            '$onSiteDeactivate',
            '$onSiteDelete'
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
            this.parent( options );
        },

        /**
         * Get a site from the project
         *
         * @method classes/projects/Project#get
         * @param {Integer} id - ID of the site
         * @return {classes/projects/project/Site}
         */
        get : function(id)
        {
            var Site = this.$ids[ id ];

            if ( typeof Site !== 'undefined' ) {
                return Site;
            }

            Site = new ProjectSite( this, id );

            Site.addEvents({
                'onDelete'      : this.$onSiteDelete,
                'onSave'        : this.$onSiteSave,
                'onActivate'    : this.$onSiteActivate,
                'onDeactivate'  : this.$onSiteDeactivate,
                'onCreateChild' : this.$onSiteCreate
            });

            this.$ids[ id ] = Site;
            this.$config    = null;

            return this.$ids[ id ];
        },

        /**
         * Return the configuration of the project
         *
         * @param {Function} callback - callback function
         * @param {String} param - param name
         */
        getConfig : function(callback, param)
        {
            if ( this.$config )
            {
                callback( this.$config );
                return;
            }

            Ajax.get('ajax_project_get_config', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }
            }, {
                project : this.getName(),
                param   : param || false
            });
        },

        /**
         * Set the config for a project
         * You can set a single config parameter or multible parameters
         *
         * @param {Function} callback
         * @param {Object} params - one ore more params
         */
        setConfig : function(callback, params)
        {
            Ajax.get('ajax_project_set_config', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result );
                }
            }, {
                project : this.getName(),
                params  : JSON.encode( params )
            });
        },

        /**
         * Return the Media Object for the Project
         *
         * @method classes/projects/Project#getMedia
         * @return {classes/projects/project/Media}
         */
        getMedia : function()
        {
            if ( !this.$Media ) {
                this.$Media = new Media( this );
            }

            return this.$Media;
        },

        /**
         * Return the Trash Object for the Project
         *
         * @method classes/projects/Project#getTrash
         * @return {classes/projects/project/Trash}
         */
        getTrash : function()
        {
            if ( !this.$Trash ) {
                this.$Trash = new Trash( this );
            }

            return this.$Trash;
        },

        /**
         * Return the Project name
         *
         * @method classes/projects/Project#getName
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
         * @method classes/projects/Project#getName
         * @return {String}
         */
        getLang : function()
        {
            return this.getAttribute( 'lang' );
        },

        /**
         * event : on Site deletion
         *
         * @method classes/projects/Project#$onChildDelete
         * @param {classes/projects/project/Site} Site
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
         * @param {classes/projects/project/Site} Site
         * @fires siteSave
         */
        $onSiteSave : function(Site)
        {
            this.fireEvent( 'siteSave', [ this, Site ] );
        },

        /**
         * event : on Site create
         *
         * @param {classes/projects/project/Site} Site
         * @param {Integer} newchildid - id of the new child
         * @fires siteCreate
         */
        $onSiteCreate : function(Site, newchildid)
        {
            this.fireEvent( 'siteCreate', [ this, Site, newchildid ] );
        },

        /**
         * event : on Site activasion
         *
         * @param {classes/projects/project/Site} Site
         * @fires Activate
         */
        $onSiteActivate : function(Site)
        {
            this.fireEvent( 'siteActivate', [ this, Site ] );
        },

        /**
         * event : on Site deactivasion
         *
         * @param {classes/projects/project/Site} Site
         * @fires Activate
         */
        $onSiteDeactivate : function(Site)
        {
            this.fireEvent( 'siteDeactivate', [ this, Site ] );
        }
    });
});
