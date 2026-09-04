<?php

namespace QUI\Projects\Fixtures;

use QUI\Projects\Media;

class ExternalImageTestDouble extends Media\Image
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params, Media $Media, private readonly string $externalResponse)
    {
        parent::__construct($params, $Media);
    }

    protected function fetchExternalUrl(string $url): string
    {
        return $this->externalResponse;
    }
}
