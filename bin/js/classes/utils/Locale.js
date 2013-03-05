/**
 * Locale translation class
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module classes/utils/Locale
 * @package com.pcsg.qui.js.classes.utils
 * @namespace QUI.classes.utils
 *
 * @event onError [ {String}, {this} ]
 */

define('classes/utils/Locale', [

    'classes/DOM'

], function(QDOM)
{
    QUI.namespace( 'classes.utils' );

    /**
     * @class QUI.classes.utils.Locale
     */
    QUI.classes.utils.Locale = new Class({

        Implements : [ QDOM ],
        Type       : 'QUI.classes.utils.Locale',

        /**
         * Current lang, use getCurrent() to get the lang
         */
        current : false,

        /**
         * Available langs
         */
        langs : {},

        /**
         * No translation flag, makes no translation
         * if true, get() returns the translation group
         * usefull to show all translation groups
         */
        no_translation : false,

        /**
         * init
         *
         * @param options - dom options
         */
        initialize : function(options)
        {
            this.init( options );
        },

        /**
         * Set the current lang
         *
         * @param {String} lang
         */
        setCurrent : function(lang)
        {
            this.current = lang;
        },

        /**
         * Return the current Lang
         * @return {String}
         */
        getCurrent : function()
        {
            return this.current;
        },

        /**
         * Set a translation for a translation group
         *
         * @param {String} lang - language, which is translated
         * @param {String} group - group, which is translated
         * @param {String|Object} values - variable(s) which is translated
         * @param {String} value - the translation value
         *
         * @example Locale.set("en", "my/group", "my.translation.variable", "Some text is translated")
         * @example Locale.set("de", "my/group", "my.translation.variable", "Ein Text der übersetzt wird")
         */
        set : function(lang, group, values, value)
        {
            if ( !this.langs[ lang ] ) {
                this.langs[ lang ] = {};
            }

            if ( !this.langs[ lang ][ group ] ) {
                this.langs[ lang ][ group ] = {};
            }

            if ( typeof value !== 'undefined' )
            {
                this.langs[ lang ][ set ][ values ] = value;
                return this;
            }

            var _key = this.langs[ lang ][ group ];

            for ( var k in values ) {
                _key[ k ] = values[ k ];
            }

            this.langs[ lang ][ group ] = _key;
        },

        /**
         * Retrurn the translation of a translation group
         *
         * @param {String} group - Group of the translation
         * @param {String} value - Translation value / name
         * @param {Object} repl  - Assoziative Array of replacements
         * @return {String}
         *
         * @example Locale.get('my/group', 'my.translation.variable')
         * @example Locale.get('my/group', 'my.translation.variable', {
         *      placeholder : 'my replace'
         * })
         */
        get : function(group, value, repl)
        {
            if ( typeof repl === 'undefined' ) {
                return this.$get( group, value );
            }

            var result = this.$get( group, value );

            for ( group in repl ) {
                result = result.replace( '['+ group +']', repl[ group ] );
            }

            return result;
        },

        /**
         * Helper for get translation
         *
         * @param {String} key   - Group / Key of the translation
         * @param {String} value - Translation value / name
         * @param {Object} repl  - Assoziative Array of replacements
         * @return {String}
         */
        $get : function(key, value)
        {
            if ( this.no_translation ) {
                return '['+ key +'] '+ value;
            }

            if ( !this.langs[ this.current ] ||
                 !this.langs[ this.current ][ key ] )
            {
                this.fireEvent('error', [
                    'No translation found for ['+ key +'] '+ value,
                    this
                ]);

                return '['+ key +'] '+ value;
            }

            if ( typeof value == 'undefined' ) {
                return this.langs[ this.current ][ key ];
            }

            if ( this.langs[ this.current ][ key ][ value ] ) {
                return this.langs[ this.current ][ key ][ value ];
            }

            return '['+ key +'] '+ value;
        }
    });

    return QUI.classes.utils.Locale;
});