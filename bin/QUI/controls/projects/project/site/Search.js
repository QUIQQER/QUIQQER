/**
 * Site search panel
 **/

define('controls/projects/project/site/Search', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'controls/grid/Grid',
    'controls/projects/project/site/Panel',
    'Projects',
    'Ajax',

    'css!controls/projects/project/site/Search.css'

], function(QUI, QUIPanel, QUIButton, Grid, SitePanel, Projects, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/site/Search',

        Binds : [
            '$onCreate',
            '$onResize',
            '$onShow',
            '$openSite'
        ],

        options : {
            icon  : 'icon-search',
            title : 'Seitensuche'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Grid = null;
            this.$LabelContainer = null;
            this.$SearchInput    = null;
            this.$ProjectList    = null;

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize,
                onShow   : this.$onShow
            });
        },

        /**
        * event : on create
        */
        $onCreate : function()
        {
            var self    = this,
                Content = this.getContent();

            Content.addClass( 'control-site-search' );

            Content.set(
                'html',

                '<div class="control-site-search-label box">'+
                    '<label for="">Suche:</label>' +

                    '<select name="field">' +
                        '<option value="">Alle Attribute</option>' +
                        '<option value="id">ID</option>' +
                        '<option value="name">Name</option>' +
                        '<option value="title">Title</option>' +
                    '</select>' +
                    '<select name="project">' +
                        '<option value="">Alle Projekte</option>' +
                    '</select>' +
                    '<input type="text" name="search" placeholder="Seite suchen..." />' +
                '</div>' +
                '<label>Ergebnisse:</label>'
            );

            this.$LabelContainer = Content.getElement( '.control-site-search-label' );
            this.$SearchInput    = Content.getElement( '[name="search"]' );
            this.$ProjectList    = Content.getElement( '[name="project"]' );
            this.$FieldList      = Content.getElement( '[name="field"]' );

            new QUIButton({
                text : 'Suche starten',
                textimage : 'icon-search',
                events :
                {
                    onClick : function() {
                        self.search();
                    }
                }
            }).inject( this.$LabelContainer );

            this.$SearchInput.addEvents({
                keyup : function(event)
                {
                    if ( event && event.key == 'enter' ) {
                        self.search();
                    }
                }
            });

            // Grid
            var Container = new Element('div', {
                'class' : 'control-site-search-grid box'
            }).inject( Content );

            this.$Grid = new Grid( Container, {
                columnModel : [{
                    dataType  : 'button',
                    header    : '&nbsp;',
                    dataIndex : 'open',
                    width     : 50
                }, {
                    dataType  : 'integer',
                    header    : 'ID',
                    dataIndex : 'id',
                    width     : 100
                }, {
                    dataType  : 'string',
                    header    : 'name',
                    dataIndex : 'name',
                    width     : 150
                }, {
                    dataType  : 'string',
                    header    : 'Title',
                    dataIndex : 'title',
                    width     : 150
                }, {
                    dataType  : 'string',
                    header    : 'type',
                    dataIndex : 'type',
                    width     : 150
                }, {
                    dataType  : 'string',
                    header    : 'Projekt',
                    dataIndex : 'project',
                    width     : 150
                }],
                pagination : true,
                onrefresh : function() {
                    self.search();
                }
            });
        },

        /**
         * event : on inject
         */
        $onShow : function()
        {
            var self = this;

            this.Loader.show();

            Projects.getList(function(list)
            {
                self.$ProjectList.set(
                    'html',
                    '<option value="">Alle Projekte</option>'
                );

                for ( var project in list )
                {
                    new Element('option', {
                        html  : project,
                        value : project
                    }).inject( self.$ProjectList );
                }

                self.$SearchInput.focus();
                self.Loader.hide();
            });
        },

        /**
        * event : on resize
        */
        $onResize : function()
        {
            if ( !this.$Grid ) {
                return;
            }

            var Body = this.getContent();

            if ( !Body ) {
                return;
            }

            var size      = Body.getSize(),
                labelSite = this.$LabelContainer.getSize();

            this.$Grid.setHeight( size.y - 100 - labelSite.y );
            this.$Grid.setWidth( size.x - 40 );
        },

        /**
         *
         */
        search : function()
        {
            var self   = this,
                fields = [];

            this.Loader.show();

            if ( this.$FieldList.value.value !== '' ) {
                fields.push( this.$FieldList.value );
            }

            Ajax.get('ajax_site_search', function(result)
            {
                var data = result.data;

                for ( var i = 0, len = data.length; i < len; i++ )
                {
                    result.data[ i ].open = {
                        icon        : 'icon-file-alt',
                        siteid      : data[ i ].id,
                        siteproject : data[ i ].project,
                        title       : 'Seite öffnen',
                        alt         : 'Seite öffnen',
                        events : {
                            onClick : self.$openSite
                        }
                    };
                }

                self.$Grid.setData( result );
                self.Loader.hide();
            }, {
                search : this.$SearchInput.value,
                params : JSON.encode({
                    limit   : self.$Grid.options.perPage,
                    page    : self.$Grid.options.page,
                    project : this.$ProjectList.value,
                    fields  : fields.join(',')
                })
            });
        },

        /**
         * button click : Open a site
         *
         * @param {qui/controls/buttons/Button} Btn
         */
        $openSite : function(Btn)
        {
            var siteId      = Btn.getAttribute( 'siteid' ),
                projectData = Btn.getAttribute( 'siteproject' );

            projectData = projectData.replace('(', '').replace(')', '').split(' ');

            var Project = Projects.get( projectData[ 0 ], projectData[ 1 ] ),
                Site    = Project.get( siteId );

            new SitePanel( Site ).inject( this.getParent() );
        }
    });

});