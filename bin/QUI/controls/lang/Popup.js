/**
 * List all available languages
 *
 * @module controls/lang/Popup
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

], function (QUIConfirm, QUIButton, Locale, Grid) {
    "use strict";

    /**
     * @class controls/lang/Popup
     */
    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/lang/Popup',

        Binds: [
            'submit',
            '$onCreate',
            '$onSubmit'
        ],

        options: {
            title    : Locale.get('quiqqer/quiqqer', 'lang.popup.title'),
            maxHeight: 600,
            maxWidth : 500,
            autoclose: false
        },

        initialize: function (options) {
            this.$Active = null;

            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize
            });
        },

        /**
         * Submit the window, close the window if a language is selected
         * and trigger the onSubmit event
         */
        submit: function () {
            if (!this.$Grid) {
                return;
            }

            var selected = this.$Grid.getSelectedData();

            if (!selected.length) {
                return;
            }

            var result = [];

            for (var i = 0, len = selected.length; i < len; i++) {
                result.push(selected[i].lang);
            }

            this.fireEvent('submit', [result, this]);
            this.close();
        },

        /**
         * event : onCreate
         */
        $onCreate: function () {
            var Content = this.getContent(),
                langs   = this.getLanguages();

            this.getElm().set('data-qui', this.getType());

            var GridContainer = new Element('div', {
                styles: {
                    width: '100%'
                }
            }).inject(Content);

            this.$Grid = new Grid(GridContainer, {
                columnModel: [{
                    header   : Locale.get('quiqqer/quiqqer', 'language.code'),
                    dataIndex: 'lang',
                    dataType : 'string',
                    width    : 100
                }, {
                    header   : Locale.get('quiqqer/quiqqer', 'language'),
                    dataIndex: 'text',
                    dataType : 'string',
                    width    : 250
                }]
            });

            this.$Grid.addEvents({
                dblClick: this.submit
            });

            var data = [];

            for (var i = 0, len = langs.length; i < len; i++) {
                data.push({
                    text: Locale.get('quiqqer/quiqqer', 'language.' + langs[i]),
                    lang: langs[i]
                });
            }

            this.$Grid.setData({
                data: data
            });
        },

        /**
         * event : resize window
         */
        $onResize: function () {
            var Content = this.getContent();

            if (Content.getElement('.submit-body')) {
                Content.getElement('.submit-body').destroy();
            }

            if (this.$Grid) {
                this.$Grid.setHeight(Content.getSize().y - 40);
            }
        },

        /**
         * Return all available languages
         *
         * @return {Array}
         */
        getLanguages: function () {
            var languages = [
                'aa', 'ab', 'ace', 'ach', 'ada', 'ady', 'ae', 'aeb', 'af', 'afh', 'agq', 'ain',
                'ak', 'akk', 'akz', 'ale', 'aln', 'alt', 'am', 'an', 'ang', 'anp', 'ar', 'ar-001',
                'arc', 'arn', 'aro', 'arp', 'arq', 'arw', 'ary', 'arz', 'as', 'asa', 'ase', 'ast',
                'av', 'avk', 'awa', 'ay', 'az', 'az-alt-short', 'ba', 'bal', 'ban', 'bar', 'bas',
                'bax', 'bbc', 'bbj', 'be', 'bej', 'bem', 'bew', 'bez', 'bfd', 'bfq', 'bg', 'bgn',
                'bho', 'bi', 'bik', 'bin', 'bjn', 'bkm', 'bla', 'bm', 'bn', 'bo', 'bpy', 'bqi',
                'br', 'bra', 'brh', 'brx', 'bs', 'bss', 'bua', 'bug', 'bum', 'byn', 'byv', 'ca',
                'cad', 'car', 'cay', 'cch', 'ce', 'ceb', 'cgg', 'ch', 'chb', 'chg', 'chk', 'chm',
                'chn', 'cho', 'chp', 'chr', 'chy', 'ckb', 'co', 'cop', 'cps', 'cr', 'crh', 'cs',
                'csb', 'cu', 'cv', 'cy', 'da', 'dak', 'dar', 'dav', 'de', 'de-AT', 'de-CH', 'del',
                'den', 'dgr', 'din', 'dje', 'doi', 'dsb', 'dtp', 'dua', 'dum', 'dv', 'dyo', 'dyu',
                'dz', 'dzg', 'ebu', 'ee', 'efi', 'egl', 'egy', 'eka', 'el', 'elx', 'en', 'en-AU',
                'en-CA', 'en-GB', 'en-GB-alt-short', 'en-US', 'en-US-alt-short', 'enm', 'eo', 'es',
                'es-419', 'es-ES', 'es-MX', 'esu', 'et', 'eu', 'ewo', 'ext', 'fa', 'fa-AF', 'fan',
                'fat', 'ff', 'fi', 'fil', 'fit', 'fj', 'fo', 'fon', 'fr', 'fr-CA', 'fr-CH', 'frc',
                'frm', 'fro', 'frp', 'frr', 'frs', 'fur', 'fy', 'ga', 'gaa', 'gag', 'gan', 'gay',
                'gba', 'gbz', 'gd', 'gez', 'gil', 'gl', 'glk', 'gmh', 'gn', 'goh', 'gom', 'gon',
                'gor', 'got', 'grb', 'grc', 'gsw', 'gu', 'guc', 'gur', 'guz', 'gv', 'gwi', 'ha',
                'hai', 'hak', 'haw', 'he', 'hi', 'hif', 'hil', 'hit', 'hmn', 'ho', 'hr', 'hsb',
                'hsn', 'ht', 'hu', 'hup', 'hy', 'hz', 'ia', 'iba', 'ibb', 'id', 'ie', 'ig', 'ii',
                'ik', 'ilo', 'inh', 'io', 'is', 'it', 'iu', 'izh', 'ja', 'jam', 'jbo', 'jgo', 'jmc',
                'jpr', 'jrb', 'jut', 'jv', 'ka', 'kaa', 'kab', 'kac', 'kaj', 'kam', 'kaw', 'kbd',
                'kbl', 'kcg', 'kde', 'kea', 'ken', 'kfo', 'kg', 'kgp', 'kha', 'kho', 'khq', 'khw',
                'ki', 'kiu', 'kj', 'kk', 'kkj', 'kl', 'kln', 'km', 'kmb', 'kn', 'ko', 'koi', 'kok',
                'kos', 'kpe', 'kr', 'krc', 'kri', 'krj', 'krl', 'kru', 'ks', 'ksb', 'ksf', 'ksh',
                'ku', 'kum', 'kut', 'kv', 'kw', 'ky', 'ky-alt-variant', 'la', 'lad', 'lag', 'lah',
                'lam', 'lb', 'lez', 'lfn', 'lg', 'li', 'lij', 'liv', 'lkt', 'lmo', 'ln', 'lo', 'lol',
                'loz', 'lrc', 'lt', 'ltg', 'lu', 'lua', 'lui', 'lun', 'luo', 'lus', 'luy', 'lv',
                'lzh', 'lzz', 'mad', 'maf', 'mag', 'mai', 'mak', 'man', 'mas', 'mde', 'mdf', 'mdh',
                'mdr', 'men', 'mer', 'mfe', 'mg', 'mga', 'mgh', 'mgo', 'mh', 'mi', 'mic', 'min',
                'mis', 'mk', 'ml', 'mn', 'mnc', 'mni', 'moh', 'mos', 'mr', 'mrj', 'ms', 'mt', 'mua',
                'mul', 'mus', 'mwl', 'mwr', 'mwv', 'my', 'my-alt-variant', 'mye', 'myv', 'mzn',
                'na', 'nan', 'nap', 'naq', 'nb', 'nd', 'nds', 'nds-NL', 'ne', 'new', 'ng', 'nia',
                'niu', 'njo', 'nl', 'nl-BE', 'nmg', 'nn', 'nnh', 'no', 'nog', 'non', 'nov', 'nqo',
                'nr', 'nso', 'nus', 'nv', 'nwc', 'ny', 'nym', 'nyn', 'nyo', 'nzi', 'oc', 'oj', 'om',
                'or', 'os', 'osa', 'ota', 'pa', 'pag', 'pal', 'pam', 'pap', 'pau', 'pcd', 'pdc',
                'pdt', 'peo', 'pfl', 'phn', 'pi', 'pl', 'pms', 'pnt', 'pon', 'prg', 'pro', 'ps',
                'ps-alt-variant', 'pt', 'pt-BR', 'pt-PT', 'qu', 'quc', 'qug', 'raj', 'rap', 'rar',
                'rgn', 'rif', 'rm', 'rn', 'ro', 'ro-MD', 'rof', 'rom', 'root', 'rtm', 'ru', 'rue',
                'rug', 'rup', 'rw', 'rwk', 'sa', 'sad', 'sah', 'sam', 'saq', 'sas', 'sat', 'saz',
                'sba', 'sbp', 'sc', 'scn', 'sco', 'sd', 'sdc', 'sdh', 'se', 'see', 'seh', 'sei',
                'sel', 'ses', 'sg', 'sga', 'sgs', 'sh', 'shi', 'shn', 'shu', 'si', 'sid', 'sk',
                'sl', 'sli', 'sly', 'sm', 'sma', 'smj', 'smn', 'sms', 'sn', 'snk', 'so', 'sog',
                'sq', 'sr', 'srn', 'srr', 'ss', 'ssy', 'st', 'stq', 'su', 'suk', 'sus', 'sux',
                'sv', 'sw', 'sw-CD', 'swb', 'syc', 'syr', 'szl', 'ta', 'tcy', 'te', 'tem', 'teo',
                'ter', 'tet', 'tg', 'th', 'ti', 'tig', 'tiv', 'tk', 'tkl', 'tkr', 'tl', 'tlh',
                'tli', 'tly', 'tmh', 'tn', 'to', 'tog', 'tpi', 'tr', 'tru', 'trv', 'ts', 'tsd',
                'tsi', 'tt', 'ttt', 'tum', 'tvl', 'tw', 'twq', 'ty', 'tyv', 'tzm', 'udm', 'ug',
                'ug-alt-variant', 'uga', 'uk', 'umb', 'und', 'ur', 'uz', 'vai', 've', 'vec', 'vep',
                'vi', 'vls', 'vmf', 'vo', 'vot', 'vro', 'vun', 'wa', 'wae', 'wal', 'war', 'was',
                'wbp', 'wo', 'wuu', 'xal', 'xh', 'xmf', 'xog', 'yao', 'yap', 'yav', 'ybb', 'yi',
                'yo', 'yrl', 'yue', 'za', 'zap', 'zbl', 'zea', 'zen', 'zgh', 'zh', 'zh-Hans',
                'zh-Hant', 'zu', 'zun', 'zxx', 'zza'
            ];

            languages = languages.filter(function (language) {
                return language.length === 2;
            });

            languages = languages.sort();

            return languages;
        }
    });

});
