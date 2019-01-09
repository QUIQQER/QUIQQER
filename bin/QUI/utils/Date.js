/**
 * Date utils
 *
 * @module utils/Date
 *
 * @author www.pcsg.de (Jan Wennrich)
 * @author Smart people from Stack Overflow
 *
 * @require Locale
 */
define('utils/Date', [
    'Locale'
], function (QUILocale) {
    "use strict";

    return {
        /**
         * Returns the time since a given date as a readable string for the current language.
         * Examples:
         * - Date-object given dated to 3 days ago returns "3 days ago"
         * - Date-object given dated to 51 seconds ago returns "51 seconds ago"
         * - Currently german language; Date-object given dated to 51 seconds ago returns "vor 51 Sekunden"
         *
         * @param {TimeSinceObject} GivenDate
         *
         * @return {string}
         */
        getTimeSinceAsString: function (GivenDate) {
            var timeSince = this.getTimeSince(GivenDate);

            return QUILocale.get(
                'quiqqer/quiqqer',
                'time.ago',
                {
                    amount: timeSince.amount,
                    // get the time unit in dative form (necessary for languages like german)
                    unit  : QUILocale.get('quiqqer/quiqqer', timeSince.unit + '.dative')
                }
            );
        },


        /**
         * Returns the time since a given date in a appropriate size/format.
         * The result is an object containing the time since the date and the calculated unit.
         * This for example useful to format texts that tell the user how long ago something happened (e.g. for posts).
         *
         * If you don't want any fancy formatted text, then getTimeSinceAsString() can be used.
         *
         * Example:
         * - A date 3 months ago would return {amount: 3, unit: 'months'}
         * - A date 2 minutes ago would return {amount: 2, unit: 'minutes'}
         *
         * @author Inspired by a Stack Overflow answer from Sky Sander (https://stackoverflow.com/a/3177838)
         *
         * @param {Date} GivenDate
         *
         * @typedef {Object} TimeSinceObject
         * @property {number} amount - The amount of time since a date
         * @property {string} unit - The unit of time since a date. Can be either years, months, days, hours, minutes, or seconds
         *
         * @return {TimeSinceObject}
         */
        getTimeSince: function (GivenDate) {
            var seconds = Math.floor((new Date() - GivenDate) / 1000);

            var interval = Math.floor(seconds / 31536000);

            if (interval > 1) {
                return {amount: interval, unit: "years"};
            }

            interval = Math.floor(seconds / 2592000);
            if (interval > 1) {
                return {amount: interval, unit: "months"};
            }

            interval = Math.floor(seconds / 86400);
            if (interval > 1) {
                return {amount: interval, unit: "days"};
            }

            interval = Math.floor(seconds / 3600);
            if (interval > 1) {
                return {amount: interval, unit: "hours"};
            }

            interval = Math.floor(seconds / 60);
            if (interval > 1) {
                return {amount: interval, unit: "minutes"};
            }

            return {amount: interval, unit: "seconds"};
        }
    };
});
