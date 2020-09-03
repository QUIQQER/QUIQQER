<?php

$dir = \str_replace('quiqqer/quiqqer/lib/QUI/Export/bin', '', \dirname(__FILE__));
\define('QUIQQER_SYSTEM', true);
\define('QUIQQER_AJAX', true);

require_once $dir.'header.php';

$body = file_get_contents('php://input');
$body = \json_decode($body, true);

if (!$body
    || !isset($body['data'])
    || !isset($body['data']['header'])
    || !isset($body['data']['data'])
) {
    exit;
}

$type      = 'csv';
$enclosure = "\x1f";

// header
$header = [];

foreach ($body['data']['header'] as $key => $entry) {
    $header[] = \html_entity_decode($entry['header']);
}

// data
$data = [];

foreach ($body['data']['data'] as $key => $entry) {
    $entry = \array_values($entry);

    foreach ($entry as $k => $v) {
        if (\is_string($v)) {
            $entry[$k] = $v;
            continue;
        }

        if (\is_array($v) && \count($v) === 1) {
            $entry[$k] = \current($v);
            continue;
        }

        $entry[$k] = \json_encode($v);
    }

    $data[] = $entry;
}

// export
try {
    $Writer = League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
    $Writer->setDelimiter("#");
    $Writer->setNewline("\r\n"); //use windows line endings for compatibility with some csv libraries
    $Writer->setEnclosure($enclosure);

    // header
    $Writer->insertOne($header);

    if ($type === 'xml') {
        $filename = 'export.xml';

        $Writer->insertAll($data);
        $Reader = League\Csv\Reader::createFromString($Writer->getContent());
        $Dom    = (new League\Csv\XMLConverter())->convert($Reader);
        $output = $Dom->saveXML();
    } elseif ($type === 'json') {
        $filename = 'export.json';
        $output   = \json_encode($data);
    } else {
        $filename = 'export.csv';

        $Writer->insertAll($data);

        $output = $Writer->getContent();
        $output = str_replace($enclosure, '', $output);
        $output = iconv('utf-8', 'utf-16//IGNORE', $output);
    }

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
