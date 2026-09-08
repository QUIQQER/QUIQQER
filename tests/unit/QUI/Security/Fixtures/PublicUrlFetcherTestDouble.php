<?php

namespace QUI\Security;

use QUI\Exception;

class PublicUrlFetcherTestDouble extends PublicUrlFetcher
{
    /** @var array<string, list<string>> */
    private array $resolvedHosts;

    /** @var list<array{status: int, location: string|null, body: string}> */
    private array $responses;

    /**
     * @var list<array{url: string, host: string, port: int, addresses: non-empty-list<string>}>
     */
    public array $requests = [];

    /**
     * @param array<string, list<string>> $resolvedHosts
     * @param list<array{status: int, location: string|null, body: string}> $responses
     */
    public function __construct(array $resolvedHosts = [], array $responses = [])
    {
        $this->resolvedHosts = $resolvedHosts;
        $this->responses = $responses;
    }

    protected function resolveHost(string $host): array
    {
        return $this->resolvedHosts[$host] ?? parent::resolveHost($host);
    }

    protected function request(string $url, string $host, int $port, array $addresses): array
    {
        $this->requests[] = [
            'url' => $url,
            'host' => $host,
            'port' => $port,
            'addresses' => $addresses
        ];

        if ($this->responses === []) {
            throw new Exception('No test response configured.');
        }

        return array_shift($this->responses);
    }
}
