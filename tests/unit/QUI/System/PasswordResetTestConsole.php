<?php

declare(strict_types=1);

namespace QUITests\QUI\System;

use QUI\System\Console;

final class PasswordResetTestConsole extends Console
{
    /** @var list<string> */
    private array $inputs;

    public string $output = '';

    private ?string $generatedPassword;

    private ?string $stdinPassword;

    /**
     * @param list<string> $inputs
     */
    public function __construct(
        array $inputs,
        ?string $generatedPassword = null,
        ?string $stdinPassword = null
    ) {
        $this->inputs = $inputs;
        $this->generatedPassword = $generatedPassword;
        $this->stdinPassword = $stdinPassword;
    }

    public function runPasswordReset(
        ?string $identifier = null,
        bool $noInteraction = false,
        bool $passwordStdin = false
    ): int {
        return $this->passwordReset($identifier, $noInteraction, $passwordStdin);
    }

    public function readInput(): string
    {
        return array_shift($this->inputs) ?? '';
    }

    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
    {
        $this->output .= PHP_EOL . $msg;
    }

    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
    {
        $this->output .= $msg;
    }

    public function clearMsg(): void
    {
    }

    protected function createPasswordResetPassword(): string
    {
        return $this->generatedPassword ?? parent::createPasswordResetPassword();
    }

    protected function readPasswordResetPasswordFromStdin(): ?string
    {
        return $this->stdinPassword;
    }
}
