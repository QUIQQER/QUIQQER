<?php

/**
 * \QUI\System\Console\Tools\Tests
 */

namespace QUI\System\Console\Tools;

use QUI;
use QUI\Exception;
use QUI\Interfaces\System\Test;

use function class_exists;
use function count;
use function error_get_last;
use function str_replace;

/**
 * Checks the system and execute the system tests
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Tests extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('quiqqer:tests')
            ->setDescription('Execute system tests');
    }

    /**
     * (non-PHPdoc)
     *
     * @throws Exception
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        QUI::getErrorHandler()->registerShutdown(function (): void {
            $last_error = error_get_last();

            if ($last_error && $last_error['type'] === E_ERROR) {
                $this->writeLn();

                $this->writeLn(
                    $last_error['message'] . ' at line ' . $last_error['line'] . ' :: ' . $last_error['file'],
                    'red'
                );

                $this->writeLn();
            }
        });

        // read tests
        $testDir = LIB_DIR . 'QUI/System/Tests/';
        $tests = QUI\Utils\System\File::readDir($testDir);
        $list = [];

        foreach ($tests as $testFile) {
            $cls = 'QUI/System/Tests/' . str_replace('.php', '', $testFile);
            $cls = str_replace('/', '\\', $cls);

            if (!class_exists($cls)) {
                require $testDir . $testFile;
            }

            if (!class_exists($cls)) {
                continue;
            }

            $Test = new $cls();

            if (!($Test instanceof Test)) {
                continue;
            }

            $list[] = $Test;
        }

        $this->writeLn('Execute Tests: ' . count($list));
        $this->writeLn('=================================');

        $failed = 0;

        foreach ($list as $Test) {
            try {
                $result = $Test->execute();
            } catch (\Exception) {
                $result = QUI\System\Test::STATUS_ERROR;
            }

            $message = '[ OK ] ';
            $color = 'green';

            if ($result == QUI\System\Test::STATUS_ERROR) {
                $message = '[ -- ] ';
                $color = 'red';

                if ($Test->isOptional()) {
                    $color = 'purple';
                }

                $failed++;
            }

            $message .= $Test->getTitle();

            $this->writeLn($message, $color);
            $this->resetColor();
        }

        if ($failed) {
            $this->writeLn();

            $this->writeLn('Some tests are failed!!');
            $this->writeLn(
                'Please check the failed tests, QUIQQER may not function properly under some circumstances.'
            );
            $this->writeLn();
        }


        $this->writeLn();
    }
}
