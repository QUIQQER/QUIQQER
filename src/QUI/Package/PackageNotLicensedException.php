<?php

namespace QUI\Package;

use QUI;
use QUI\Exception;

/**
 * Class PackageNotLicensedException
 *
 * Is thrown when a package license is required but not existing.
 */
class PackageNotLicensedException extends Exception
{
    /**
     * @var int
     */
    protected $code = Manager::EXCEPTION_CODE_PACKAGE_NOT_LICENSED;

    /**
     * Constructor
     *
     * @param string $package - The concerned package
     * @param string|null $message (optional) - If omitted, use default message
     * @param string|null $url (optional) - Package download URL
     */
    public function __construct(
        string $package,
        null | string $message = null,
        null | string $url = null
    ) {
        if (empty($message)) {
            $message = QUI::getLocale()->get(
                'quiqqer/core',
                'PackageNotLicensedException.message',
                [
                    'package' => $package
                ]
            );
        }

        parent::__construct($message, $this->code);

        $this->setAttribute('package', $package);
        $this->setAttribute('url', $url);
    }
}
