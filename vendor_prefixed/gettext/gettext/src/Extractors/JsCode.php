<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 14-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Gettext\Extractors;

use Exception;
use GravityKit\GravityView\Gettext\Translations;
use GravityKit\GravityView\Gettext\Utils\FunctionsScanner;

/**
 * Class to get gettext strings from javascript files.
 */
class JsCode extends Extractor implements ExtractorInterface, ExtractorMultiInterface
{
    public static $options = [
        'constants' => [],

        'functions' => [
            'gettext' => 'gettext',
            '__' => 'gettext',
            'ngettext' => 'ngettext',
            'n__' => 'ngettext',
            'pgettext' => 'pgettext',
            'p__' => 'pgettext',
            'dgettext' => 'dgettext',
            'd__' => 'dgettext',
            'dngettext' => 'dngettext',
            'dn__' => 'dngettext',
            'dpgettext' => 'dpgettext',
            'dp__' => 'dpgettext',
            'npgettext' => 'npgettext',
            'np__' => 'npgettext',
            'dnpgettext' => 'dnpgettext',
            'dnp__' => 'dnpgettext',
            'noop' => 'noop',
            'noop__' => 'noop',
        ],
    ];

    protected static $functionsScannerClass = 'GravityKit\GravityView\Gettext\Utils\JsFunctionsScanner';

    /**
     * @inheritdoc
     * @throws Exception
     */
    public static function fromString($string, Translations $translations, array $options = [])
    {
        static::fromStringMultiple($string, [$translations], $options);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public static function fromStringMultiple($string, array $translations, array $options = [])
    {
        $options += static::$options;

        /** @var FunctionsScanner $functions */
        $functions = new static::$functionsScannerClass($string);
        $functions->saveGettextFunctions($translations, $options);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public static function fromFileMultiple($file, array $translations, array $options = [])
    {
        foreach (static::getFiles($file) as $file) {
            $options['file'] = $file;
            static::fromStringMultiple(static::readFile($file), $translations, $options);
        }
    }
}