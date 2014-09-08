
/**
 * System  user profile
 *
 * @module controls/system/Profile
 * @author www.pcsg.de (Henning Leutz)
 */

define([

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'utils/Controls',
    'Ajax',
    'Locale',

    'css!controls/system/Profile.css'

], function(QUI, QUIConfirm, FormUtils, ControlUtils, Ajax, Locale)
{
    "use strict";


    return new Class({

        Extends : QUIConfirm,
        Type    : 'controls/system/Profile',

        Binds : [
            '$onOpen',
            '$onSubmit'
        ],

        options : {
            title  : 'Profil',
            icon   : 'icon-user',
            maxHeight : 500,
            maxWidth  : 700,
            autoclose : false,
            ok_button : {
                text : 'Speichern'
            },
            close_button : {
                text : 'Abbrechen'
            }
        },

        initialize : function(options)
        {
            this.parent( options );

            this.addEvents({
                onOpen   : this.$onOpen,
                onSubmit : this.$onSubmit
            });
        },

        /**
         * event : on open
         */
        $onOpen : function()
        {
            var self    = this,
                Content = this.getContent();

            this.Loader.show();

            Ajax.get([
                'ajax_users_get',
                'ajax_user_profileTemplate'
            ], function(data, profileTemplate)
            {
                if ( !Content ) {
                    return;
                }

                Content.set(
                    'html',
                    '<form class="qui-control-profil">'+ profileTemplate +'</form>'
                );

                FormUtils.setDataToForm( data, Content.getElement( 'form' ) );
                ControlUtils.parse( Content );


                self.Loader.hide();
            }, {
                uid : USER.id
            });
        },

        /**
         * event : on submit
         */
        $onSubmit : function()
        {
            this.Loader.show();

            var self    = this,
                Content = this.getContent(),
                Form    = Content.getElement( 'form' );

            var data = FormUtils.getFormData( Form );

            Ajax.post('ajax_users_save', function()
            {
                // reload if lang not the current lang
                if ( Locale.getCurrent() !== data.lang )
                {
                    window.location.reload();
                    return;
                }

                self.close();
            }, {
                uid        : USER.id,
                attributes : JSON.encode( data )
            });
        }

    });
});