<?php

namespace QUI\Users\Attribute\Verifiable;

use QUI\Users\Attribute\UserAttributeInterface;
use QUI\Users\Attribute\AttributeVerificationStatus;

interface VerifiableUserAttributeInterface extends UserAttributeInterface
{
    public function getUuid(): string;

    public function getVerificationStatus(): AttributeVerificationStatus;

    public function setVerificationStatus(AttributeVerificationStatus $verificationStatus): void;

    public function getValue(): string;

    public function toArray(): array;

    /**
     * @throws \QUI\Exception
     */
    public function verify();

    /**
     * @param array<string|int,mixed> $customData
     * @return void
     */
    public function setCustomData(array $customData): void;

    /**
     * @return array<string|int,mixed>
     */
    public function getCustomData(): array;

    public function setCustomDataEntry(string $key, mixed $value): void;

    public function getCustomDataEntry(string $key): mixed;
}
