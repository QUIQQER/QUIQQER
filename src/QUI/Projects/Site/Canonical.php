<?php

/**
 * This file contains \QUI\Projects\Site\Canonical
 */

namespace QUI\Projects\Site;

use QUI;
use QUI\Exception;

use function ltrim;
use function strtolower;
use function trim;

use const URL_DIR;

/**
 * Canonical meta helper
 */
class Canonical
{
    protected bool $considerGetParams = true;

    public function __construct(
        protected QUI\Interfaces\Projects\Site $Site
    ) {
    }

    /**
     * Return the meta tag, if it is allowed
     */
    public function output(): string
    {
        if ($this->isNoIndex()) {
            return '';
        }

        $canonical = $this->buildCanonicalUrl();

        if ($canonical === '') {
            return '';
        }

        return $this->getLinkRel($canonical);
    }

    /**
     * Build the canonical target as an absolute URL.
     */
    protected function buildCanonicalUrl(): string
    {
        $Project = $this->Site->getProject();
        $canonical = trim($this->Site->getCanonical());
        $metaCanonical = $this->Site->getAttribute('meta.canonical');

        if ($metaCanonical && !QUI\Projects\Site\Utils::isSiteLink($metaCanonical)) {
            return filter_var($metaCanonical, FILTER_VALIDATE_URL) ? $metaCanonical : '';
        }

        if (QUI\Projects\Site\Utils::isSiteLink($canonical)) {
            try {
                $CanonicalSite = QUI\Projects\Site\Utils::getSiteByLink($canonical);
                $canonical = $CanonicalSite->getUrlRewritten();
            } catch (\Exception) {
                return '';
            }
        }

        if ($this->isAbsoluteUrl($canonical)) {
            return $canonical;
        }

        $canonical = ltrim($canonical, '/');
        $installationPath = trim(URL_DIR, '/');
        $languagePath = $Project->getVHostPath();

        if (
            $installationPath !== ''
            && (
                $canonical === $installationPath
                || str_starts_with($canonical, $installationPath . '/')
            )
        ) {
            $canonical = ltrim(substr($canonical, strlen($installationPath)), '/');
        }

        if ($languagePath !== '') {
            if ($canonical === $languagePath) {
                $canonical = '';
            } elseif (str_starts_with($canonical, $languagePath . '/')) {
                $canonical = ltrim(substr($canonical, strlen($languagePath)), '/');
            }
        }

        return $Project->getVHostBaseUrl() . $canonical;
    }

    /**
     * Determine whether the current page should be excluded from canonical output.
     */
    protected function isNoIndex(): bool
    {
        $robots = strtolower((string)$this->Site->getAttribute('meta.robots'));

        if (str_contains($robots, 'noindex')) {
            return true;
        }

        return QUI::getLocale()->no_translation;
    }

    /**
     * Determine whether a canonical value is already absolute.
     */
    protected function isAbsoluteUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    /**
     * Return <link rel="canonical"> tag
     */
    protected function getLinkRel(string $url): string
    {
        return '<link rel="canonical" href="' . $url . '" />';
    }

    /**
     * Consider get Parameter at the canonical request check
     */
    public function considerGetParameterOn(): void
    {
        $this->considerGetParams = true;
    }

    /**
     * Get parameters are not considered at the request check
     */
    public function considerGetParameterOff(): void
    {
        $this->considerGetParams = false;
    }
}
