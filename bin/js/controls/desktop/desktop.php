<?php

    /**
     * QUIQQER Widget Board
     *
     * You can access the Board without the QUIQQER Admin
     */

    if ( !\QUI::getUserBySession()->getId() )
    {

        exit;
    }

    $GRIDSTER_DIR = URL_BIN_DIR .'js/lib/gridster/';

    // load widgets
    $widgets = \QUI_Desktop::getWidgetList();
    $list    = array();

    foreach ( $widgets as $Widget ) {
        $list[] = $Widget->getAttributes();
    }

?>
<!doctype html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="de"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="de"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="de"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="de"> <!--<![endif]-->
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <link rel="stylesheet" href="<?php echo URL_BIN_DIR ?>js/controls/desktop/Wall.Loader.css" />
    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>normalize.css" />
    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>main.css" />
    <link rel="stylesheet" href="<?php echo URL_BIN_DIR; ?>css/classes.css" />
    <link rel="stylesheet" href="<?php echo URL_BIN_DIR; ?>css/buttons.css" />
    <link rel="stylesheet" href="<?php echo URL_BIN_DIR; ?>css/tables.css" />
    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>css/font-awesome.min.css">

    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>jquery.gridster.min.css" />
    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>loader.css" />

    <title>QUIQQER - Desktop</title>

    <?php

    echo '
        <script type="text/javascript">
        /* <![CDATA[ */
            var USER = {
                isSU : '. ($User->isSU() ? 1 : 0) .',
                id   : '. $User->getId() .',
                lang : "'. $User->getLang() .'"
            };

            var URL_DIR     = "'. URL_DIR .'",
                URL_LIB_DIR = "'. URL_LIB_DIR .'",
                URL_BIN_DIR = "'. URL_BIN_DIR .'",
                URL_USR_DIR = "'. URL_USR_DIR .'",
                URL_SYS_DIR = "'. URL_SYS_DIR .'",
                URL_OPT_DIR = "'. URL_OPT_DIR .'";
                URL_VAR_DIR = "'. URL_VAR_DIR .'";

            var PHP = {
                upload_max_filesize : "'. \Utils_System::getUploadMaxFileSize() .'"
            };

            var QUI_CONFIG  = '. json_encode( $config ) .';
            var QUI_DESKTOP_WIDGETS = '. json_encode( $list ) .';

        /* ]]> */
        </script>';
    ?>
</head>
<body>

    <div class="qui-wall-loader">
        <div class="windows8">
            <div class="wBall" id="wBall_1">
                <div class="wInnerBall"></div>
            </div>
            <div class="wBall" id="wBall_2">
                <div class="wInnerBall"></div>
            </div>
            <div class="wBall" id="wBall_3">
                <div class="wInnerBall"></div>
            </div>

            <div class="wBall" id="wBall_4">
                <div class="wInnerBall"></div>
            </div>

            <div class="wBall" id="wBall_5">
                <div class="wInnerBall"></div>
            </div>
        </div>
    </div>

    <script src="<?php echo URL_BIN_DIR; ?>js/mvc/require.js"></script>

    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/mootools-core-1.4.5.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/mootools-more.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/moofx.js" type="text/javascript"></script>

    <!-- bad defines @todo, must implemented into quiqqer -->
    <script src="<?php echo URL_BIN_DIR; ?>js/mocha/define.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/classes/messages/define.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/controls/windows/define.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/classes/desktop/define.js" type="text/javascript"></script>


    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
    <script src="<?php echo $GRIDSTER_DIR ?>jquery.gridster.min.js"></script>

    <script>
        $.noConflict();

        // require config
        require.config({
            baseUrl : URL_BIN_DIR +'js/',
            paths   : {
                "package" : URL_OPT_DIR,
                "admin"   : URL_SYS_DIR +'bin/',
                "locale"  : URL_VAR_DIR +'locale/bin/'
            },

            waitSeconds : 0,
            locale      : "de-de",
            catchError  : true,
            map: {
                '*': {
                    'css': 'mvc/css'
                }
            }
        });


        var QUI;

        // we draw the desktop
        require(['classes/QUIQQER'], function(QUIQQER)
        {
            "use strict";

            QUI = new QUIQQER();
            QUI.config( 'dir', URL_BIN_DIR +'js/' );

            QUI.addEvent('onLoad', function()
            {
                "use strict";

                require([
                    'controls/desktop/Wall'
                ], function(Wall)
                {
                    new Wall().inject(
                        document.body
                    ).load();
                });
            });

            QUI.load();
        });

        jQuery( window ).resize(function()
        {
            // var gridster = $('.gridster ul').gridster().data('gridster');
            // console.log( gridster.serialize() );
        });

    </script>
</body>
</html>