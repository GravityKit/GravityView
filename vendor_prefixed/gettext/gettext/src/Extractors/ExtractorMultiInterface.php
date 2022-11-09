<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 09-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Gettext\Extractors;

use GravityKit\GravityView\Gettext\Translations;

interface ExtractorMultiInterface
{
    /**
     * Parses a string and append the translations found in the Translations instance.
     * Allows scanning for multiple domains at a time (each Translation has to have a different domain)
     *
     * @param string $string
     * @param Translations[] $translations
     * @param array $options
     */
    public static function fromStringMultiple($string, array $translations, array $options = []);

    /**
     * Parses a string and append the translations found in the Translations instance.
     * Allows scanning for multiple domains at a time (each Translation has to have a different domain)
     *
     * @param $file
     * @param Translations[] $translations
     * @param array $options
     */
    public static function fromFileMultiple($file, array $translations, array $options = []);
}
