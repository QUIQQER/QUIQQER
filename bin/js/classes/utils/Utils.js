/**
 * Some Utils
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module classes/utils/Utils
 * @package com.pcsg.qui.js
 * @namespace QUI.lib
 */

define('classes/utils/Utils', function()
{
    "use strict";

    QUI.namespace( 'classes.utils' );

    /**
     * @class QUI.classes.utils.Utils
     */
    QUI.classes.utils.Utils = new Class({

        /**
         * Combines two Object
         *
         * @method QUI.lib.Utils#combine
         *
         * @param {Object} first - First Object
         * @param {Object} second - Second Object
         * @return {Object}
         */
        combine : function(first, second)
        {
            first  = first || {};
            second = second || {};

            return Object.append(first, second);
        },

        /**
         * Filter the array or object for JSON
         *
         * @method QUI.lib.Utils#filterForJSON
         *
         * @param {Array|Object} arr - Array Oor Object to filter
         * @return {Array|Object}
         */
        filterForJSON : function(arr)
        {
            var result = {};

            for (var i in arr)
            {
                if (arr.hasOwnProperty( i ) === false) {
                    continue;
                }

                switch (typeOf(arr[i]))
                {
                    case 'string':
                    case 'array':
                    case 'object':
                        result[i] = arr[i];
                    break;
                }
            }

            return result;
        },

        /**
         * UTF8 encode
         *
         * @method QUI.lib.Utils#encodeUTF8
         *
         * @param {String} rohtext
         * @return {String}
         */
        encodeUTF8 : function(rohtext)
        {
            // dient der Normalisierung des Zeilenumbruchs
            rohtext     = rohtext.replace(/\r\n/g,"\n");
            var utftext = "";

            for (var n=0; n<rohtext.length; n++)
            {
                // ermitteln des Unicodes des  aktuellen Zeichens
                var c=rohtext.charCodeAt(n);
                // alle Zeichen von 0-127 => 1byte
                if (c<128)
                {
                    utftext += String.fromCharCode(c);
                } else if ((c>127) && (c<2048))
                {
                    // alle Zeichen von 127 bis 2047 => 2byte
                    utftext += String.fromCharCode((c>>6)|192);
                    utftext += String.fromCharCode((c&63)|128);
                } else
                {
                    // alle Zeichen von 2048 bis 66536 => 3byte
                    utftext += String.fromCharCode((c>>12)|224);
                    utftext += String.fromCharCode(((c>>6)&63)|128);
                    utftext += String.fromCharCode((c&63)|128);
                }
            }

            return utftext;
        },

        /**
         * UTF8 Decode
         *
         * @method QUI.lib.Utils#decodeUTF8
         *
         * @param {String} utftext - UTF8 String
         * @return {String}
         */
        decodeUTF8 : function(utftext)
        {
            var i, c, c1, c2, c3;
            var plaintext = "";

            i = c = c1 = c2 = 0;

            while (i < utftext.length)
            {
                c = utftext.charCodeAt(i);

                if (c < 128)
                {
                    plaintext += String.fromCharCode(c);
                    i++;
                } else if ((c > 191) && (c < 224))
                {
                    c2 = utftext.charCodeAt(i+1);
                    plaintext += String.fromCharCode(((c&31)<<6) | (c2&63));
                    i+=2;
                } else
                {
                    c2 = utftext.charCodeAt(i+1);
                    c3 = utftext.charCodeAt(i+2);

                    plaintext += String.fromCharCode(((c&15)<<12) | ((c2&63)<<6) | (c3&63));
                    i+=3;
                }
            }

            return plaintext;
        },

        /**
         * Set an object to an formular DOMNode
         * goes through all object attributes and set it to the appropriate form elements
         *
         * @method QUI.lib.Utils#setDataToForm
         *
         * @param {Object} data
         * @param {DOMNode} form - Formular
         */
        setDataToForm : function(data, form)
        {
            if ( typeof form === 'undefined' || form.nodeName !== 'FORM' ) {
                return;
            }

            var i, k, len, Elm;

            data = data || {};

            for ( k in data )
            {
                if ( !form.elements[ k ] ) {
                    continue;
                }

                Elm = form.elements[ k ];

                if ( Elm.type === 'checkbox' )
                {
                    if ( data[k] === false || data[k] === true )
                    {
                        Elm.checked = data[k];
                        continue;
                    }

                    Elm.checked = ( (data[k]).toInt() ? true : false );
                    continue;
                }

                if ( Elm.type === 'text' ||
                     Elm.type === 'textarea' ||
                     Elm.type === 'select' ||
                     Elm.type === 'hidden' )
                {
                    Elm.value = data[k];
                    continue;
                }

                if ( Elm.length )
                {
                    for ( i = 0, len = Elm.length; i < len; i++ )
                    {
                        if ( Elm[i].type !== 'radio' ) {
                            continue;
                        }

                        if ( Elm[i].value == data[k] ) {
                            Elm[i].checked = true;
                        }
                    }

                    continue;
                }
            }
        },

        /**
         * Get all Data from a Formular
         *
         * @method QUI.lib.Utils#getFormData
         *
         * @param {DOMNode} form - DOMNode Formular
         * @return {Object}
         */
        getFormData : function(form)
        {
            if ( typeof form === 'undefined' || !form ) {
                return {};
            }

            var i, len, Elm;
            var result   = {},
                elements = form.elements;

            for ( i = 0, len = elements.length; i < len; i++ )
            {
                Elm = elements[i];

                if ( Elm.type === 'text' ||
                     Elm.type === 'textarea' ||
                     Elm.type === 'select' ||
                     Elm.type === 'select-one' ||
                     Elm.type === 'select-multiple' ||
                     Elm.type === 'hidden' )
                {
                    result[ Elm.name ] = Elm.value;
                    continue;
                }

                if ( Elm.type === 'checkbox' )
                {
                    result[ Elm.name ] = Elm.checked ? true : false;
                    continue;
                }

                if ( Elm.type === 'radio' && !Elm.length )
                {
                    if ( Elm.checked ) {
                        result[ Elm.name ] = Elm.value;
                    }

                    continue;
                }

                if ( Elm.length )
                {
                    for ( i = 0, len = Elm.length; i < len; i++ )
                    {
                        if ( Elm[i].type !== 'radio' ) {
                            continue;
                        }

                        result[ Elm[i].name ] = '';

                        if ( Elm[i].checked )
                        {
                            result[ Elm[i].name ] = Elm[i].value;

                            //console.log( result[ Elm[i].name ] );

                            break;
                        }
                    }
                }
            }

            return result;
        },

        /**
         * get params from an url
         *
         * @method QUI.lib.Utils#getUrlParams
         *
         * @param {String} str - index.php?param1=12&param2=test
         * @return {Object}
         */
        getUrlParams : function(str)
        {
            str = str.split('?');

            if ( typeof str[1] === 'undefined' ){
                return {};
            }

            str = str[1].split('&');

            var i, len, sp;
            var r = {};

            for ( i = 0, len = str.length; i < len; i++ )
            {
                sp = str[i].split('=');

                r[ sp[0] ] = sp[1];
            }

            return r;
        },

        /**
         * Resize Variables in dependence on each other
         *
         * @method QUI.lib.Utils#resizeVar
         *
         * @param {Integer} var1 - First variable
         * @param {Integer} var1 - Second variable
         * @param {Integer} max  - Max value of each variable
         *
         * @return {Object} Object {
         *     var1 : value,
         *  var2 : value
         * }
         */
        resizeVar : function(var1, var2, max)
        {
            var resize_by_percent;

            if ( var1 > max )
            {
                resize_by_percent = (max * 100 )/ var1;
                var2 = Math.round((var2 * resize_by_percent)/100);
                var1 = max;
            }

            if ( var2 > max )
            {
                resize_by_percent = (max * 100 )/ var2;
                var1 = Math.round((var1 * resize_by_percent)/100);
                var2 = max;
            }

            return {
                var1 : var1,
                var2 : var2
            };
        },

        /**
         * Format an amount value
         *
         * @method QUI.lib.Utils#displayAmount
         *
         * @param {Integer} number - the number which to be formatted
         * @param {Integer} decimals - how many decimals
         * @param {String} dec_point - decimal sign
         * @param {String} thousands_sep - thousend seperator
         *
         * @return {String}
         */
        displayAmount : function(number, decimals, dec_point, thousands_sep)
        {
            if (number === false) {
                number = 0;
            }

            var exponent  = "";
            var numberstr = number.toString();
            var eindex    = numberstr.indexOf("e");

            if (eindex > -1)
            {
                exponent = numberstr.substring (eindex);
                number   = parseFloat(numberstr.substring (0, eindex));
            }

            if (decimals !== null)
            {
                var temp = Math.pow(10, decimals);
                number   = Math.round(number * temp) / temp;
            }

            var i, z;
            var sign    = number < 0 ? "-" : "";
            var integer = (number > 0 ?

            Math.floor(number) : Math.abs(Math.ceil (number))).toString();

            var fractional = number.toString ().substring(integer.length + sign.length);
            dec_point      = dec_point !== null ? dec_point : ".";
            fractional     = decimals !== null && decimals > 0 || fractional.length > 1 ? (dec_point + fractional.substring (1)) : "";

            if (decimals !== null && decimals > 0)
            {
                for (i = fractional.length - 1, z = decimals; i < z; ++i) {
                    fractional += "0";
                }
            }

            thousands_sep = (thousands_sep != dec_point || fractional.length === 0) ? thousands_sep : null;

            if (thousands_sep !== null && thousands_sep !== "")
            {
                for (i = integer.length - 3; i > 0; i -= 3) {
                    integer = integer.substring(0 , i) + thousands_sep + integer.substring(i);
                }
            }

            return sign + integer + fractional + exponent;
        },

        /**
         * Parse an amount to a real float value
         *
         * @method QUI.lib.Utils#parseAmountToFloat
         *
         * @param {String} str - Value, String
         * @return {Float}
         */
        parseAmountToFloat : function(str)
        {
            return parseFloat(
                str.toString().replace(',', '.')
            );
        },

        /**
         * Calc a VAT
         *
         * @method QUI.lib.Utils#calcMwst
         *
         * @param {Float|Integer|Bool} brutto
         * @param {Float|Integer|Bool} netto
         * @param {Integer} mwst
         *
         * @return {Object} Object {
         *        brutto : brutto,
         *         netto : netto
         * }
         *
         * @example
         *
         * QUI.Utils.calcMwst(0.20, false, 19);
         * QUI.Utils.calcMwst(false, 100, 19);
         */
        calcMwst : function(brutto, netto, mwst)
        {
            mwst = (parseInt(mwst, 10) / 100) + 1;

            if (brutto === false)
            {
                brutto = netto * mwst;
            } else if (netto === false)
            {
                netto = brutto / mwst;
            }

            return {
                brutto : brutto,
                netto  : netto
            };
        },

        /**
         * Percent calculation
         * Return the percentage integer value
         *
         * @method QUI.lib.Utils#percent
         * @param Integer|Float $amount
         * @param Integer|Float $total
         *
         * @return {Integer}
         */
        percent : function(amount, total)
        {
            if (amount === 0 || total === 0) {
                return 0;
            }

            return ((amount * 100) / total).round();
        },

        /**
         * Formats the size of a file into a readable output format and append the ending
         *
         * @param {Integer} size - number in bytes
         * @param {Integer} round
         *
         * @return {String}
         */
        formatSize : function(size, round)
        {
            var i, len;
            var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];

            round = round || 0;

            for (i = 0, len = sizes.length; i < len-1 && size >= 1024; i++) {
                size /= 1024;
            }

            return size.round( round ) +' '+ sizes[ i ];
        }
    });

    return QUI.classes.utils.Utils;
});
