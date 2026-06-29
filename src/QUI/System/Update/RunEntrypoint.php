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
        if ($sapi !== 'cli' && !headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}
