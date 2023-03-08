<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\ThirdParty\Gettext\Generators;

use GravityKit\GravityView\Foundation\ThirdParty\Gettext\Translations;

abstract class Generator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public static function toFile(Translations $translations, $file, array $options = [])
    {
        $content = static::toString($translations, $options);

        if (file_put_contents($file, $content) === false) {
            return false;
        }

        return true;
    }
}
