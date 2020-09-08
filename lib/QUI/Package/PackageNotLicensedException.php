<?php

namespace QUI\Package;

use QUI;

/**
 * Class PackageNotLicensedException
 *
 * Is thrown when a package license is required but not existing.
 */
class PackageNotLicensedException extends \QUI\Exception
{
    /**
     * @var int
     */
    protected $code = Manager::EXCEPTION_CODE_PACKAGE_NOT_LICENSED;

    /**
     * Constructor
     *
     * @param string $package - The concerned package
     * @param string|array $message (optional) - If omitted, use default message
     * @param string $url (optional) - Package download URL
     */
    public function __construct(string $package, $message = null, string $url = null)
    {
        if (empty($message)) {
            $message = QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'PackageNotLicensedException.message',
                [
                    'package' => $package
                ]
            );
        }

        parent::__construct($message, $this->code, []);

        $this->setAttribute('package', $package);
        $this->setAttribute('url', $url);
    }
}
