/**
 *
 */

define('controls/projects/project/site/siteSort', [

    'qui/QUI',
    'controls/grid/Grid'

], function(QUI, Grid)
{
    "use strict";

    return {

        /**
         * event onload navigation
         *
         * @param {qui/controls/buttons/Button} Category
         * @param {qui/controls/desktop/Panel} Panel
         */
        onload : function(Category, Panel)
        {
            var Content    = Panel.getContent(),
                Navigation = Content.getElement('.qui-site-navigation'),
                Site       = Panel.getSite(),
                size       = Content.getSize(),
                height     = size.y - 100;

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
                }],
                height : height,
                pagination : true
            });


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
                        c_date : entry.c_date
                    });
                }

                GridTable.setData({
                    data : data
                });

                Panel.Loader.hide();
            });

            Panel.addEvents({
                onResize : this.onResize
            });
        },

        /**
         * event onunload navigation
         *
         * @param {qui/controls/buttons/Button} Category
         * @param {qui/controls/desktop/Panel} Panel
         */
        onunload : function(Category, Panel)
        {
            Panel.removeEvent( 'onResize', this.onResize );
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
        }
    };

});