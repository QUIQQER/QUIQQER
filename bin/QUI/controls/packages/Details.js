
/**
 * Package detail Control
 *
 * @module controls/packages/Details
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onOpenSheet [ {self}, {DOMNode} Sheet ]
 */

define([

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',
    'classes/packages/Manager',
    'Ajax',
    'Locale',

    'css!controls/packages/Details.css'

], function (QUI, QUIControl, QUILoader, QUIButton, PackageManager, Ajax, Locale) {
    "use strict";


    return new Class({

        Extends : QUIControl,
        Type    : 'controls/packages/Details',

        Binds : [
            'refresh'
        ],

        options : {
            'package' : false
        },

        initialize : function (options) {
            this.parent(options);

            this.$data    = null;
            this.$Manager = new PackageManager();
            this.Loader   = new QUILoader();

            this.addEvents({
                onInject : this.refresh
            });
        },

        /**
         * create the DOMNode element
         *
         * @return {DOMNode}
         */
        create : function () {
            this.parent();

            this.$Elm.set(
                'html',

                '<table class="data-table qui-packages-package-window-details">' +
                    '<thead>' +
                        '<tr>' +
                            '<th class="package-name" colspan="2"></th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>' +
                        '<tr class="odd">' +
                            '<td>Beschreibung</td>' +
                            '<td class="package-desc"></td>' +
                        '</tr>' +
                        '<tr class="even">' +
                            '<td>Aktuelle Version</td>' +
                            '<td class="package-version"></td>' +
                        '</tr>' +
                        '<tr class="odd">' +
                            '<td>Verfügbare Versionen</td>' +
                            '<td>' +
                                '<select name="versions"></select>' +
                            '</td>' +
                        '</tr>' +
                        '<tr class="even">' +
                            '<td>Benötigte Pakete</td>' +
                            '<td class="package-require"></td>' +
                        '</tr>' +
                    '</tbody>' +
                '</table>'
            );

            this.$Elm.addClass('qui-box');

            this.Loader.inject(this.$Elm);

            this.$Versions = this.$Elm.getElement('[name="versions"]'),
            this.$Name     = this.$Elm.getElement('.package-name'),
            this.$Desc     = this.$Elm.getElement('.package-desc'),
            this.$Require  = this.$Elm.getElement('.package-require'),
            this.$Version  = this.$Elm.getElement('.package-version');


            return this.$Elm;
        },

        /**
         * refresh the data
         */
        refresh : function () {
            var self = this;

            this.Loader.show();

            this.$Manager.getPackage(this.getAttribute('package'), function (result) {
                self.$data = result;

                self.$Name.set('html', result.name);
                self.$Desc.set('html', result.description);
                self.$Version.set('html', result.version);
                self.$Require.set('html', '');

                var versions = result.versions || [];

                for (var i = 0, len = versions.length; i < len; i++) {
                    versions[ i ] = versions[ i ].replace('* ', '');

                    new Element('option', {
                        value : versions[ i ],
                        html  : versions[ i ]
                    }).inject(self.$Versions);
                }

                var RequireList = new Element('ul').inject(self.$Require);

                Object.each(result.require, function (version, key) {
                    new Element('li', {
                        html : key + ' - ' + version
                    }).inject(RequireList);
                });

                // version change
                self.$Versions.addEvent('change', function () {
                    self.$onVersionChange();
                });

                self.Loader.hide();
            });
        },

        /**
         * get the package data
         */
        getData : function (callback) {
            this.$Manager.getPackage(this.getAttribute('package'), function (result) {
                this.$data = result;

                callback(result);

            }.bind(this));
        },

        /**
         * Set the version of the package
         *
         * @param {String} version - wanted version
         */
        setVersion : function (version) {
            var self = this;

            this.Loader.show();

            this.$Manager.setVersion(this.getAttribute('package'), version, function () {
                self.Loader.hide();
            });
        },

        /**
         * event : version change
         */
        $onVersionChange : function () {
            var self = this;

            this.Loader.show();

            this.openSheet(function (Content, Sheet) {
                Content.set(
                    'html',

                    '<p>Möchten Sie wirklich auf die die Version ' + self.$Versions.value + ' wechseln?</p>' +
                    '<p>Es wird ein Update des Packates durchgeführt</p>'
                );

                Content.setStyles({
                    textAlign : 'center'
                });

                new QUIButton({
                    text   : 'Abbrechen',
                    events :
                    {
                        onClick : function () {
                            self.$Versions.value = self.$data.version;

                            Sheet.fireEvent('close');
                        }
                    },
                    styles : {
                        'float' : 'none',
                        margin  : '10px 10px 0 0'
                    }
                }).inject(Content);

                new QUIButton({
                    text   : 'Übernehmen',
                    events :
                    {
                        onClick : function () {
                            Sheet.fireEvent('close');

                            self.setVersion(self.$Versions.value);
                        }
                    },
                    styles : {
                        'float' : 'none',
                        margin  : '10px 0 0 0'
                    }
                }).inject(Content);


                self.fireEvent('openSheet', [self, Sheet]);
                self.Loader.hide();
            });
        }
    });

});
