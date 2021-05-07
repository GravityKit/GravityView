<?php

declare(strict_types=1);

namespace JsonMapper\Middleware;

use JsonMapper\Enums\TextNotation;
use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;

class CaseConversion extends AbstractMiddleware
{
    /** @var TextNotation */
    private $searchSeparator;
    /** @var TextNotation */
    private $replacementSeparator;

    public function __construct(TextNotation $searchSeparator, TextNotation $replacementSeparator)
    {
        $this->searchSeparator = $searchSeparator;
        $this->replacementSeparator = $replacementSeparator;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        if ($this->searchSeparator->equals($this->replacementSeparator)) {
            return;
        }

        $keys = array_keys((array) $json);
        foreach ($keys as $key) {
            $replacementKey = $this->getReplacementKey($key);

            if ($replacementKey === $key) {
                continue;
            }

            $json->$replacementKey = $json->$key;
            unset($json->$key);
        }
    }

    private function getReplacementKey(string $key): string
    {
        switch ($this->searchSeparator) {
            case TextNotation::CAMEL_CASE():
                return $this->replaceFromCamelCase($key);
            case TextNotation::STUDLY_CAPS():
                return $this->replaceFromStudlyCaps($key);
            case TextNotation::UNDERSCORE():
                return $this->replaceFromUnderscore($key);
            case TextNotation::KEBAB_CASE():
                return $this->replaceFromKebabCase($key);
            default:
                return $key;
        }
    }

    private function replaceFromCamelCase(string $key): string
    {
        switch ($this->replacementSeparator) {
            case TextNotation::STUDLY_CAPS():
                return ucfirst($key);
            case TextNotation::UNDERSCORE():
                return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            case TextNotation::KEBAB_CASE():
                return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $key));
            case TextNotation::CAMEL_CASE():
            default:
                return $key;
        }
    }

    private function replaceFromStudlyCaps(string $key): string
    {
        switch ($this->replacementSeparator) {
            case TextNotation::CAMEL_CASE():
                return lcfirst($key);
            case TextNotation::UNDERSCORE():
                return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            case TextNotation::KEBAB_CASE():
                return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $key));
            case TextNotation::STUDLY_CAPS():
            default:
                return $key;
        }
    }

    private function replaceFromUnderscore(string $key): string
    {
        switch ($this->replacementSeparator) {
            case TextNotation::STUDLY_CAPS():
                return ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            case TextNotation::CAMEL_CASE():
                return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            case TextNotation::KEBAB_CASE():
                return str_replace('_', '-', $key);
            case TextNotation::UNDERSCORE():
            default:
                return $key;
        }
    }

    private function replaceFromKebabCase(string $key): string
    {
        switch ($this->replacementSeparator) {
            case TextNotation::STUDLY_CAPS():
                return ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $key))));
            case TextNotation::CAMEL_CASE():
                return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $key))));
            case TextNotation::UNDERSCORE():
                return str_replace('-', '_', $key);
            case TextNotation::KEBAB_CASE():
            default:
                return $key;
        }
    }
}
