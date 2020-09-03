<?php

$dir = \str_replace('quiqqer/quiqqer/lib/QUI/Export/bin', '', \dirname(__FILE__));
\define('QUIQQER_SYSTEM', true);
\define('QUIQQER_AJAX', true);

require_once $dir.'header.php';

$type = 'csv';

try {
    $Writer = League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
    $Writer->setDelimiter("#");
    $Writer->setNewline("\r\n"); //use windows line endings for compatibility with some csv libraries
    $Writer->setEnclosure($enclosure);

    if ($type === 'xml') {
    }

    $output = $Writer->__toString();
    $output = str_replace($enclosure, '', $output);
    $output = iconv('utf-8', 'utf-16//IGNORE', $output);

    $filename = 'export.csv';

    header("Content-Type: text/csv; charset=utf-16");
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo $output;
    exit;
} catch (QUI\Exception $Exception) {
    QUI\System\Log::writeDebugException($Exception);
} catch (\Exception $Exception) {
    QUI\System\Log::writeDebugException($Exception);
}
