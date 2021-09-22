<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\Enums\TextNotation;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\CaseConversion;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class CaseConversionTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\CaseConversion
     * @dataProvider conversionDataProvider
     */
    public function testCanConvertObject(
        TextNotation $search,
        TextNotation $replacement,
        string $original,
        string $replacementKey
    ): void {
        $middleware = new CaseConversion($search, $replacement);
        $json = (object) [$original => 'placeholder'];
        $object = new ObjectWrapper(new \stdClass());

        $middleware->handle($json, $object, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertObjectHasAttribute($replacementKey, $json);
        self::assertEquals('placeholder', $json->$replacementKey);
        self::assertObjectNotHasAttribute($original, $json);
    }

    /**
     * @covers \JsonMapper\Middleware\CaseConversion
     * @dataProvider possibleTextNotationDataProvider
     */
    public function testWillRemainUntouchedOnSameTextNotation(TextNotation $search): void
    {
        $middleware = new CaseConversion($search, $search);
        $key = 'StudlyCase-CamelCase_underscore';
        $json = (object) [$key => 'placeholder'];
        $object = new ObjectWrapper(new \stdClass());

        $middleware->handle($json, $object, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertObjectHasAttribute($key, $json);
    }

    /**
     * @covers \JsonMapper\Middleware\CaseConversion
     * @dataProvider conversionDataProvider
     */
    public function testWillRemainUntouchedOnSameReplacementKeyAsOriginalKey(
        TextNotation $search,
        TextNotation $replacement,
        string $original,
        string $replacementKey
    ): void {
        $middleware = new CaseConversion($search, $replacement);
        $json = (object) [$replacementKey => 'placeholder'];
        $object = new ObjectWrapper(new \stdClass());

        $middleware->handle($json, $object, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertObjectHasAttribute($replacementKey, $json);
        self::assertEquals('placeholder', $json->$replacementKey);
    }

    /**
     * @covers \JsonMapper\Middleware\CaseConversion
     */
    public function testWillRemainUntouchedOnInvalidExtensionOfTextNotationClassForSearch(): void
    {
        $extensionTextNotation = new class extends TextNotation
        {
            private const A = 'a';

            public function __construct()
            {
                parent::__construct('a');
            }
        };
        $middleware = new CaseConversion($extensionTextNotation, TextNotation::STUDLY_CAPS());
        $json = (object) ['key' => 'placeholder'];
        $object = new ObjectWrapper(new \stdClass());

        $middleware->handle($json, $object, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertObjectHasAttribute('key', $json);
        self::assertEquals('placeholder', $json->key);
    }

    /**
     * @covers \JsonMapper\Middleware\CaseConversion
     * @dataProvider possibleTextNotationDataProvider
     */
    public function testWillRemainUntouchedOnInvalidExtensionOfTextNotationClassForReplacement(TextNotation $search): void
    {
        $extensionTextNotation = new class extends TextNotation
        {
            private const A = 'a';

            public function __construct()
            {
                parent::__construct('a');
            }
        };
        $middleware = new CaseConversion($search, $extensionTextNotation);
        $json = (object) ['key' => 'placeholder'];
        $object = new ObjectWrapper(new \stdClass());

        $middleware->handle($json, $object, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertObjectHasAttribute('key', $json);
        self::assertEquals('placeholder', $json->key);
    }

    public function conversionDataProvider(): array
    {
        return [
            'Studly caps to camel case' => [TextNotation::STUDLY_CAPS(), TextNotation::CAMEL_CASE(), 'DeliveryAddress', 'deliveryAddress'],
            'Studly caps to underscore' => [TextNotation::STUDLY_CAPS(), TextNotation::UNDERSCORE(), 'DeliveryAddress', 'delivery_address'],
            'Studly caps to kebab case' => [TextNotation::STUDLY_CAPS(), TextNotation::KEBAB_CASE(), 'DeliveryAddress', 'delivery-address'],
            'Camel case to studly caps' => [TextNotation::CAMEL_CASE(), TextNotation::STUDLY_CAPS(), 'deliveryAddress', 'DeliveryAddress'],
            'Camel case to underscore' => [TextNotation::CAMEL_CASE(), TextNotation::UNDERSCORE(), 'deliveryAddress', 'delivery_address'],
            'Camel case to kebab case' => [TextNotation::CAMEL_CASE(), TextNotation::KEBAB_CASE(), 'deliveryAddress', 'delivery-address'],
            'Underscore to studly caps' => [TextNotation::UNDERSCORE(), TextNotation::STUDLY_CAPS(), 'delivery_address', 'DeliveryAddress'],
            'Underscore to camel case' => [TextNotation::UNDERSCORE(), TextNotation::CAMEL_CASE(), 'delivery_address', 'deliveryAddress'],
            'Underscore to kebab case' => [TextNotation::UNDERSCORE(), TextNotation::KEBAB_CASE(), 'delivery_address', 'delivery-address'],
            'Kebab case to studly caps' => [TextNotation::KEBAB_CASE(), TextNotation::STUDLY_CAPS(), 'delivery-address', 'DeliveryAddress'],
            'Kebab case to camel case' => [TextNotation::KEBAB_CASE(), TextNotation::CAMEL_CASE(), 'delivery-address', 'deliveryAddress'],
            'Kebab case to underscore' => [TextNotation::KEBAB_CASE(), TextNotation::UNDERSCORE(), 'delivery-address', 'delivery_address'],
        ];
    }

    public function possibleTextNotationDataProvider(): array
    {
        return [
            'Studly caps' => [TextNotation::STUDLY_CAPS()],
            'Camel case' => [TextNotation::CAMEL_CASE()],
            'Underscore' => [TextNotation::UNDERSCORE()],
            'Kebab case' => [TextNotation::KEBAB_CASE()],
        ];
    }
}
