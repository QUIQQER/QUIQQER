<?php

namespace Mcp\Server;

if (!class_exists(Builder::class)) {
    class Builder
    {
        /**
         * @var array<int, array{
         *     callback: callable,
         *     name: string,
         *     description: string,
         *     inputSchema: array<string, mixed>|null
         * }>
         */
        private array $tools = [];

        /**
         * @param callable $callback
         * @param array<string, mixed>|null $inputSchema
         */
        public function addTool(
            callable $callback,
            string $name,
            string $description,
            ?array $inputSchema = null
        ): void {
            $this->tools[] = compact(
                'callback',
                'name',
                'description',
                'inputSchema'
            );
        }
    }
}
