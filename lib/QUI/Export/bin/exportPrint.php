<?php

$dir = str_replace('quiqqer/quiqqer/lib/QUI/Export/bin', '', __DIR__);

require_once $dir . 'header.php';

try {
    QUI\Permissions\Permission::checkAdminUser(QUI::getUserBySession());
} catch (QUI\Exception $Exception) {
    QUI::getGlobalResponse()->setStatusCode($Exception->getMessage());
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

// header
$header = [];

foreach ($body['data']['header'] as $key => $entry) {
    $header[] = html_entity_decode($entry['header']);
}

// data
$data = [];

foreach ($body['data']['data'] as $key => $entry) {
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


$Smarty = QUI::getTemplateManager()->getEngine();

$Smarty->assign([
    'header' => $header,
    'data' => $data
]);

$output = $Smarty->fetch(dirname(__FILE__) . '/exportPrint.html');


// name
$name = 'export';

if (!empty($body['name'])) {
    $name = $body['name'];
}

$filename = $name . '.html';

header("Content-Type: text/html; charset=utf-16");
header('Content-Transfer-Encoding: binary');
header("Content-Disposition: attachment; filename=\"$filename\"");

echo $output;
