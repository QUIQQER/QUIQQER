/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

Object.append(Element.NativeEvents, {
    dragenter: 2, dragleave: 2, dragover: 2, dragend: 2, drop: 2
});

Object.extend({

    /**
     * Exists the namespace/ entries / values in the object?
     *
     * @example Object.existsValue('my.sub.vars');
     *
     * @param {Sring} namespace
     * @param {Object} obj
     * @returns {Boolean}
     */
    existsValue : function( namespace, obj )
    {
        var parts = namespace.split( '.' );

        for ( var i = 0, len = parts.length; i < len; ++i )
        {
            if ( typeof obj[ parts[ i ] ] === 'undefined' ) {
                return false;
            }

            obj = obj[ parts[ i ] ];
        }

        return true;
    },

    /**
     * Return the value of a namespace/ entry / value in the object
     *
     * @example Object.getValue('my.sub.vars');
     *
     * @param {Sring} namespace
     * @param {Object} obj
     * @returns {unknown_type}
     */
    getValue : function( namespace, obj )
    {
        var parts = namespace.split( '.' );

        for ( var i = 0, len = parts.length; i < len; ++i )
        {
            if ( typeof obj[ parts[ i ] ] === 'undefined' ) {
                return undefined;
            }

            obj = obj[ parts[ i ] ];
        }

        return obj;
    },

    /**
     * Create a namespace in or extend a object
     *
     * @param {String} namespace
     * @param {Object} obj
     *
     * @return {Object}
     */
    namespace : function extend( namespace, obj )
    {
        var pl, i;
        var parts  = namespace.split('.'),
            parent = obj;

        pl = parts.length;

        for ( i = 0; i < pl; i++ )
        {
            //create a property if it doesnt exist
            if ( typeof parent[ parts[ i ] ] === 'undefined' ) {
                parent[ parts[ i ] ] = {};
            }

            parent = parent[ parts[ i ] ];
        }

        return parent;
    }
});
