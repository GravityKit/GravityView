<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 11-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Gettext\Generators;

use GravityKit\GravityView\Gettext\Translations;
use GravityKit\GravityView\Gettext\Utils\MultidimensionalArrayTrait;
use Symfony\Component\Yaml\Yaml as YamlDumper;

class Yaml extends Generator implements GeneratorInterface
{
    use MultidimensionalArrayTrait;

    public static $options = [
        'includeHeaders' => false,
        'indent' => 2,
        'inline' => 4,
    ];

    /**
     * {@inheritdoc}
     */
    public static function toString(Translations $translations, array $options = [])
    {
        $options += static::$options;

        return YamlDumper::dump(
            static::toArray($translations, $options['includeHeaders']),
            $options['inline'],
            $options['indent']
        );
    }
}
