<?php

namespace QUI\Users\Attribute\Verifiable;

use QUI\Users\Attribute\AttributeVerificationStatus;

use function array_key_exists;

abstract class AbstractVerifiableUserAttribute implements VerifiableUserAttributeInterface
{
    /**
     * @var array<string|int,mixed>
     */
    protected array $customData = [];

    public function __construct(
        protected readonly string $uuid,
        protected readonly string $value,
        protected AttributeVerificationStatus $verificationStatus
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getVerificationStatus(): AttributeVerificationStatus
    {
        return $this->verificationStatus;
    }

    public function setVerificationStatus(AttributeVerificationStatus $verificationStatus): void
    {
        $this->verificationStatus = $verificationStatus;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param array<string|int,mixed> $customData
     * @return void
     */
    public function setCustomData(array $customData): void
    {
        $this->customData = $customData;
    }

    /**
     * @return array<string|int,mixed>
     */
    public function getCustomData(): array
    {
        return $this->customData;
    }

    public function setCustomDataEntry(string $key, mixed $value): void
    {
        $this->customData[$key] = $value;
    }

    public function getCustomDataEntry(string $key): mixed
    {
        if (array_key_exists($key, $this->customData)) {
            return $this->customData[$key];
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'value' => $this->getValue(),
            'verification_status' => $this->getVerificationStatus()->value,
            'custom_data' => $this->getCustomData(),
            'type' => $this::class
        ];
    }

    public function verify()
    {
        // TODO: Implement verify() method.
    }
}
