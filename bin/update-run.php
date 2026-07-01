<?php

/**
 * Web entrypoint for prepared QUIQQER update runs.
 */

declare(strict_types=1);

define('QUIQQER_SYSTEM', true);
define('QUIQQER_BACKEND', true);

$cmsDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;

define('CMS_DIR', $cmsDir);
define('ETC_DIR', $cmsDir . 'etc/');

require $cmsDir . 'bootstrap.php';

$id = (string)($_GET['id'] ?? '');
$token = (string)($_GET['token'] ?? '');
$output = (string)($_GET['output'] ?? 'html');
$action = (string)($_GET['action'] ?? 'run');
$root = VAR_DIR . 'update/runs/';

if ($output === 'json') {
    handleJsonRequest($id, $token, $root, $action);
    exit;
}

if ($output === 'sse') {
    handleSseRequest($id, $token, $root);
    exit;
}

renderHtmlConsole($id, $token);

function handleJsonRequest(string $id, string $token, string $root, string $action): void
{
    if ($action === 'run') {
        $entrypoint = new QUI\System\Update\RunEntrypoint();
        ob_start();

        $exitCode = $entrypoint->execute(
            $id,
            $root,
            QUI\System\Update\DefaultRunActions::create(),
            [
                'token' => $token,
                'foreground' => '1',
                'yes' => '1'
            ]
        );
        $output = (string)ob_get_clean();
        $lines = array_values(array_filter(array_map('trim', explode(PHP_EOL, $output))));
        $payload = [];

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $decoded = json_decode($lines[$i], true);

            if (is_array($decoded)) {
                $payload = $decoded;
                break;
            }
        }

        if (empty($payload)) {
            sendJson([
                'success' => false,
                'error' => trim($output) ?: 'Update runner returned no JSON response.',
                'output' => $output
            ], 500);
            exit;
        }

        $payload['output'] = $output;

        sendJson($payload, $exitCode === 0 ? 200 : 500);
        exit;
    }

    try {
        $Repository = new QUI\System\Update\RunRepository($root);
        $State = $Repository->load($id);
        $State->assertToken($token);

        sendJson([
            'success' => true,
            'id' => $State->getId(),
            'phase' => $State->getPhase(),
            'status' => $State->getStatus(),
            'state' => $State->toArray(),
            'log' => readRunLog($root, $id)
        ]);
    } catch (Throwable $Exception) {
        sendJson([
            'success' => false,
            'error' => $Exception->getMessage()
        ], 500);
    }
}

function sendJson(array $payload, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }

    echo json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function handleSseRequest(string $id, string $token, string $root): void
{
    if (!headers_sent()) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Accel-Buffering: no');
    }

    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', '0');

    if (function_exists('session_write_close')) {
        session_write_close();
    }

    while (ob_get_level() > 0) {
        ob_end_flush();
    }

    $lastLog = '';

    for ($i = 0; $i < 1800; $i++) {
        try {
            $Repository = new QUI\System\Update\RunRepository($root);
            $State = $Repository->load($id);
            $State->assertToken($token);
            $log = readRunLog($root, $id);

            if ($log !== $lastLog) {
                sendSseEvent('log', [
                    'append' => substr($log, strlen($lastLog))
                ]);

                $lastLog = $log;
            }

            sendSseEvent('status', [
                'success' => true,
                'id' => $State->getId(),
                'phase' => $State->getPhase(),
                'status' => $State->getStatus(),
                'state' => $State->toArray()
            ]);

            if (in_array($State->getStatus(), ['finished', 'failed', 'cancelled'], true)) {
                sendSseEvent('close', [
                    'status' => $State->getStatus(),
                    'state' => $State->toArray()
                ]);
                return;
            }
        } catch (Throwable $Exception) {
            sendSseEvent('error', [
                'success' => false,
                'error' => $Exception->getMessage()
            ]);
            return;
        }

        sleep(1);
    }
}

function sendSseEvent(string $event, array $payload): void
{
    echo 'event: ' . $event . "\n";
    echo 'data: ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n\n";
    flush();
}

function readRunLog(string $root, string $id): string
{
    try {
        QUI\System\Update\RunState::assertValidIdentifier($id);
    } catch (Throwable) {
        return '';
    }

    $file = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'runner.log';

    if (!is_file($file)) {
        return '';
    }

    $content = file_get_contents($file);

    if ($content === false) {
        return '';
    }

    return cleanRunLog($content);
}

