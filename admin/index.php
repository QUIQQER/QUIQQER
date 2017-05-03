<?php

define('QUIQQER_SYSTEM', true);
require 'header.php';

// qui path
$qui_path   = URL_OPT_DIR . 'bin/qui/';
$qui_extend = URL_OPT_DIR . 'bin/qui/extend/';

$config            = array();
$config['globals'] = QUI::conf('globals');
$config['gui']     = QUI::conf('gui');

$Project = null;

try {
    $Project = QUI::getProjectManager()->getStandard();
} catch (QUI\Exception $Exception) {
}
?>
<!doctype html>
<!--[if lt IE 7 ]>
<html class="ie ie6" lang="<?php echo $User->getLang(); ?>"> <![endif]-->
<!--[if IE 7 ]>
<html class="ie ie7" lang="<?php echo $User->getLang(); ?>"> <![endif]-->
<!--[if IE 8 ]>
<html class="ie ie8" lang="<?php echo $User->getLang(); ?>"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html lang="<?php echo $User->getLang(); ?>"> <!--<![endif]-->
<head>

    <link
            href="//fonts.googleapis.com/css?family=Open+Sans:400,700,400italic"
            rel="stylesheet"
            type="text/css"
    />

    <meta name="viewport"
          content="width=device-width, initial-scale=1, minimum-scale=1,maximum-scale=1"
    />

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <!-- HTML5
        ================================================== -->

    <title>QUIQQER - <?php echo HOST ?></title>

    <link href="<?php echo URL_BIN_DIR; ?>images/favicon.ico" rel="shortcut icon" type="image/x-icon"/>

    <!-- [begin] css combine -->

    <link href="<?php echo URL_BIN_DIR; ?>css/tables.css"
          rel="stylesheet"
          type="text/css"
    />

    <?php

    echo QUI\FontAwesome\EventHandler::fontawesome(false, false);

    ?>

    <!--    <link href="--><?php //echo $qui_extend; ?><!--font-awesome/css/font-awesome.min.css"-->
    <!--          rel="stylesheet"-->
    <!--          type="text/css"-->
    <!--    />-->
    <!---->
    <!--    <link href="--><?php //echo URL_BIN_DIR; ?><!--css/fonts/font-awesome.min.css"-->
    <!--          rel="stylesheet"-->
    <!--          type="text/css"-->
    <!--    />-->

    <!--[if (lt IE 9) & (!IEMobile)]>
    <link href="<?php echo $qui_extend; ?>unsemantic/ie.css"
          rel="stylesheet"
          type="text/css"
    />
    <![endif]-->

    <link
            href="<?php echo $qui_extend; ?>unsemantic/unsemantic-grid-responsive.css"
            rel="stylesheet"
            type="text/css"
    />

    <link
            href="<?php echo URL_OPT_DIR; ?>quiqqer/messages/bin/messages.css"
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

    <link href="<?php echo $qui_extend; ?>elements.css"
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
            isSU: <?php echo $User->isSU() ? 1 : 0; ?>,
            id  : <?php echo $User->getId() ? $User->getId() : 0; ?>,
            lang: "<?php echo $User->getLang(); ?>",
            name: "<?php echo $User->getName(); ?>"
        };

        var URL_DIR     = "<?php echo URL_DIR; ?>",
            URL_LIB_DIR = "<?php echo URL_LIB_DIR; ?>",
            URL_BIN_DIR = "<?php echo URL_BIN_DIR; ?>",
            URL_USR_DIR = "<?php echo URL_USR_DIR; ?>",
            URL_SYS_DIR = "<?php echo URL_SYS_DIR; ?>",
            URL_OPT_DIR = "<?php echo URL_OPT_DIR; ?>",
            URL_VAR_DIR = "<?php echo URL_VAR_DIR; ?>";

        var PHP = {
            upload_max_filesize: "<?php echo \QUI\Utils\System::getUploadMaxFileSize(); ?>",
            memory_limit       : <?php echo QUI\Utils\System::getMemoryLimit(); ?>
        };

        var QUIQQER_VERSION = "<?php echo \QUI::version() ?>";
        var QUIQQER_CONFIG  = <?php echo json_encode($config); ?>;

        // standard project
        var QUIQQER_PROJECT = <?php echo json_encode(array(
            'name' => $Project ? $Project->getName() : '',
            'lang' => $Project ? $Project->getLang() : ''
        )); ?>;

        var QUIQQER = {
            Rewrite         : {
                URL_PARAM_SEPARATOR  : "<?php echo \QUI\Rewrite::URL_PARAM_SEPARATOR; ?>",
                URL_SPACE_CHARACTER  : "<?php echo \QUI\Rewrite::URL_SPACE_CHARACTER; ?>",
                URL_PROJECT_CHARACTER: "<?php echo \QUI\Rewrite::URL_PROJECT_CHARACTER; ?>",
                SUFFIX               : "<?php echo \QUI\Rewrite::getDefaultSuffix(); ?>"
            },
            inAdministration: true,
            lu              : "<?php echo QUI::getPackageManager()->getLastUpdateDate(); ?>",
            installPackage  : function (packageName, version, server) {
                return new Promise(function (resolve) {
                    resolve();
                });
            }
        };

        /* ]]> */
    </script>

    <?php
    /**
     * locale file
     */

    $files = array();

    try {
        $files = \QUI\Translator::getJSTranslationFiles($User->getLang());
    } catch (\QUI\Exception $Exception) {
    }

    $locales = array();

    foreach ($files as $package => $file) {
        $locales[] = $package . '/' . $User->getLang();
    }

    echo '<script type="text/javascript">';
    echo '/* <![CDATA[ */';
    echo 'var QUIQQER_LOCALE = ' . json_encode($locales, true);
    echo '/* ]]> */';
    echo '</script>';

    \QUI::getEvents()->fireEvent('adminLoad');
    ?>

</head>
<body class="<?php echo $User->getLang(); ?>">

<div id="wrapper">
    <!--
        <div class="qui-logo-container grid-100 grid-parent">
            <a href="/admin/">
                <img src="<?php echo URL_BIN_DIR; ?>quiqqer_logo_mini.png"
                    title="QUIQQER - Open Source Management System for Entrepreneurs"
                />
            </a>
        </div>
        -->

    <div class="qui-menu-container grid-100 grid-parent"></div>
    <div class="qui-workspace-container grid-100 grid-parent"></div>
</div>

<noscript>
    <div class="error"
         style="position: absolute; z-index: 100000; width: 100%; height: 30px; line-height: 30px; color: red; text-align: center;">
        JavaScript ist in Ihrem Browser nicht aktiviert. Bitte aktivieren Sie
        JavaScript
    </div>
</noscript>

<script src="<?php echo URL_OPT_DIR; ?>bin/require.js"></script>
<script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-core.js"></script>
<script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-more.js"></script>
<script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/moofx.js"></script>

<!-- load the quiqqer admin -->
<script src="<?php echo URL_BIN_DIR; ?>QUI/init.js"></script>

<?php
QUI::getEvents()->fireEvent('adminLoadFooter');
?>

</body>
</html>