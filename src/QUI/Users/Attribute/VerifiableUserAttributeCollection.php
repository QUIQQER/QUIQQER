<?php

namespace QUI\Users\Attribute;

use QUI\Users\Attribute\Verifiable\AddressAttribute;
use QUI\Users\Attribute\Verifiable\MailAttribute;
use QUI\Users\Attribute\Verifiable\PhoneNumberAttribute;
use QUI\Users\Attribute\Verifiable\VerifiableUserAttributeInterface;
use Ramsey\Collection\AbstractCollection;

class VerifiableUserAttributeCollection extends AbstractCollection
{
    /**
     * @return class-string<VerifiableUserAttributeInterface>
     */
    #[\Override]
    public function getType(): string
    {
        return VerifiableUserAttributeInterface::class;
    }

    /**
     * Checks if the collection is not empty and that all attributes are verified
     *
     * @return bool
     */
    public function isFullyVerified(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        if (!$this->getUnverifiedAttributes()->isEmpty()) {
            return false;
        }

        return true;
    }

    public function isAnyVerified(): bool
    {
        foreach ($this as $verifiableUserAttribute) {
            if ($verifiableUserAttribute->getVerificationStatus() === AttributeVerificationStatus::VERIFIED) {
                return true;
            }
        }

        return false;
    }

    public function getVerifiedAttributes(): VerifiableUserAttributeCollection
    {
        /** @var VerifiableUserAttributeCollection<VerifiableUserAttributeInterface> $verifiedAttributes */
        $verifiedAttributes = $this->filter(
            fn(VerifiableUserAttributeInterface $verifiableUserAttribute
            ) => $verifiableUserAttribute->getVerificationStatus() === AttributeVerificationStatus::VERIFIED,
        );

        return $verifiedAttributes;
    }

    public function getUnverifiedAttributes(): VerifiableUserAttributeCollection
    {
        /** @var VerifiableUserAttributeCollection<VerifiableUserAttributeInterface> $unverifiedAttributes */
        $unverifiedAttributes = $this->filter(
            fn(VerifiableUserAttributeInterface $verifiableUserAttribute
            ) => $verifiableUserAttribute->getVerificationStatus() !== AttributeVerificationStatus::VERIFIED,
        );

        return $unverifiedAttributes;
    }

    public function getMails(): VerifiableUserAttributeCollection
    {
        /** @var VerifiableUserAttributeCollection<VerifiableUserAttributeInterface> $unverifiedAttributes */
        $unverifiedAttributes = $this->filter(
            fn(VerifiableUserAttributeInterface $verifiableUserAttribute
            ) => $verifiableUserAttribute instanceof MailAttribute
        );

        return $unverifiedAttributes;
    }

    public function getAddresses(): VerifiableUserAttributeCollection
    {
        /** @var VerifiableUserAttributeCollection<VerifiableUserAttributeInterface> $unverifiedAttributes */
        $unverifiedAttributes = $this->filter(
            fn(VerifiableUserAttributeInterface $verifiableUserAttribute
            ) => $verifiableUserAttribute instanceof AddressAttribute
        );

        return $unverifiedAttributes;
    }

    public function getPhoneNumbers(): VerifiableUserAttributeCollection
    {
        /** @var VerifiableUserAttributeCollection<VerifiableUserAttributeInterface> $unverifiedAttributes */
        $unverifiedAttributes = $this->filter(
            fn(VerifiableUserAttributeInterface $verifiableUserAttribute
            ) => $verifiableUserAttribute instanceof PhoneNumberAttribute
        );

        return $unverifiedAttributes;
    }
}
