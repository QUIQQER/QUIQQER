<?php

/**
 * This file contains \QUI\Interfaces\Template\EngineInterface
 */

namespace QUI\Interfaces\Template;

use QUI\Locale;
use QUI\Projects\Site\Canonical;

/**
 * Interface of a template engine
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
interface EngineInterface
{
    /**
     * Return the complete template
     *
     * @param string $template - path to the template
     *
     * @return string
     */
    public function fetch(string $template): string;

    /**
     * Assign a Variable to the engine
     */
    public function assign(array|string $var, mixed $value = false);

    /**
     * Return the value of a template variable
     */
    public function getTemplateVariable(string $variableName): mixed;

    /**
     * Return the current template canonical object
     */
    public function getCanonical(): Canonical;

    /**
     * Set a locale object to the engine
     */
    public function setLocale(Locale $Locale): void;

    /**
     * Return the engine locale object
     */
    public function getLocale(): ?Locale;
}
