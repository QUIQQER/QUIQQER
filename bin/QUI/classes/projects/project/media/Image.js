
/**
 * A media image
 *
 * @module classes/projects/project/media/Image
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require classes/projects/project/media/Item
 */

define('classes/projects/project/media/Image', [

    'classes/projects/project/media/Item',
    'qui/utils/Object',
    'Ajax'

], function(MediaItem, Utils, Ajax)
{
    "use strict";

    /**
     * @class classes/projects/project/media/Image
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : MediaItem,
        Type    : 'classes/projects/project/media/Image',

        initialize : function(params, Media)
        {
            this.parent( params, Media );

            this.$effects = null;
        },

        /**
         * Save the File attributes to the database
         *
         * @method classes/projects/project/media/Item#save
         * @fires onSave [this]
         * @param {Function} [oncomplete] - (optional) callback Function
         * @param {Object} [params]      - (optional), parameters that are linked to the request object
         */
        save : function(oncomplete, params)
        {
            var self = this;
            var attributes = this.getAttributes();

            attributes.image_effects = this.getEffects();

            params = Utils.combine(params, {
                project    : this.getMedia().getProject().getName(),
                fileid     : this.getId(),
                attributes : JSON.encode( attributes )
            });

            console.log(attributes);

            Ajax.post('ajax_media_file_save', function(result, Request)
            {
                self.setAttributes( result );
                self.fireEvent( 'save', [ self ] );

                if ( typeOf( oncomplete ) === 'function' ) {
                    oncomplete( result, Request );
                }
            }, params);
        },

        /**
         * Return the own image effects for the immage
         * @returns {Object}
         */
        getEffects : function()
        {
            if ( this.$effects ) {
                return this.$effects;
            }

            if ( !this.getAttribute('image_effects') )
            {
                this.$effects = {};
                return this.$effects;
            }

            this.$effects = JSON.decode( this.getAttribute('image_effects') );

            if ( !this.$effects ) {
                this.$effects = {};
            }

            return this.$effects;
        },

        /**
         * Get a effect value
         *
         * @param {String} effect
         */
        getEffect : function(effect)
        {
            var effects = this.getEffects();

            return effect in effects ? effects[ effect ] : false;
        },

        /**
         * Set a effect
         *
         * @param {String} effect
         * @param {String|Number|null} value - if value is null, effect would be deleted
         */
        setEffect: function(effect, value)
        {
            this.getEffects();

            if ( value === null ) {
                delete this.$effects[ effect ];
                return;
            }

            if (typeOf(this.$effects) !== 'object') {
                this.$effects = {};
            }

            this.$effects[ effect ] = value;
        }
    });
});
