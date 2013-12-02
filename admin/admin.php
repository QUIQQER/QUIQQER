<?php

    require 'header.php';

    $Plugins = \QUI::getPlugins();
    $plugins = $Plugins->getAvailablePlugins(false, true);

    // qui path
    $qui_path   =  URL_OPT_DIR .'bin/qui/';
    $qui_extend =  URL_OPT_DIR .'bin/qui/extend/';

?>
<!doctype html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="<?php echo $User->getLang(); ?>"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="<?php echo $User->getLang(); ?>"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="<?php echo $User->getLang(); ?>"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="<?php echo $User->getLang(); ?>"> <!--<![endif]-->
<head>

    <link href="//fonts.googleapis.com/css?family=Open+Sans:400,700,400italic|Bitter"
        rel="stylesheet"
        type="text/css"
    />

    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1,maximum-scale=1"
    />

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <!-- HTML5
        ================================================== -->

    <title>QUIQQER Content Management System - <?php echo HOST ?></title>

    <link href="<?php echo URL_DIR; ?>favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />

    <!-- [begin] css combine -->

    <link href="<?php echo $qui_extend; ?>font-awesome/css/font-awesome.min.css"
        rel="stylesheet"
        type="text/css"
    />

    <!--[if (lt IE 9) & (!IEMobile)]>
        <link href="<?php echo $qui_extend; ?>unsemantic/ie.css"
            rel="stylesheet"
            type="text/css"
        />
    <![endif]-->

    <link href="<?php echo $qui_extend; ?>unsemantic/unsemantic-grid-responsive.css"
        rel="stylesheet"
        type="text/css"
    />

    <link href="<?php echo $qui_extend; ?>animate.min.css"
        rel="stylesheet"
        type="text/css"
    />

    <link href="<?php echo $qui_extend; ?>classes.css"
        rel="stylesheet"
        type="text/css"
    />

    <link href="<?php echo $qui_extend; ?>buttons.css"
        rel="stylesheet"
        type="text/css"
    />

    <link href="<?php echo URL_BIN_DIR; ?>css/style.css"
        rel="stylesheet"
        type="text/css"
    />

    <script type="text/javascript">
    /* <![CDATA[ */
        var USER = {
            isSU : <?php echo $User->isSU() ? 1 : 0; ?>,
            id   : <?php echo $User->getId(); ?>,
            lang : "<?php echo $User->getLang(); ?>"
        };

        var URL_DIR     = "<?php echo URL_DIR; ?>",
            URL_LIB_DIR = "<?php echo URL_LIB_DIR; ?>",
            URL_BIN_DIR = "<?php echo URL_BIN_DIR; ?>",
            URL_USR_DIR = "<?php echo URL_USR_DIR; ?>",
            URL_SYS_DIR = "<?php echo URL_SYS_DIR; ?>",
            URL_OPT_DIR = "<?php echo URL_OPT_DIR; ?>",
            URL_VAR_DIR = "<?php echo URL_VAR_DIR; ?>";

        var PHP = {
            upload_max_filesize : "<?php echo \QUI\Utils\System::getUploadMaxFileSize(); ?>"
        };

        var QUIQQER_VERSION = '1.0.0';

    /* ]]> */
    </script>

</head>
<body class="<?php echo $User->getLang(); ?>">

    <div id="wrapper">
        <div class="qui-logo-container grid-100 grid-parent">
            <img src="<?php echo URL_BIN_DIR; ?>quiqqer_logo_mini.png" />

            <p>
                QUIQQER Managament System - www.namerobot.com
            </p>
        </div>

        <div class="qui-menu-container grid-100 grid-parent">

        </div>

        <div class="qui-workspace-container grid-100 grid-parent">

        </div>
    </div>

    <noscript>
        <div class="error" style="position: absolute; z-index: 100000; width: 100%; height: 30px; line-height: 30px; color: red; text-align: center;">
            JavaScript ist in Ihrem Browser nicht aktiviert. Bitte aktivieren Sie JavaScript
        </div>
    </noscript>

    <script src="<?php echo URL_OPT_DIR; ?>bin/require.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/src/lib/mootools-core.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/src/lib/mootools-more.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/src/lib/moofx.js"></script>

    <!-- load the quiqqer admin -->
    <script src="<?php echo URL_BIN_DIR; ?>QUI/init.js"></script>

</body>
</html>