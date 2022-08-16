<?php

use QUI\InstallationWizard\ProviderHandler;

const QUIQQER_SYSTEM = true;

require_once dirname(__FILE__, 7) . '/header.php';

// Set a valid header so browsers pick it up correctly.
header('Content-type: text/html; charset=utf-8');

// https://www.jeffgeerling.com/blog/2016/streaming-php-disabling-output-buffering-php-apache-nginx-and-varnish

ob_start();

function flushIt()
{
    if (!headers_sent()) {
        // Disable gzip in PHP.
        ini_set('zlib.output_compression', 0);

        // Force disable compression in a header.
        // Required for flush in some cases (Apache + mod_proxy, nginx, php-fpm).
        header('Content-Encoding: none');
    }

    // Fill-up 4 kB buffer (should be enough in most cases).
    echo str_pad('', 4 * 1024);

    // Flush all buffers.
    do {
        $flushed = @ob_end_flush();
    } while ($flushed);

    @ob_flush();
    flush();
}

?>

    <html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

        <title>Installation Wizard</title>
        <link href="execute.css" rel="stylesheet" type="text/css"/>
    </head>
<?php
flushIt();
?>
<body>

    <script>
        const URL_DIR     = "<?php echo URL_DIR; ?>",
              URL_LIB_DIR = "<?php echo URL_LIB_DIR; ?>",
              URL_BIN_DIR = "<?php echo URL_BIN_DIR; ?>",
              URL_USR_DIR = "<?php echo URL_USR_DIR; ?>",
              URL_SYS_DIR = "<?php echo URL_SYS_DIR; ?>",
              URL_OPT_DIR = "<?php echo URL_OPT_DIR; ?>",
              URL_VAR_DIR = "<?php echo URL_VAR_DIR; ?>";
    </script>

<?php
flushIt();

$Config   = ProviderHandler::getConfig();
$provider = $Config->get('execute', 'provider');
$data     = $Config->get('execute', 'data');
$data     = json_decode($data, true);

$interfaces = class_implements($provider);

if (!isset($interfaces['QUI\InstallationWizard\InstallationWizardInterface'])) {
    // @todo window parent close frame and show error
    exit;
}

/* @var $Provider QUI\InstallationWizard\InstallationWizardInterface */
$Provider  = new $provider();
$execSteps = $Provider->getExecuteSteps();

?>
    <script>
        window.STEPS = <?php echo json_encode($execSteps); ?>;
    </script>
<?php
echo $Provider->getExecuteContent(); ?>

    <div class="wizard-steps"></div>

    <script src="<?php
    echo URL_OPT_DIR; ?>bin/quiqqer-asset/requirejs/requirejs/require.js"></script>
    <script src="execute.js"></script>
    <div class="wizard-process" style="display: none">
<?php

flushIt();

$Provider->execute(json_decode($data, true));
