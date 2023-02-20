<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\ThirdParty\Gettext\Generators;

use GravityKit\GravityView\Foundation\ThirdParty\Gettext\Translations;
use GravityKit\GravityView\Foundation\ThirdParty\Gettext\Utils\HeadersGeneratorTrait;
use GravityKit\GravityView\Foundation\ThirdParty\Gettext\Utils\CsvTrait;

/**
 * Class to export translations to csv.
 */
class Csv extends Generator implements GeneratorInterface
{
    use HeadersGeneratorTrait;
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

        if ($options['includeHeaders']) {
            static::fputcsv($handle, ['', '', static::generateHeaders($translations)], $options);
        }

        foreach ($translations as $translation) {
            if ($translation->isDisabled()) {
                continue;
            }

            $line = [$translation->getContext(), $translation->getOriginal(), $translation->getTranslation()];

            if ($translation->hasPluralTranslations(true)) {
                $line = array_merge($line, $translation->getPluralTranslations());
            }

            static::fputcsv($handle, $line, $options);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
