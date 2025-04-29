<?php

namespace QUI\Users\Attribute\Verifiable;

use QUI;
use QUI\Exception;
use QUI\Users\Attribute\AttributeVerificationStatus;

final class PhoneNumberAttribute extends AbstractVerifiableUserAttribute
{
    /**
     * @param string $uuid
     * @param string $value
     * @param AttributeVerificationStatus $verificationStatus
     *
     * @throws \libphonenumber\NumberParseException
     * @throws Exception
     */
    public function __construct(string $uuid, string $value, AttributeVerificationStatus $verificationStatus)
    {
        if (!class_exists('QUI\PhoneApi\Entity\PhoneNumber')) {
            throw new Exception('The package "quiqqer/phone-api" is required to use the phone number attribute.');
        }

        $phoneNumber = new QUI\PhoneApi\Entity\PhoneNumber($value);
        parent::__construct($uuid, $phoneNumber->asE164(), $verificationStatus);
    }
}
