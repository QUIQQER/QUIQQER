<?php

    require 'header.php';

    $Plugins = QUI::getPlugins();
    $plugins = $Plugins->getAvailablePlugins(false, true);

?>

<!doctype html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="de"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="de"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="de"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="de"> <!--<![endif]-->
<head>
    <!-- HTML5
          ================================================== -->
    <!--[if lt IE 9]>
        <script src="<?php echo URL_BIN_DIR; ?>js/mvc/html5.js"></script>
    <![endif]-->

    <title>QUIQQER Content Management System - <?php echo HOST ?></title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <?php
        $Standard = false;

        try
        {
            $Standard = \Projects_Manager::getStandard();
        } catch ( QException $e )
        {
            // nothing
        }

        $config = array();
        $config['globals'] = \QUI::conf( 'globals' );

        // locale file
        try
        {
            $files = \QUI\Translator::getJSTranslationFiles( $User->getLang() );
        } catch (QException $e )
        {

        }

        $locales = array();

        foreach ( $files as $package => $file ) {
            $locales[] = $package .'/'. $User->getLang();
        }

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
            var QUI_LOCALES = '. json_encode( $locales, true ) .';

        /* ]]> */
        </script>';
    ?>

    <!-- mootools -> combine the js -->
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/mootools-core-1.4.5.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/mootools-more.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/moofx.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/Elements.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/Object.js" type="text/javascript"></script>
    <script src="<?php echo URL_BIN_DIR; ?>js/mootools/Slick.js" type="text/javascript"></script>

    <!-- QUIQQER && require -->
    <script data-main="<?php echo URL_BIN_DIR; ?>js/QUIQQER.js" src="<?php echo URL_BIN_DIR; ?>js/mvc/require.js" type="text/javascript"></script>

    <!-- MochUI define -->
    <script src="<?php echo URL_BIN_DIR; ?>js/mocha/define.js" type="text/javascript"></script>

    <!-- Error Handler define -->
    <script src="<?php echo URL_BIN_DIR; ?>js/classes/messages/define.js" type="text/javascript"></script>

    <!-- Windows define -->
    <script src="<?php echo URL_BIN_DIR; ?>js/controls/windows/define.js" type="text/javascript"></script>

    <!-- Desktop define -->
    <script src="<?php echo URL_BIN_DIR; ?>js/classes/desktop/define.js" type="text/javascript"></script>

    <!-- CSS -> combine it -->
    <style type="text/css">
        @import url("<?php echo URL_BIN_DIR; ?>css/style.css");
        @import url("<?php echo URL_BIN_DIR; ?>css/classes.css");
        @import url("<?php echo URL_BIN_DIR; ?>css/elements.css");
        @import url("<?php echo URL_BIN_DIR; ?>css/tables.css");
        @import url("<?php echo URL_BIN_DIR; ?>css/buttons.css");
        @import url("<?php echo URL_BIN_DIR; ?>css/animate.css");
    </style>

    <?php
        // Admin Header Includes
        /*
        foreach ( $plugins as $plugin )
        {
            if ( file_exists(OPT_DIR . $plugin .'/admin/bin/define.js') ) {
                echo '<script src="'. URL_OPT_DIR . $plugin .'/admin/bin/define.js" type="text/javascript"></script>';
            }
        }

        // packages defines
        echo QUI::getPackageManager()->getJavaScriptDefines();
        */
    ?>

</head>
<body>
<noscript>
    <div class="error" style="position: absolute; z-index: 100000; width: 100%; height: 30px; line-height: 30px; color: red; text-align: center;">
        JavaScript ist in Ihrem Browser nicht aktiviert. Bitte aktivieren Sie JavaScript
    </div>
</noscript>

</body>
</html>