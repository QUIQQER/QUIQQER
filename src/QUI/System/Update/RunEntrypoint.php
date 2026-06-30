<?php

namespace QUI\System\Update;

use Throwable;

class RunEntrypoint
{
    /**
     * @param array<string, RunActionInterface> $actions
     * @param array<string, string>|null $query
     * @param array<int, string>|null $argv
     */
    public function execute(
        string $id,
        string $root,
        array $actions,
        ?array $query = null,
        ?array $argv = null,
        ?string $sapi = null,
        ?int $now = null
    ): int {
        $sapi ??= php_sapi_name();
        $query ??= $_GET;
        $argv ??= $_SERVER['argv'] ?? [];

        try {
            $token = $this->getToken($sapi, $query, $argv);
            $repository = new RunRepository($root);
            $processor = new RunProcessor($repository, $actions);
            $state = $processor->process($id, $token, $now);

            $this->sendResponse([
                'success' => true,
                'id' => $state->getId(),
                'phase' => $state->getPhase(),
                'status' => $state->getStatus()
            ], $sapi);

            return 0;
        } catch (Throwable $Exception) {
            $this->sendResponse([
                'success' => false,
                'error' => $Exception->getMessage()
            ], $sapi, 500);

            return 1;
        }
    }

    /**
     * @param array<string, string> $query
     * @param array<int, string> $argv
     */
    private function getToken(string $sapi, array $query, array $argv): string
    {
        if ($sapi === 'cli') {
            return (string)($argv[1] ?? '');
        }

        return (string)($query['token'] ?? '');
    }

    private function sendResponse(array $payload, string $sapi, int $statusCode = 200): void
    {
        if ($sapi === 'cli') {
            $this->sendCliResponse($payload);
            return;
        }

        if ($sapi !== 'cli' && !headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    private function sendCliResponse(array $payload): void
    {
        if (($payload['success'] ?? false) === false) {
            $this->sendCliError((string)($payload['error'] ?? 'Unknown error'));
            return;
        }

        $status = (string)($payload['status'] ?? '');
        $phase = (string)($payload['phase'] ?? '');

        if ($status === RunState::STATUS_RESTART_REQUIRED) {
            echo '[2/6] Composer tool' . PHP_EOL;
            echo '  [OK] Composer updated' . PHP_EOL;
            echo '  [..] Continuing with system update' . PHP_EOL;
            return;
        }

        if ($status === RunState::STATUS_FINISHED) {
            echo '  [OK] Update finished.' . PHP_EOL;
            echo PHP_EOL;
            return;
        }

        echo 'Update status: ' . $status . ' (' . $phase . ')' . PHP_EOL;
    }

    private function sendCliError(string $message): void
    {
        $headline = 'Update failed';
        $lines = [$headline, $message];
        $width = 0;

        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }

        $border = str_repeat(' ', $width + 4);
        $redBackground = "\033[41;37m";
        $reset = "\033[0m";

        echo PHP_EOL;
        echo $redBackground . $border . $reset . PHP_EOL;

        foreach ($lines as $line) {
            echo $redBackground
                . '  '
                . str_pad($line, $width)
                . '  '
                . $reset
                . PHP_EOL;
        }

        echo $redBackground . $border . $reset . PHP_EOL;
    }
}
