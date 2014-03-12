/**
 *
 */

define('controls/projects/project/site/SiteChildrenSort', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'controls/grid/Grid',
    'Ajax',

    'css!controls/projects/project/site/SiteChildrenSort.css'

], function(QUI, QUIControl, QUILoader, Grid, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/site/SiteChildrenSort',

        Binds : [
            '$onInject',
            '$onResize'
        ],

        options : {

        },

        initialize : function(Site, options)
        {
            this.parent( options );

            this.$Site      = Site;
            this.$Container = null;
            this.$Select    = null;
            this.$GridTable = null;

            this.Loader = new QUILoader();

            this.addEvents({
                onInject : this.$onInject,
                onResize : this.$onResize
            });
        },

        /**
         * Create the DOMNode
         *
         * @return {DOMNode}
         */
        create : function()
        {
            var self = this;

            this.$Elm = new Element('div', {
                'class' : 'qui-project-children-sort box',
                html  : '<label for="order-type">Sortierung</label>' +
                        '<select name="order-type">' +
                            '<option value="manuell">manuell</option>' +
                            '<option value="name ASC">Name aufsteigend</option>' +
                            '<option value="name DESC">Name absteigend</option>' +
                            '<option value="title ASC">Title aufsteigend</option>' +
                            '<option value="title DESC">Title absteigend</option>' +

                            '<option value="c_date ASC">Erstellungsdatum aufsteigend</option>' +
                            '<option value="c_date DESC">Erstellungsdatum absteigend</option>' +
                            '<option value="d_date ASC">Editierungsdatum aufsteigend</option>' +
                            '<option value="d_date DESC">Editierungsdatum absteigend</option>' +
                        '</select>' +
                        '<div class="qui-project-children-sort-container"></div>'
            });

            this.Loader.inject( this.$Elm );

            this.$Container = this.$Elm.getElement(
                '.qui-project-children-sort-container'
            );

            this.$Select = this.$Elm.getElement( '[name="order-type"]' );

            this.$GridTable = new Grid(this.$Container, {
                columnModel : [{
                    header    : 'ID',
                    dataIndex : 'id',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : 'Site-Name',
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Site-Titel',
                    dataIndex : 'title',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Erstellungsdatum',
                    dataIndex : 'c_date',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Editierungsdatum',
                    dataIndex : 'e_date',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Sortierungsfeld',
                    dataIndex : 'order_field',
                    dataType  : 'string',
                    width     : 150
                }],
                buttons : [{
                    name      : 'up',
                    textimage : 'icon-angle-up',
                    text      : 'hoch',
                    disabled  : true,
                    events    :
                    {
                        onClick : function() {
                            GridTable.moveup();
                        }
                    }
                }, {
                    name      : 'down',
                    textimage : 'icon-angle-down',
                    text      : 'runter',
                    disabled  : true,
                    events    :
                    {
                        onClick : function() {
                            GridTable.movedown();
                        }
                    }
                }, {
                    name      : 'sortSave',
                    textimage : 'icon-save',
                    text      : 'Sortierung speichern',
                    disabled  : true,
                    events    :
                    {
                        onClick : function(Btn)
                        {
                            Btn.setAttribute( 'textimage', 'icon-refresh icon-spin' );

                            self.saveSort(Site, GridTable, function() {
                                Btn.setAttribute( 'textimage', 'icon-save' );
                            });
                        }
                    }
                }],
                height     : 300,
                pagination : true,
                onrefresh  : function() {
                    self.displayChildren();
                }
            });


            this.$Select.value = this.$Site.getAttribute( 'order_type' );

            this.$Select.addEvent('change', function()
            {
                self.$Site.setAttribute( 'order_type', this.value );
                self.disableUpDownButtons();
            });

            this.displayChildren();


            return this.$Elm;
        },

        /**
         * Display the children in the grid
         *
         * @param {controls/projects/project/Panel} Panel - Site Panel
         * @param {controls/grid/Grid} GridTable - grid in the site panel
         */
        displayChildren : function()
        {
            this.Loader.show();

            var self    = this,
                perPage = this.$GridTable.options.perPage,
                page    = this.$GridTable.options.page;

            var limit = ((page - 1) * perPage) +','+ perPage;

            this.$Site.getChildren(function(result)
            {
                var i, len, entry;
                var data = [];

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    entry = result[ i ];

                    data.push({
                        id     : entry.id,
                        name   : entry.name,
                        title  : entry.title,
                        e_date : entry.e_date,
                        c_date : entry.c_date,
                        order_field : entry.order_field
                    });
                }

                self.$GridTable.setData({
                    data    : data,
                    total   : self.$Site.countChild(),
                    page    : page,
                    perPage : perPage
                });

                self.Loader.hide();

            }, {
                limit : limit
            });
        },

        /**
         * Enable the up and down buttons
         *
         * @param {DOMNode} Content - parent node
         */
        enableUpDownButtons : function(Content)
        {
            var buttons = this.$Container.getElements( 'button' );

            for ( var i = 0, len = buttons.length; i < len; i++ )
            {
                var quiid  = buttons[ i ].get('data-quiid'),
                    Button = QUI.Controls.getById( quiid );

                if ( !Button ) {
                    continue;
                }

                if ( Button.getAttribute('name') != 'up' &&
                     Button.getAttribute('name') != 'down' &&
                     Button.getAttribute('name') != 'sortSave')
                {
                    continue;
                }

                Button.enable();
            }
        },

        /**
         * Disable the up and down buttons
         *
         * @param {DOMNode} Content - parent node
         */
        disableUpDownButtons : function(Content)
        {
            var buttons = this.$Container.getElements( 'button' );

            for ( var i = 0, len = buttons.length; i < len; i++ )
            {
                var quiid  = buttons[ i ].get('data-quiid'),
                    Button = QUI.Controls.getById( quiid );

                if ( !Button ) {
                    continue;
                }

                if ( Button.getAttribute('name') != 'up' &&
                     Button.getAttribute('name') != 'down' &&
                     Button.getAttribute('name') != 'sortSave')
                {
                    continue;
                }

                Button.disable();
            }
        },

        /**
         * Save the actually sort of the children
         *
         * @param {classes/projects/project/Site} Site
         * @param {controls/grid/Grid} GridTable - grid in the site panel
         * @param {Function} callback - [optional] callback function
         */
        save : function(Site, GridTable, callback)
        {
            var i, len;

            var Project = this.$Site.getProject(),
                ids     = [],
                perPage = GridTable.options.perPage,
                page    = GridTable.options.page,
                data    = GridTable.getData();


            for ( i = 0, len = data.length; i < len; i++ ) {
                ids.push( data[ i ].id );
            }

            Ajax.post('ajax_site_children_sort', function()
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            }, {
                project : Project.getName(),
                lang    : Project.getLang(),
                ids     : JSON.encode( ids ),
                start   : (page - 1) * perPage
            });
        },

        /**
         * event on inject
         */
        $onInject : function()
        {
            this.$onResize();
        },

        /**
         * event on resize
         */
        $onResize : function()
        {
            var Parent = this.getElm().getParent(),
                size   = Parent.getSize();

            this.$GridTable.setHeight( size.y - 100 );
        }
    });

    return {

        open : function(Sheet)
        {

        },

        /**
         * event onload navigation
         *
         * @param {qui/controls/buttons/Button} Category
         * @param {qui/controls/desktop/Panel} Panel
         */
        onload : function(Category, Panel)
        {
            var self       = this,
                Content    = Panel.getContent(),
                Navigation = Content.getElement('.qui-site-navigation'),
                Select     = Content.getElement( '[name="order-type"]' ),
                Site       = Panel.getSite(),
                Project    = Site.getProject(),

                size   = Content.getSize(),
                height = size.y - 100;

            Navigation.setStyles({
                height     : height,
                paddingTop : 20
            });

            var GridTable = new Grid(Navigation, {
                columnModel : [{
                    header    : 'ID',
                    dataIndex : 'id',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : 'Site-Name',
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Site-Titel',
                    dataIndex : 'title',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : 'Erstellungsdatum',
                    dataIndex : 'c_date',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Editierungsdatum',
                    dataIndex : 'e_date',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Sortierungsfeld',
                    dataIndex : 'order_field',
                    dataType  : 'string',
                    width     : 150
                }],
                buttons : [{
                    name      : 'up',
                    textimage : 'icon-angle-up',
                    text      : 'hoch',
                    disabled  : true,
                    events    :
                    {
                        onClick : function() {
                            GridTable.moveup();
                        }
                    }
                }, {
                    name      : 'down',
                    textimage : 'icon-angle-down',
                    text      : 'runter',
                    disabled  : true,
                    events    :
                    {
                        onClick : function() {
                            GridTable.movedown();
                        }
                    }
                }, {
                    name      : 'sortSave',
                    textimage : 'icon-save',
                    text      : 'Sortierung speichern',
                    disabled  : true,
                    events    :
                    {
                        onClick : function(Btn)
                        {
                            Btn.setAttribute( 'textimage', 'icon-refresh icon-spin' );

                            self.saveSort(Site, GridTable, function() {
                                Btn.setAttribute( 'textimage', 'icon-save' );
                            });
                        }
                    }
                }],
                height : height,
                pagination : true,
                onrefresh : function() {
                    self.displayChildren( Panel, GridTable );
                }
            });

            GridTable.addEvents({
                click : function()
                {
                    var sel = GridTable.getSelectedIndices();

                    if ( !sel.length ) {
                        return;
                    }

                    if ( Select.value == 'manuell' )
                    {
                        self.enableUpDownButtons( Content );
                    } else
                    {
                        self.disableUpDownButtons( Content );
                    }
                }
            });


            Select.value = Site.getAttribute( 'order_type' );

            Select.addEvent('change', function()
            {
                Site.setAttribute( 'order_type', this.value );

                self.disableUpDownButtons( Content );
            });

            this.displayChildren( Panel, GridTable );


            Panel.addEvents({
                onResize : this.onResize
            });

            Select.fireEvent( 'change' );

            // site event handling
            Site.addEvent( 'onCreateChild', this.onChildCreate );
            // Project.addEvent( 'onSiteDelete', this.onSiteDelete );
        },

        /**
         * event onunload navigation
         *
         * @param {qui/controls/buttons/Button} Category
         * @param {qui/controls/desktop/Panel} Panel
         */
        onunload : function(Category, Panel)
        {
            var Site    = Panel.getSite(),
                Project = Site.getProject();

            Panel.removeEvent( 'onResize', this.onResize );

            Site.removeEvent( 'onCreateChild', this.onChildCreate );
            Project.removeEvent( 'onSiteDelete', this.onSiteDelete );
        },

        /**
         * resize
         */
        onResize : function(Panel)
        {
            var Content    = Panel.getContent(),
                Navigation = Content.getElement('.qui-site-navigation'),
                size       = Content.getSize(),
                height     = size.y - 100;

            Navigation.setStyle( 'height', height );

            var GridTable = QUI.Controls.getById(
                Content.getElement( '.omnigrid' ).get( 'data-quiid' )
            );

            GridTable.setHeight( height );
        },

        /**
         * event: on child create
         */
        onChildCreate : function(Site, newid)
        {
            require(['controls/projects/project/site/siteSort'], function(siteSort)
            {
                var i, len, Panel, Content, GridTable, GridContainer;

                var Project = Site.getProject();

                // get site panels
                var panelName = 'panel-'+
                                Project.getName() +'-'+
                                Project.getLang() +'-'+
                                Site.getId();

                var panels = QUI.Controls.get( panelName );

                for ( i = 0, len = panels.length; i < len; i++ )
                {
                    Panel   = panels[ i ];
                    Content = Panel.getContent();

                    GridContainer = Content.getElement( '.omnigrid' );

                    if ( !GridContainer ) {
                        continue;
                    }

                    GridTable = QUI.Controls.getById(
                        GridContainer.get( 'data-quiid' )
                    );

                    siteSort.displayChildren( Panel, GridTable );
                }
            });
        },

        /**
         * event: on site delete
         *
         * vorerst nicht umgesetzt, da das parent panel gesucht werden muss
         * die site aber nicht mehr existiert und somit auch die parentid nicht gefunden werden kann
         */
//        onSiteDelete : function(Project, siteid)
//        {
//            var i, len, data, Panel, Content, GridTable, GridContainer;
//
//            // get site panels
//            var panelName = 'panel-'+
//                            Project.getName() +'-'+
//                            Project.getLang() +'-'+
//                            siteid;
//
//            var panels = QUI.Controls.get( panelName );
//
//            for ( i = 0, len = panels.length; i < len; i++ )
//            {
//                Panel   = panels[ i ];
//                Content = Panel.getContent();
//
//                GridContainer = Content.getElement( '.omnigrid' );
//
//                if ( !GridContainer ) {
//                    continue;
//                }
//
//                GridTable = QUI.Controls.getById(
//                    GridContainer.get( 'data-quiid' )
//                );
//
//                // check if the site id is in the children
//                data = GridTable.getData();
//
//                console.log( data );
//
//            }
//        },

        /**
         * Display the children in the grid
         *
         * @param {controls/projects/project/Panel} Panel - Site Panel
         * @param {controls/grid/Grid} GridTable - grid in the site panel
         */
        displayChildren : function(Panel, GridTable)
        {
            Panel.Loader.show();

            var Site = Panel.getSite();

            var perPage = GridTable.options.perPage,
                page    = GridTable.options.page;

            var limit = ((page - 1) * perPage) +','+ perPage;

            Site.getChildren(function(result)
            {
                var i, len, entry;
                var data = [];

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    entry = result[ i ];

                    data.push({
                        id     : entry.id,
                        name   : entry.name,
                        title  : entry.title,
                        e_date : entry.e_date,
                        c_date : entry.c_date,
                        order_field : entry.order_field
                    });
                }

                GridTable.setData({
                    data    : data,
                    total   : Site.countChild(),
                    page    : page,
                    perPage : perPage
                });

                Panel.Loader.hide();
            }, {
                limit : limit
            });
        },

        /**
         * Enable the up and down buttons
         *
         * @param {DOMNode} Content - parent node
         */
        enableUpDownButtons : function(Content)
        {
            var Navigation = Content.getElement('.qui-site-navigation'),
                buttons    = Navigation.getElements( 'button' );

            for ( var i = 0, len = buttons.length; i < len; i++ )
            {
                var quiid  = buttons[ i ].get('data-quiid'),
                    Button = QUI.Controls.getById( quiid );

                if ( !Button ) {
                    continue;
                }

                if ( Button.getAttribute('name') != 'up' &&
                     Button.getAttribute('name') != 'down' &&
                     Button.getAttribute('name') != 'sortSave')
                {
                    continue;
                }

                Button.enable();
            }
        },

        /**
         * Disable the up and down buttons
         *
         * @param {DOMNode} Content - parent node
         */
        disableUpDownButtons : function(Content)
        {
            var Navigation = Content.getElement('.qui-site-navigation'),
                buttons    = Navigation.getElements( 'button' );

            for ( var i = 0, len = buttons.length; i < len; i++ )
            {
                var quiid  = buttons[ i ].get('data-quiid'),
                    Button = QUI.Controls.getById( quiid );

                if ( !Button ) {
                    continue;
                }

                if ( Button.getAttribute('name') != 'up' &&
                     Button.getAttribute('name') != 'down' &&
                     Button.getAttribute('name') != 'sortSave')
                {
                    continue;
                }

                Button.disable();
            }
        },

        /**
         * Save the actually sort of the children
         *
         * @param {classes/projects/project/Site} Site
         * @param {controls/grid/Grid} GridTable - grid in the site panel
         * @param {Function} callback - [optional] callback function
         */
        saveSort : function(Site, GridTable, callback)
        {
            var i, len;

            var Project = Site.getProject(),
                ids     = [],
                perPage = GridTable.options.perPage,
                page    = GridTable.options.page,
                data    = GridTable.getData();


            for ( i = 0, len = data.length; i < len; i++ ) {
                ids.push( data[ i ].id );
            }

            Ajax.post('ajax_site_children_sort', function()
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            }, {
                project : Project.getName(),
                lang    : Project.getLang(),
                ids     : JSON.encode( ids ),
                start   : (page - 1) * perPage
            });

        }
    };

});