function cleanRunLog(string $content): string
{
    $content = preg_replace('/\x1b\[[0-9;]*m/', '', $content) ?? $content;
    $jsonPosition = strpos($content, '{"success":');

    if ($jsonPosition !== false) {
        $content = substr($content, 0, $jsonPosition);
    }

    $lines = preg_split('/\R/', $content);

    if (!is_array($lines)) {
        return rtrim($content);
    }

    foreach ($lines as $index => $line) {
        $trimmed = trim($line);

        if (!str_starts_with($trimmed, '[!!]')) {
            continue;
        }

        $withoutPrefix = trim(substr($trimmed, 4));

        if (preg_match('/^\[\d+\/\d+\]/', $withoutPrefix) || str_starts_with($withoutPrefix, '[..]')) {
            $lines[$index] = $withoutPrefix;
        }
    }

    return rtrim(implode(PHP_EOL, $lines));
}

function renderHtmlConsole(string $id, string $token): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
    }

    $encodedId = json_encode($id, JSON_UNESCAPED_SLASHES);
    $encodedToken = json_encode($token, JSON_UNESCAPED_SLASHES);
    $title = htmlspecialchars('QUIQQER Update', ENT_QUOTES, 'UTF-8');
    $logo = htmlspecialchars(URL_BIN_DIR . 'quiqqer_logo.svg', ENT_QUOTES, 'UTF-8');

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #050817;
            --panel: #0c1328;
            --line: #26314f;
            --text: #ecf3ff;
            --muted: #9aa9c5;
            --ok: #36d17c;
            --warn: #f0b429;
            --err: #ff5c6c;
            --info: #38bdf8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font: 14px/1.5 Consolas, Monaco, "Liberation Mono", monospace;
        }

        .wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 28px;
        }

        header {
            align-items: center;
            border-bottom: 1px solid var(--line);
            display: flex;
            gap: 18px;
            justify-content: space-between;
            margin-bottom: 22px;
            padding-bottom: 18px;
        }

        h1 {
            margin: 0;
        }

        .logo {
            display: block;
            height: 34px;
            width: auto;
        }

        .status {
            align-items: center;
            color: var(--muted);
            display: flex;
            font-size: 13px;
            gap: 10px;
        }

        .pulse {
            animation: pulse 1s ease-in-out infinite;
            background: var(--info);
            border-radius: 999px;
            box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.45);
            display: inline-block;
            height: 9px;
            width: 9px;
        }

        .pulse.stopped {
            animation: none;
            background: var(--muted);
            box-shadow: none;
        }

        .cursor::after {
            animation: blink 1s steps(2, start) infinite;
            content: "█";
            margin-left: 5px;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.45);
            }

            70% {
                box-shadow: 0 0 0 9px rgba(56, 189, 248, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(56, 189, 248, 0);
            }
        }

        @keyframes blink {
            0%, 45% {
                opacity: 1;
            }

            46%, 100% {
                opacity: 0;
            }
        }

        .console {
            background: var(--panel);
            border: 1px solid var(--line);
            height: min(72vh, 760px);
            overflow: auto;
            padding: 20px;
            white-space: pre-wrap;
        }

        .line {
            display: block;
        }

        .muted {
            color: var(--muted);
        }

        .ok {
            color: var(--ok);
        }

        .warn {
            color: var(--warn);
        }

        .err {
            color: var(--err);
        }

        .info {
            color: var(--info);
        }
    </style>
</head>
<body>
<div class="wrap">
    <header>
        <h1><img alt="QUIQQER" class="logo" src="{$logo}"></h1>
        <div class="status"><span class="pulse" id="pulse"></span><span id="status">Preparing</span></div>
    </header>
    <main class="console" id="console"></main>
</div>
<script>
const runId = {$encodedId};
const token = {$encodedToken};
const consoleNode = document.getElementById('console');
const statusNode = document.getElementById('status');
const pulseNode = document.getElementById('pulse');
let lastLog = '';
let stopped = false;
let liveOutput = false;
let continuedAfterRefresh = false;
let cursorNode = null;
let runInFlight = false;
let restartRunRequested = false;
let finalStateWritten = false;

function endpoint(action) {
    const url = new URL(window.location.href);
    url.searchParams.set('output', 'json');
    url.searchParams.set('action', action);
    url.searchParams.set('id', runId);
    url.searchParams.set('token', token);
    return url.toString();
}

function sseEndpoint() {
    const url = new URL(window.location.href);
    url.searchParams.set('output', 'sse');
    url.searchParams.set('id', runId);
    url.searchParams.set('token', token);
    return url.toString();
}

