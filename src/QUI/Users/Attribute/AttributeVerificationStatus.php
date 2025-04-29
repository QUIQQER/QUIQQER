<?php

namespace QUI\Users\Attribute;

enum AttributeVerificationStatus: string
{
    case VERIFIED = 'VERIFIED';
    case VERIFICATION_IN_PROGRESS = 'VERIFICATION_IN_PROGRESS';
    case UNVERIFIED = 'UNVERIFIED';
}
