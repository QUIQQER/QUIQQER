/**
 * Global locale object
 *
 * @module Locale
 * @author www.pcsg.de (Henning Leutz)
 * @require qui/Locale
 */
define('Locale', ['classes/Locale'], function (QUILocale) {
    "use strict";

    if (window.location.search.match('lang=false')) {
        QUILocale.no_translation = true;
    } else if (window.location.toString().match('_lang_false')) {
        QUILocale.no_translation = true;
    }

    return new QUILocale();
});