function write(text, type) {
    removeCursor();
    text = cleanConsoleText(text);

    if (!text || text.trim() === '') {
        showCursor();
        return;
    }

    const node = document.createElement('span');
    node.className = 'line ' + (type || '');
    node.textContent = text;
    consoleNode.appendChild(node);
    showCursor();
    consoleNode.scrollTop = consoleNode.scrollHeight;
}

function cleanConsoleText(text) {
    text = String(text || '');
    text = removeAnsiSequences(text, String.fromCharCode(27));
    text = removeAnsiSequences(text, '␛');

    const jsonPosition = text.indexOf('{"success":');

    if (jsonPosition !== -1) {
        text = text.substring(0, jsonPosition);
    }

    return trimRightText(text);
}

function removeAnsiSequences(text, marker) {
    let result = '';

    for (let i = 0; i < text.length; i++) {
        if (text.charAt(i) !== marker || text.charAt(i + 1) !== '[') {
            result += text.charAt(i);
            continue;
        }

        i += 2;

        while (i < text.length && text.charAt(i) !== 'm') {
            i++;
        }
    }

    return result;
}

function trimRightText(text) {
    while (text.length > 0 && text.charAt(text.length - 1).trim() === '') {
        text = text.substring(0, text.length - 1);
    }

    return text;
}

function writeFinalState(state) {
    if (finalStateWritten) {
        return;
    }

    finalStateWritten = true;
    const finished = state.status === 'finished';

    write(finished ? '[OK] Update finished' : '[!!] Update ' + state.status, finished ? 'ok' : 'err');

    if (!finished && state.errorMessage) {
        write('[!!] ' + state.errorMessage, 'err');
    }
}

function writeLogText(text) {
    text = cleanConsoleText(text);

    if (!text || text.trim() === '') {
        return;
    }

    const lineBreak = String.fromCharCode(10);
    text = text.split(String.fromCharCode(13) + lineBreak).join(lineBreak);
    text = text.split(String.fromCharCode(13)).join(lineBreak);

    const lines = text.split(lineBreak);

    lines.forEach(function (line) {
        line = normalizeLogLine(line);

        if (!line || line.trim() === '') {
            write('', 'muted');
            return;
        }

        write(line, getLogLineType(line));
    });
}

function normalizeLogLine(line) {
    line = String(line || '');

    const trimmed = line.trim();
    line = trimmed;

    if (trimmed.indexOf('[!!]') === 0) {
        const withoutErrorPrefix = trimmed.substring(4).trim();

        if (isSectionLine(withoutErrorPrefix) || withoutErrorPrefix.indexOf('[..]') === 0) {
            return withoutErrorPrefix;
        }
    }

    if (trimmed === '\\n"}' || trimmed === '\\n}' || trimmed === '\\n') {
        return '';
    }

    return line;
}

function getLogLineType(line) {
    const trimmed = line.trim();

    if (isSectionLine(trimmed)) {
        return 'info';
    }

    if (trimmed.indexOf('[OK]') !== -1) {
        return 'ok';
    }

    if (trimmed.indexOf('[?]') !== -1) {
        return 'warn';
    }

    if (trimmed.indexOf('The update has found inconsistencies') !== -1) {
        return 'warn';
    }

    if (trimmed.indexOf('[!!]') !== -1 || trimmed.indexOf('[error]') !== -1) {
        return 'err';
    }

    if (trimmed.indexOf('[..]') !== -1) {
        return 'muted';
    }

    return 'muted';
}

function isSectionLine(line) {
    if (line.charAt(0) !== '[') {
        return false;
    }

    const end = line.indexOf(']');

    if (end === -1) {
        return false;
    }

    const parts = line.substring(1, end).split('/');

    return parts.length === 2 && !isNaN(parseInt(parts[0], 10)) && !isNaN(parseInt(parts[1], 10));
}

function showCursor() {
    if (stopped || cursorNode) {
        return;
    }

    cursorNode = document.createElement('span');
    cursorNode.className = 'line muted cursor';
    cursorNode.textContent = '  [..] Waiting for update output';
    consoleNode.appendChild(cursorNode);
}

function removeCursor() {
    if (!cursorNode) {
        return;
    }

    cursorNode.remove();
    cursorNode = null;
}

function stopActivity() {
    stopped = true;
    removeCursor();
    pulseNode.classList.add('stopped');
}

