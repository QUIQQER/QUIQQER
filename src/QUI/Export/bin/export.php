<?php

use QUI\HtmlToPdf\Document;

$dir = str_replace('quiqqer/core/src/QUI/Export/bin', '', __DIR__);
const QUIQQER_SYSTEM = true;
const QUIQQER_AJAX = true;

require_once $dir . 'header.php';

try {
    QUI\Permissions\Permission::checkAdminUser(QUI::getUserBySession());
} catch (QUI\Exception $Exception) {
    QUI::getGlobalResponse()->setStatusCode($Exception->getCode());
    QUI::getGlobalResponse()->setContent(json_encode($Exception->toArray()));
    QUI::getGlobalResponse()->send();
    exit;
}


$body = file_get_contents('php://input');
$body = json_decode($body, true);

if (
    !$body
    || !isset($body['data'])
    || !isset($body['data']['header'])
    || !isset($body['data']['data'])
) {
    exit;
}

if (isset($body['type']) && $body['type'] === 'pdf') {
    ob_start();
    require_once 'exportPrint.php';
    $output = ob_get_clean();

    $Document = new Document();
    $Document->setContentHTML($output);
    $pdfFile = $Document->createPDF();
    $name = 'export';

    if (!empty($body['name'])) {
        $name = $body['name'];
    }

    QUI\Utils\System\File::send($pdfFile, 0, $name . '.pdf');
    exit;
}

if (isset($body['type']) && $body['type'] === 'print') {
    require_once 'exportPrint.php';
    exit;
}

$type = 'csv';
$enclosure = "\x1f";

if (isset($body['type'])) {
    switch ($body['type']) {
        case 'csv':
        case 'json':
        case 'xml':
        case 'xls':
            $type = $body['type'];
            break;
    }
}

// header
$header = [];

foreach ($body['data']['header'] as $key => $entry) {
    $header[] = html_entity_decode($entry['header']);
}

// data
$data = [];

foreach ($body['data']['data'] as $entry) {
    $entry = array_values($entry);

    foreach ($entry as $k => $v) {
        if (is_string($v)) {
            $entry[$k] = $v;
            continue;
        }

        if (is_array($v) && count($v) === 1) {
            $entry[$k] = current($v);
            continue;
        }

        $entry[$k] = json_encode($v);
    }

    $data[] = $entry;
}

// name
$name = 'export';

if (!empty($body['name'])) {
    $name = $body['name'];
}

// export
try {
    $Writer = League\Csv\Writer::createFromFileObject(new SplTempFileObject());
    $Writer->setEndOfLine("\r\n"); //use windows line endings for compatibility with some csv libraries
    //$Writer->setDelimiter(",");
    //$Writer->setEnclosure($enclosure);

    // header
    $Writer->insertOne($header);

    if ($type === 'xml') {
        $filename = $name . '.xml';

        $Writer->insertAll($data);
        $Reader = League\Csv\Reader::createFromString($Writer->toString());
        $Dom = (new League\Csv\XMLConverter())->convert($Reader);
        $output = $Dom->saveXML();
    } elseif ($type === 'json') {
        $filename = $name . '.json';
        $output = json_encode($data);
    } else {
        $filename = $name . '.csv';
        $Writer->insertAll($data);

        $output = $Writer->toString();
        $output = str_replace($enclosure, '', $output);
        $output = iconv('utf-8', 'utf-16//IGNORE', $output);
    }

    header("Content-Type: text/csv; charset=utf-16");
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo $output;
    exit;
} catch (Exception $Exception) {
    QUI\System\Log::writeDebugException($Exception);
}
