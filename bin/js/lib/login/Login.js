/**
 * Login Window
 *
 * @author Henning Leutz
 */

define('lib/login/Login', [

    'controls/windows',
    'css!'+ QUI.config('dir') +'lib/login/Login.css'

], function()
{
    QUI.namespace( 'lib' );

    QUI.lib.Login =
    {
        $Win : null,

        show : function(text)
        {
            if (this.$Win) {
                return;
            }

            this.$Win = QUI.Windows.create('submit', {
                title    : 'Login',
                name     : 'login',
                width    : 500,
                height   : 200,
                text     : text,
                texticon : URL_BIN_DIR +'48x48/login.png',
                events   :
                {
                    onSubmit : function(Win)
                    {
                        this.login(
                            $('login.username').value,
                            $('login.password').value,

                            function(result, Request)
                            {
                                this.close();

                            }.bind(this)
                        );

                        this.$Win.setAttribute( 'autoclose', false );
                    }.bind( this ),

                    onDrawEnd : function(Win)
                    {
                        QUI.lib.Login.loadTemplate( Win );
                    }
                }
            });
        },

        close : function()
        {
            this.$Win.close();
        },

        loadTemplate : function(Win)
        {
            Win.Loader.show();

            QUI.Ajax.get('ajax_login_template', function(result, Request)
            {
                var Win  = Request.getAttribute('Win'),
                    Body = Win.getBody();

                if (Body.getElement('.textbody'))
                {
                    new Element('div', {
                        html : result
                    }).inject( Body.getElement('.textbody') );

                    Body.getElement('form').addEvent('submit', function(event) {
                        event.stop();
                    });

                    Body.getElements('input').addEvent('keyup', function(event)
                    {
                        if (event.key === 'enter') {
                            this.fireEvent('submit');
                        }
                    }.bind( Win ));

                    $('login.username').focus();
                }

                Win.Loader.hide();
            }, {
                Win : Win
            });
        },

        login : function(username, password, logincallback)
        {
            QUI.Ajax.get('ajax_login_login', function(result, Request)
            {
                if (Request.getAttribute('logincallback')) {
                    Request.getAttribute('logincallback')(result, Request);
                }
            }, {
                username : username,
                password : password,
                logincallback : logincallback,
                onError  : function(Exception, Request)
                {
                    QUI.MH.addException( Exception );
                }
            });
        }
    };

    return QUI.lib.Login;
});