async function request(action) {
    const response = await fetch(endpoint(action), {
        cache: 'no-store',
        credentials: 'same-origin'
    });
    const text = await response.text();
    let data;

    try {
        data = JSON.parse(text);
    } catch (error) {
        throw new Error(text.trim() || error.message);
    }

    if (!response.ok || data.success === false) {
        const requestError = new Error(data.error || 'Update request failed');
        requestError.runnerOutput = data.output || '';
        requestError.state = data.state || data;

        throw requestError;
    }

    return data;
}

function writeRequestError(error) {
    if (error.runnerOutput) {
        writeLogText(error.runnerOutput);
    }

    if (error.message && (error.message.indexOf(String.fromCharCode(10)) !== -1 ||
        error.message.indexOf(String.fromCharCode(13)) !== -1)) {
        writeLogText(error.message);
    }

    if (error.state && finalState(error.state.status)) {
        writeFinalState(error.state);
        return;
    }

    if (error.message && error.message.indexOf(String.fromCharCode(10)) === -1 &&
        error.message.indexOf(String.fromCharCode(13)) === -1) {
        write('[!!] ' + error.message, 'err');
    }
}

function renderLog(log) {
    if (!log || log === lastLog) {
        return;
    }

    const next = log.substring(lastLog.length);
    lastLog = log;

    if (next.trim() !== '') {
        writeLogText(next);
    }
}

function appendLog(text) {
    if (!text || text.trim() === '') {
        return;
    }

    lastLog += text;
    writeLogText(text);
}

function finalState(status) {
    return ['finished', 'failed', 'cancelled'].indexOf(status) !== -1;
}

function writeContinueMessage() {
    if (continuedAfterRefresh) {
        return;
    }

    continuedAfterRefresh = true;
    write('[..] Continuing update after Composer refresh', 'info');
}

async function poll() {
    if (stopped) {
        return;
    }

    try {
        const data = await request('status');
        const state = data.state || data;
        statusNode.textContent = state.status + ' / ' + state.phase;
        renderLog(data.log || '');

        if (state.status === 'restart_required') {
            writeContinueMessage();
            run();
            return;
        }

        if (finalState(state.status)) {
            stopActivity();
            writeFinalState(state);
            return;
        }

        window.setTimeout(poll, 1500);
    } catch (error) {
        stopActivity();
        statusNode.textContent = 'failed';
        writeRequestError(error);
    }
}

function startSse() {
    if (!window.EventSource) {
        return false;
    }

    const source = new EventSource(sseEndpoint());
    let opened = false;

    source.onopen = function () {
        opened = true;
        write('[..] Live output connected', 'info');
    };

    source.addEventListener('log', function (event) {
        const data = JSON.parse(event.data);
        appendLog(data.append || '');
    });

    source.addEventListener('status', function (event) {
        const data = JSON.parse(event.data);
        const state = data.state || data;
        statusNode.textContent = state.status + ' / ' + state.phase;

        if (state.status === 'restart_required') {
            writeContinueMessage();
            requestRestartRun();
        }

        if (finalState(state.status)) {
            stopActivity();
            source.close();
            writeFinalState(state);
        }
    });

    source.addEventListener('close', function (event) {
        const data = JSON.parse(event.data);
        const state = data.state || data;
        stopActivity();
        source.close();
        writeFinalState(state);
    });

    source.addEventListener('error', function (event) {
        if (stopped) {
            source.close();
            return;
        }

        source.close();

        if (!opened) {
            write('[..] Live output unavailable, using polling', 'warn');
        }

        window.setTimeout(poll, 1000);
    });

    return true;
}

async function run() {
    if (runInFlight || stopped) {
        return;
    }

    runInFlight = true;

    try {
        const data = await request('run');
        statusNode.textContent = data.status + ' / ' + data.phase;
        write('[..] ' + (data.message || 'Update runner processed step'), 'info');

        if (data.status === 'restart_required') {
            writeContinueMessage();
            requestRestartRun();
            return;
        }

        if (finalState(data.status)) {
            stopActivity();
            writeFinalState(data);
            return;
        }

        if (!liveOutput) {
            window.setTimeout(poll, 1000);
        }
    } catch (error) {
        stopActivity();
        statusNode.textContent = 'failed';
        writeRequestError(error);
    } finally {
        runInFlight = false;
    }
}

function requestRestartRun() {
    if (restartRunRequested) {
        return;
    }

    restartRunRequested = true;

    window.setTimeout(function () {
        restartRunRequested = false;
        run();
    }, 800);
}

write('[1/6] Preparing update', 'info');
write('  [..] Run ID: ' + runId, 'muted');

liveOutput = startSse();
run().then(function () {
    if (!liveOutput && !stopped) {
        poll();
    }
});
</script>
</body>
</html>
HTML;
}
