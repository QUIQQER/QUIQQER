
/**
 * A QUIQQER project
 *
 * @module classes/projects/Project
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require Ajax
 * @require classes/projects/Site
 * @require classes/projects/Media
 *
 * @events onSiteDelete [this, {Number}]
 * @events onSiteSave [this, {classes/projects/project/Site}]
 * @events onSiteCreate [this, {classes/projects/project/Site}]
 * @events onSiteActivate [this, {classes/projects/project/Site}]
 * @events onSiteDeactivate [this, {classes/projects/project/Site}]
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
     * @class classes/projects/Project
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QDOM,
        Type    : 'classes/projects/Project',

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
            lang : 'de',
            host : false
        },

        $ids   : {},
        $Media : false,
        $Trash : false,

        initialize : function(options)
        {
            this.parent( options );
        },

        /**
         * Decode the project
         * eq for ajax request
         *
         * @return {Object}
         */
        encode : function()
        {
            return JSON.encode({
                name     : this.getName(),
                lang     : this.getLang(),
                template : this.getAttribute('template')
            });
        },

        /**
         * Get a site from the project
         *
         * @method classes/projects/Project#get
         * @param {Number} id - ID of the site
         * @return {Object} classes/projects/project/Site
         */
        get : function(id)
        {
            if ( typeof this.$ids[ id ] !== 'undefined' ) {
                return this.$ids[ id ];
            }

            var Site = new ProjectSite( this, id );

            Site.addEvents({
                onDelete      : this.$onSiteDelete,
                onSave        : this.$onSiteSave,
                onActivate    : this.$onSiteActivate,
                onDeactivate  : this.$onSiteDeactivate,
                onCreateChild : this.$onSiteCreate,
                onSortSave    : this.$onSiteSortSave
            });

            this.$ids[ id ] = Site;
            this.$config    = null;

            return this.$ids[ id ];
        },

        /**
         * Return the configuration of the project
         *
         * @param {Function} [callback] - callback function
         * @param {String} [param] - param name
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
         * @param {Function} [callback]
         * @param {Object} [params] - one ore more params
         */
        setConfig : function(callback, params)
        {
            Ajax.get('ajax_project_set_config', function(result)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result );
                }
            }, {
                project : this.getName(),
                params  : JSON.encode( params || false )
            });
        },

        /**
         * Return the Media Object for the Project
         *
         * @method classes/projects/Project#getMedia
         * @return {Object} classes/projects/project/Media
         */
        getMedia : function()
        {
            if ( !this.$Media ) {
                this.$Media = new Media( this );
            }

            return this.$Media;
        },

        /*

        getTrash : function()
        {
            if ( !this.$Trash ) {
                this.$Trash = new Trash( this );
            }

            return this.$Trash;
        },*/

        /**
         * Return the Project name
         *
         * @method classes/projects/Project#getName
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
         * Return the project host
         *
         * @method classes/projects/Project#getHost
         * @param {Function} callback - callback function
         */
        getHost : function(callback)
        {
            if ( this.getAttribute( 'host' ) )
            {
                callback( this.getAttribute( 'host' ) );
                return;
            }

            var self = this;

            Ajax.get([
                'ajax_project_get_config',
                'ajax_vhosts_getList'
            ], function(config, vhosts)
            {
                var vhost       = config.vhost,
                    projectName = self.getName(),
                    projectLang = self.getLang();

                for ( var h in vhosts )
                {
                    if ( !vhosts.hasOwnProperty( h ) ) {
                        continue;
                    }

                    if ( h == 404 || h == 301 ) {
                        continue;
                    }

                    if ( vhosts[ h ].project != projectName ) {
                        continue;
                    }

                    if ( vhosts[ h ].lang != projectLang ) {
                        continue;
                    }

                    if ( 'httpshost' in vhosts[ h ] && vhosts[ h ].httpshost !== '' )
                    {
                        vhost = 'https://'+ vhosts[ h ];
                        break;
                    }

                    vhost = h;
                    break;
                }

                if ( !vhost.match( 'http://' ) && !vhost.match( 'https://' ) ) {
                    vhost = 'http://'+ vhost;
                }

                self.setAttribute( 'host', vhost );

                callback( self.getAttribute( 'host' ) );

            }, {
                project : this.getName(),
                params  : false
            });
        },

        /**
         * event : on Site deletion
         *
         * @method classes/projects/Project#$onChildDelete
         * @param {Object} Site - classes/projects/project/Site
         * @return {Object} this (classes/projects/Project)
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
         * @param {Object} Site - classes/projects/project/Site
         * @fires siteSave
         */
        $onSiteSave : function(Site)
        {
            this.fireEvent( 'siteSave', [ this, Site ] );
        },

        /**
         * event : on Site create
         *
         * @param {Object} Site - classes/projects/project/Site
         * @param {Number} newchildid - id of the new child
         * @fires siteCreate
         */
        $onSiteCreate : function(Site, newchildid)
        {
            this.fireEvent( 'siteCreate', [ this, Site, newchildid ] );
        },

        /**
         * event : on Site activasion
         *
         * @param {Object} Site - classes/projects/project/Site
         * @fires Activate
         */
        $onSiteActivate : function(Site)
        {
            this.fireEvent( 'siteActivate', [ this, Site ] );
        },

        /**
         * event : on Site deactivasion
         *
         * @param {Object} Site - classes/projects/project/Site
         * @fires Activate
         */
        $onSiteDeactivate : function(Site)
        {
            this.fireEvent( 'siteDeactivate', [ this, Site ] );
        },


        /**
         * event : on Site sort saving
         *
         * @param {Object} Site - classes/projects/project/Site
         * @fires sortSave
         */
        $onSiteSortSave : function(Site)
        {
            this.fireEvent( 'siteSortSave', [ this, Site ] );
        }
    });
});
