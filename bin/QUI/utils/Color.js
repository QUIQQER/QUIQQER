/**
 * Colors
 *
 * @module utils/color
 *
 * @author www.pcsg.de (Jan Wennrich)
 * @author A lot of smart people from Stack Overflow
 */
define('utils/Color', [], function () {
    "use strict";

    return {
        /**
         * A custom web color pallet.
         * Taken from clrs.cc (available under MIT license).
         *
         * @link https://clrs.cc/
         * @link https://github.com/mrmrs/colors
         *
         * @licence MIT
         */
        colorPalette: {
            aqua   : "#7FDBFF",
            black  : "#111111",
            blue   : "#0074D9",
            fuchsia: "#F012BE",
            gray   : "#AAAAAA",
            green  : "#2ECC40",
            lime   : "#01FF70",
            maroon : "#85144b",
            navy   : "#001f3f",
            olive  : "#3D9970",
            purple : "#B10DC9",
            red    : "#FF4136",
            silver : "#DDDDDD",
            teal   : "#39CCCC",
            white  : "#FFFFFF",
            yellow : "#FFDC00"
        },


        /**
         * Generates a random color in hexadecimal format with a preceding "#"
         *
         * @see https://stackoverflow.com/questions/1484506/random-color-generator#comment6801353_5365036
         *
         * @returns {String}
         */
        getRandomHexColor: function () {
            return '#' + (Math.random() * 0xffffff).toString(16).slice(-6);
        },


        /**
         * Returns an array with the given amount of random colors.
         *
         * @param {number} amount - How many colors to generate
         * @return {Array}
         */
        getRandomHexColors: function (amount) {
            var result = [];
            for (var i = 0; i < amount; i++) {
                result.push(this.getRandomHexColor());
            }
            return result;
        },


        /**
         * Returns a random color from the custom color pallet.
         *
         * @return {String}
         */
        getRandomHexColorFromPallet: function () {
            var colors = Object.values(this.colorPalette);
            return colors[Math.floor(Math.random() * colors.length)];
        },


        /**
         * Returns an array with random colors from the color pallet.
         * The amount of colors to return can be passed as an argument.
         * If more colors are requested than there are in the pallet, some colors may appear multiple times.
         *
         * Random picking taken from Bergi from {@link https://stackoverflow.com/a/19270021|Stack Overflow}
         *
         * @param {number} requestedColorsAmount
         *
         * @return {Array<String>}
         */
        getRandomHexColorsFromPallet: function (requestedColorsAmount) {
            var colors         = Object.values(this.colorPalette),
                amountOfColors = colors.length,
                result         = [];

            if (requestedColorsAmount > amountOfColors) {
                result = result.concat(
                    this.getRandomHexColorsFromPallet(requestedColorsAmount - amountOfColors)
                );
                requestedColorsAmount = amountOfColors;
            }

            while (requestedColorsAmount--) {
                // Generate a random number to pick an element from the color array
                var randomNumber = Math.floor(Math.random() * colors.length);

                // Remove the color so we don't pick it twice
                var pickedColor = colors.splice(randomNumber, 1)[0];

                result.push(pickedColor);
            }

            return result;
        },


        /**
         * Returns the hex code for a given color name from the custom color pallet
         *
         * @param {String} name
         *
         * @return {String}
         */
        getHexColorFromPallet: function (name) {
            return this.colorPalette[name];
        },


        /**
         * Converts a hex color (#0033FF, #03F, 03F, 0033FF) to an Object with RGB values.
         *
         * @note Taken from Tim Down from {@link https://stackoverflow.com/a/5624139|Stack Overflow}
         *
         * @param hex - Hex color format: '#0033FF', '#03F', '03F', or '0033FF'
         *
         * @return {Object} - RGB colors in object properties r, g, and b
         */
        getRgbColorFromHex: function (hex) {
            // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
            var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
            hex = hex.replace(shorthandRegex, function (m, r, g, b) {
                return r + r + g + g + b + b;
            });

            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },


        /**
         * Converts an object with properties r, g, b to a hex string (e.g. "#0033FF")
         *
         * @note Taken from Tim Down from {@link https://stackoverflow.com/a/13070198|StackOverflow}
         *
         * @param {object} rgbObject - object with properties r, g, b
         *
         * @return {string} hex string (e.g. "#0033FF")
         */

        getHexFromRgbObject: function (rgbObject) {
            var rgbArray = [rgbObject.r, rgbObject.g, rgbObject.b];

            var hexString = "#";
            rgbArray.forEach(function (value) {

                //Convert to a base16 string
                var hexValue = parseInt(value).toString(16);

                //Add zero if we get only one character
                if (hexValue.length === 1) {
                    hexValue = "0" + hexValue;
                }

                hexString += hexValue;
            });
            return hexString;
        },


        /**
         * Calculates whether the text color for the given background color should be black or white.
         *
         * @note Modified version of Marcus Mangelsdorf reply on {@link https://stackoverflow.com/a/36888120|StackOverflow}
         *
         * @param {Object} bgColor - Object with properties r, g and b
         *
         * @return {Object} - RGB colors in object properties r, g, and b
         */
        getTextColorForRgbObject: function (bgColor) {
            //  Counting the perceptive luminance
            var luminance = (((0.299 * bgColor.r) + ((0.587 * bgColor.g) + (0.114 * bgColor.b))) / 255);

            // Return black for bright colors, white for dark colors
            var r, g, b;
            r = g = b = 0;

            if (luminance < 0.5) {
                r = g = b = 255;
            }

            return {r: r, g: g, b: b};
        }
    };
});