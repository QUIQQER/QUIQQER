/**
 * A media Popup
 *
 * @author www.namerobot.com (Henning Leutz)
 */

define('controls/projects/project/media/Popup', [

    'qui/controls/windows/Popup',
    'controls/projects/project/media/Panel',
    'Projects'

], function(Popup, MediaPanel, Projects)
{
    "use strict";

    return new Class({

        Extends : Popup,
        Type    : 'controls/projects/project/media/Popup',

        Binds : [
            '$onCreate'
        ],

        options : {
            project    : false,
            selectable : true
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Panel = null;

            this.addEvent( 'onCreate', this.$onCreate );
        },

        /**
         * event : on create
         */
        $onCreate : function()
        {
            this.Loader.show();

            var Media, Project;

            var self    = this,
                project = this.getAttribute( 'project' );

            if ( !project ) {
                project = Projects.Standard.getName();
            }

            Project = Projects.get( project );
            Media   = Project.getMedia();

            this.$Panel = new MediaPanel(Media, {
                selectable : true,
                events :
                {
                    onCreate : function() {
                        self.Loader.hide();
                    },

                    onChildClick : function(Popup, imageData)
                    {
                        if ( imageData.type == 'folder' )
                        {
                            self.$Panel.openID( imageData.id );
                            return;
                        }

                        self.close();

                        self.fireEvent( 'submit', [ self, imageData ] );
                    }
                }
            });

            this.$Panel.inject( this.getContent() );
        }

    });

});