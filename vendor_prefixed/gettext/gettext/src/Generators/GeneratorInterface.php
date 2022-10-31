<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 31-October-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Gettext\Generators;

use GravityKit\GravityView\Gettext\Translations;

interface GeneratorInterface
{
    /**
     * Saves the translations in a file.
     *
     * @param Translations $translations
     * @param string       $file
     * @param array        $options
     *
     * @return bool
     */
    public static function toFile(Translations $translations, $file, array $options = []);

    /**
     * Generates a string with the translations ready to save in a file.
     *
     * @param Translations $translations
     * @param array        $options
     *
     * @return string
     */
    public static function toString(Translations $translations, array $options = []);
}
