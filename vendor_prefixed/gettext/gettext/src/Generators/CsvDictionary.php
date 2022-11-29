<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 28-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Gettext\Generators;

use GravityKit\GravityView\Gettext\Translations;
use GravityKit\GravityView\Gettext\Utils\DictionaryTrait;
use GravityKit\GravityView\Gettext\Utils\CsvTrait;

class CsvDictionary extends Generator implements GeneratorInterface
{
    use DictionaryTrait;
    use CsvTrait;

    public static $options = [
        'includeHeaders' => false,
        'delimiter' => ",",
        'enclosure' => '"',
        'escape_char' => "\\"
    ];

    /**
     * {@parentDoc}.
     */
    public static function toString(Translations $translations, array $options = [])
    {
        $options += static::$options;
        $handle = fopen('php://memory', 'w');

        foreach (static::toArray($translations, $options['includeHeaders']) as $original => $translation) {
            static::fputcsv($handle, [$original, $translation], $options);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
