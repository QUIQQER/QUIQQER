<?php

namespace QUI\System\Console\Tools;

use QUI;
use QUI\InstallationWizard\ProviderHandler;

/**
 * Execute the QUIQQER installation wizard
 *
 * @author  www.pcsg.de (Jan Wennrich)
 * @licence For copyright and license information, please view the /README.md
 */
class InstallationWizard extends QUI\System\Console\Tool
{
    public function __construct()
    {
        $this->systemTool = true;
        $this->setName('quiqqer:installation-wizard')
            ->setDescription('Execute the QUIQQER installation wizard with data from a given preset file')
            ->addArgument(
                'preset-file',
                'Path to a JSON file that contains the data to use for the wizard',
                false,
                false
            );
    }

    public function execute(): void
    {
        $presetFile = $this->getArgument('preset-file');

        if (!file_exists($presetFile)) {
            $this->writeLn("Error: The preset file '$presetFile' does not exist.", 'red');
        }

        $presetDataAsJsonString = file_get_contents($presetFile);
        $presetData = json_decode($presetDataAsJsonString, true);

        if ($presetData === null) {
            $this->writeLn("Error: Could not decode JSON in preset file '$presetFile'.", 'red');
        }

        $QuiqqerProvider = new QUI\InstallationWizard\QuiqqerProvider();

        // Write the preset data to the installation wizard config file (maybe for later use, web-wizard does that too)
        $ProviderHandlerConfig = ProviderHandler::getConfig();
        $ProviderHandlerConfig->set('execute', 'provider', $QuiqqerProvider::class);
        $ProviderHandlerConfig->set('execute', 'data', $presetDataAsJsonString);
        $ProviderHandlerConfig->save();

        // Execute the wizard with the preset data
        $QuiqqerProvider->execute($presetData);

        // Set a default workspace for the admin user, the wizard only sets it for the session user (systemuser on cli)
        QUI\Workspace\Manager::setStandardWorkspace(
            QUI::getUsers()->get(
                QUI::getConfig('etc/conf.ini.php')->get('globals', 'rootuser')
            ),
            1
        );

        // Mark the wizard as done
        ProviderHandler::setProviderStatus($QuiqqerProvider, ProviderHandler::STATUS_SET_UP_DONE);

        $finishMessage = $QuiqqerProvider->finish();

        if ($finishMessage) {
            $this->writeLn($finishMessage, 'green');
        }

        $this->writeLn("QUIQQER installation wizard was successfully executed.", 'green');
    }
}
