<?php

use QUI\InstallationWizard\ProviderHandler;

const QUIQQER_SYSTEM = true;

require_once dirname(__FILE__, 7) . '/header.php';

ob_start();

?>
    <html lang="en">
    <head>
        <title>Installation Wizard</title>
        <link href="execute.css" rel="stylesheet" type="text/css"/>

        <script>
            const URL_DIR     = "<?php echo URL_DIR; ?>",
                  URL_LIB_DIR = "<?php echo URL_LIB_DIR; ?>",
                  URL_BIN_DIR = "<?php echo URL_BIN_DIR; ?>",
                  URL_USR_DIR = "<?php echo URL_USR_DIR; ?>",
                  URL_SYS_DIR = "<?php echo URL_SYS_DIR; ?>",
                  URL_OPT_DIR = "<?php echo URL_OPT_DIR; ?>",
                  URL_VAR_DIR = "<?php echo URL_VAR_DIR; ?>";
        </script>
    </head>
<body>
<?php
ob_flush();

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
    <pre style="display: none">
<?php

ob_flush();

$Provider->execute(json_decode($data, true));

exit;
