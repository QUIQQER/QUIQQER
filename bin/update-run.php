<?php

/**
 * Web entrypoint for prepared QUIQQER update runs.
 */

declare(strict_types=1);

define('QUIQQER_SYSTEM', true);

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

        exit($entrypoint->execute(
            $id,
            $root,
            QUI\System\Update\DefaultRunActions::create(),
            [
                'token' => $token
            ]
        ));
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
                    'status' => $State->getStatus()
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

    return $content;
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
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .status {
            color: var(--muted);
            font-size: 13px;
        }

        .console {
            background: var(--panel);
            border: 1px solid var(--line);
            min-height: 70vh;
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
        <h1>QUIQQER Update</h1>
        <div class="status" id="status">Preparing</div>
    </header>
    <main class="console" id="console"></main>
</div>
<script>
const runId = {$encodedId};
const token = {$encodedToken};
const consoleNode = document.getElementById('console');
const statusNode = document.getElementById('status');
let lastLog = '';
let stopped = false;
let liveOutput = false;

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
    const node = document.createElement('span');
    node.className = 'line ' + (type || '');
    node.textContent = text;
    consoleNode.appendChild(node);
    consoleNode.scrollTop = consoleNode.scrollHeight;
}

async function request(action) {
    const response = await fetch(endpoint(action), {
        cache: 'no-store',
        credentials: 'same-origin'
    });
    const data = await response.json();

    if (!response.ok || data.success === false) {
        throw new Error(data.error || 'Update request failed');
    }

    return data;
}

function renderLog(log) {
    if (!log || log === lastLog) {
        return;
    }

    const next = log.substring(lastLog.length);
    lastLog = log;

    if (next.trim() !== '') {
        write(next.replace(/\\s+$/, ''), 'muted');
    }
}

function appendLog(text) {
    if (!text || text.trim() === '') {
        return;
    }

    lastLog += text;
    write(text.replace(/\\s+$/, ''), 'muted');
}

function finalState(status) {
    return ['finished', 'failed', 'cancelled'].indexOf(status) !== -1;
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
            write('[..] Continuing update after Composer refresh', 'info');
            await run();
            return;
        }

        if (finalState(state.status)) {
            stopped = true;
            write(state.status === 'finished' ? '[OK] Update finished' : '[!!] Update ' + state.status,
                state.status === 'finished' ? 'ok' : 'err');
            return;
        }

        window.setTimeout(poll, 1500);
    } catch (error) {
        stopped = true;
        statusNode.textContent = 'failed';
        write('[!!] ' + error.message, 'err');
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
            write('[..] Continuing update after Composer refresh', 'info');
            run();
        }

        if (finalState(state.status)) {
            stopped = true;
            source.close();
            write(state.status === 'finished' ? '[OK] Update finished' : '[!!] Update ' + state.status,
                state.status === 'finished' ? 'ok' : 'err');
        }
    });

    source.addEventListener('close', function (event) {
        const data = JSON.parse(event.data);
        stopped = true;
        source.close();
        write(data.status === 'finished' ? '[OK] Update finished' : '[!!] Update ' + data.status,
            data.status === 'finished' ? 'ok' : 'err');
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
    try {
        const data = await request('run');
        statusNode.textContent = data.status + ' / ' + data.phase;
        write('[..] ' + (data.message || 'Update runner processed step'), 'info');

        if (data.status === 'restart_required') {
            window.setTimeout(run, 800);
            return;
        }

        if (finalState(data.status)) {
            stopped = true;
            write(data.status === 'finished' ? '[OK] Update finished' : '[!!] Update ' + data.status,
                data.status === 'finished' ? 'ok' : 'err');
            return;
        }

        if (!liveOutput) {
            window.setTimeout(poll, 1000);
        }
    } catch (error) {
        stopped = true;
        statusNode.textContent = 'failed';
        write('[!!] ' + error.message, 'err');
    }
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
