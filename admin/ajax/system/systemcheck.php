<?php

/**
 * Return the system check
 * Only for SuperUsers
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_systemcheck',
    function () {

        if (!isset($_REQUEST['lang'])) {
            $_REQUEST['lang'] = 'en';
        }

        $lang = substr($_REQUEST['lang'], 0, 2);

        $Requirements = new \QUI\Requirements\Requirements($lang);

        $allTests = $Requirements->getAllTests();

        $html = '<div class="check-table">';

        /** @var \QUI\Requirements\Tests\Test $Test */
        foreach ($allTests as $category => $Tests) {

            $html .= '<div class="system-check check-table-row">';
            $html .= '<div class="check-table-col check-table-col-test">';
            $html .= $category;
            $html .= '</div>';
            $html .= '<div class="check-table-col check-table-col-message">';
            $html .= '<ul>';
            foreach ($Tests as $Test) {
                $Result           = $Test->getResult();
                $testMessageClass = 'test-message';

                // extra class for checksum
                if ($Test->getIdentifier() == 'quiqqer.checksums') {
                    $testMessageClass .= ' test-message-checkSum';
                }

                switch ($Result->getStatus()) {
                    case \QUI\Requirements\TestResult::STATUS_OPTIONAL:
                    case \QUI\Requirements\TestResult::STATUS_OK:
                        $html .= '<li><span class="fa fa-check" title="';
                        break;

                    case \QUI\Requirements\TestResult::STATUS_FAILED:
                        $html .= '<li class="failed"><span class="fa fa-remove" title="';
                        break;

                    case \QUI\Requirements\TestResult::STATUS_UNKNOWN:
                    case \QUI\Requirements\TestResult::STATUS_WARNING:
                        $html .= '<li><span class="fa fa-exclamation-circle" title="';
                        break;
                }

                $html .= $Result->getStatusHumanReadable() . '"></span>';
                $html .= '<span class="test-name">' . $Test->getName() . '</span>';
                $html .= '<div class="' . $testMessageClass . '">';
                $html .= $Result->getMessage();
                $html .= '</div>';
            }
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    },
    false,
    'Permission::checkSU'
);
