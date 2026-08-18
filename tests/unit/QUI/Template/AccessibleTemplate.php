<?php

declare(strict_types=1);

namespace QUITests\Template;

use QUI\Interfaces\Projects\Site;
use QUI\Template;

class AccessibleTemplate extends Template
{
    public function initializeJsonLd(Site $Site): void
    {
        $this->jsonLd = $this->createDefaultJsonLd($Site);
    }
}
