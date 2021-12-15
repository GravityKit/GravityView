<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Middleware\Attributes;

use JsonMapper\Cache\NullCache;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Attributes\Attributes;
use JsonMapper\Middleware\TypedProperties;
use JsonMapper\Tests\Implementation\Php80\AttributePopo;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Attributes\Attributes
     * @requires PHP >= 8.0
     */
    public function testAttributesMiddlewareDoesMapFrom(): void
    {
        $cache = new NullCache();
        $mapper = (new JsonMapperFactory())->create(
            new PropertyMapper(),
            new Attributes(),
            new TypedProperties($cache)
        );
        $object = new AttributePopo();
        $json = '{"Identifier": 42, "UserName": "John Doe"}';

        $mapper->mapObjectFromString($json, $object);

        self::assertSame(42, $object->id);
        self::assertSame('John Doe', $object->name);
    }
}
