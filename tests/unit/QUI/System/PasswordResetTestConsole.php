<?php

declare(strict_types=1);

namespace QUITests\QUI\System;

use QUI\System\Console;

final class PasswordResetTestConsole extends Console
{
    /** @var list<string> */
    private array $inputs;

    /** @var list<string> */
    public array $output = [];

    private ?string $generatedPassword;

    /**
     * @param list<string> $inputs
     */
    public function __construct(array $inputs, ?string $generatedPassword = null)
    {
        $this->inputs = $inputs;
        $this->generatedPassword = $generatedPassword;
    }

    public function runPasswordReset(): int
    {
        return $this->passwordReset();
    }

    public function readInput(): string
    {
        return array_shift($this->inputs) ?? '';
    }

    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
    {
        $this->output[] = $msg;
    }

    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
    {
        $this->output[] = $msg;
    }

    public function clearMsg(): void
    {
    }

    protected function createPasswordResetPassword(): string
    {
        return $this->generatedPassword ?? parent::createPasswordResetPassword();
    }
}
