/**
 * Global locale object
 *
 * @module Locale
 * @author www.pcsg.de (Henning Leutz)
 * @require qui/Locale
 */
define(['qui/Locale'], function (Locale) {
    "use strict";

    if (window.location.search.match('lang=false')) {
        Locale.no_translation = true;
    } else if (window.location.toString().match('_lang_false')) {
        Locale.no_translation = true;
    }

    return Locale;
});
