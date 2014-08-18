/**
 * List all available languages
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onSubmit [ {Array}, {this} ]
 */

define('controls/lang/Popup', [

    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'Locale',
    'controls/grid/Grid',

    'css!controls/lang/Popup.css'

], function(QUIConfirm, QUIButton, Locale, Grid)
{
    "use strict";

    /**
     * @class controls/lang/Popup
     */
    return new Class({

        Extends : QUIConfirm,
        Type    : 'controls/lang/Popup',

        Binds : [
            'submit',
            '$onCreate',
            '$onSubmit'
        ],

        options : {
            title     : Locale.get( 'quiqqer/system', 'lang.popup.title' ),
            maxHeight : 600,
            maxWidth  : 500,
            autoclose : false
        },

        initialize : function(options)
        {
            this.$Active = null;

            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });
        },

        /**
         * Submit the window, close the window if a language is selected
         * and trigger the onSubmit event
         */
        submit : function()
        {
            if ( !this.$Grid ) {
                return;
            }

            var selected = this.$Grid.getSelectedData();

            if ( !selected.length ) {
                return;
            }

            var result = [];

            for ( var i = 0, len = selected.length; i < len; i++ ) {
                result.push( selected[ i ].lang );
            }

            this.fireEvent( 'submit', [ result, this ] );
            this.close();
        },

        /**
         * event : onCreate
         */
        $onCreate : function()
        {
            var self    = this,
                Content = this.getContent(),
                langs   = this.getLanguages();

            var GridContainer = new Element('div', {
                styles : {
                    width : '100%'
                }
            }).inject( Content );

            this.$Grid = new Grid(GridContainer, {
                columnModel : [{
                    header    : '',
                    dataIndex : 'image',
                    dataType  : 'image',
                    width     : 50
                }, {
                    header    : '',
                    dataIndex : 'lang',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : Locale.get( 'quiqqer/system', 'language' ),
                    dataIndex : 'text',
                    dataType  : 'string',
                    width     : 250
                }]
            });

            this.$Grid.addEvents({
                dblClick : this.submit
            });

            var data = [];

            for ( var i = 0, len = langs.length; i < len; i++ )
            {
                data.push({
                    image : URL_BIN_DIR +'16x16/flags/'+ langs[ i ] +'.png',
                    text : Locale.get( 'quiqqer/system', 'lang.'+ langs[ i ] ),
                    lang : langs[ i ]
                });
            }

            this.$Grid.setData({
                data : data
            });
        },

        /**
         * event : resize window
         */
        $onResize : function()
        {
            var Content = this.getContent();

            if ( Content.getElement( '.submit-body' ) ) {
                Content.getElement( '.submit-body' ).destroy();
            }

            if ( this.$Grid ) {
                this.$Grid.setHeight( Content.getSize().y - 40 );
            }
        },

        /**
         * Return all available languages
         *
         * @return {Array}
         */
        getLanguages : function()
        {
            return [
                'ad', 'bi', 'cn', 'gr', 'jo', 'lv', 'mz', 'pr', 'sm',
                'tz', 'ae', 'bj', 'co', 'es', 'gs', 'jp', 'ly', 'na',
                'ps', 'sn', 'ua', 'af', 'bm', 'cr', 'et', 'gt', 'ke',
                'ma', 'nc', 'pt', 'so', 'ug', 'ag', 'bn', 'cs', 'gu',
                'kg', 'mc', 'ne', 'pw', 'sr', 'uk', 'ai', 'bo', 'cu',
                'gw', 'kh', 'md', 'nf', 'py', 'st', 'um', 'al', 'br',
                'cv', 'fi', 'gy', 'ki', 'me', 'ng', 'qa', 'sv', 'us',
                'am', 'bs', 'cx', 'fj', 'hk', 'km', 'mg', 'ni', 're',
                'sy', 'uy', 'an', 'bt', 'cy', 'fk', 'hm', 'kn', 'mh',
                'nl', 'ro', 'sz', 'uz', 'ao', 'bv', 'cz', 'fm', 'hn',
                'kp', 'mk', 'no', 'rs', 'tc', 'va', 'ar', 'bw', 'fo',
                'hr', 'kr', 'ml', 'np', 'ru', 'td', 'vc', 'as', 'by',
                'de', 'fr', 'ht', 'kw', 'mm', 'nr', 'rw', 'tf', 've',
                'at', 'bz', 'dj', 'ga', 'hu', 'ky', 'mn', 'nu', 'sa',
                'tg', 'vg', 'au', 'ca', 'dk', 'gb', 'id', 'kz', 'mo',
                'nz', 'sb', 'th', 'vi', 'aw', 'dm', 'gd', 'ie', 'la',
                'mp', 'om', 'tj', 'vn', 'ax', 'cc', 'do', 'ge', 'il',
                'mq', 'pa', 'sc', 'tk', 'vu', 'az', 'cd', 'dz', 'gf',
                'in', 'lb', 'mr', 'pe', 'sd', 'tl', 'ba', 'cf', 'ec',
                'gh', 'lc', 'ms', 'pf', 'se', 'tm', 'wf', 'bb', 'cg',
                'ee', 'gi', 'io', 'li', 'mt', 'pg', 'sg', 'tn', 'ws',
                'bd', 'ch', 'eg', 'gl', 'iq', 'lk', 'mu', 'ph', 'sh',
                'to', 'ye', 'be', 'ci', 'eh', 'gm', 'ir', 'lr', 'mv',
                'pk', 'si', 'tr', 'yt', 'bf', 'ck', 'gn', 'is', 'ls',
                'mw', 'pl', 'sj', 'tt', 'za', 'bg', 'cl', 'en', 'gp',
                'it', 'lt', 'mx', 'pm', 'sk', 'tv', 'zm', 'bh', 'cm',
                'er', 'gq', 'jm', 'lu', 'my', 'pn', 'sl', 'tw', 'zw'
            ].sort();
        }
    });

});