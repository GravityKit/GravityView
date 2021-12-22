<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Php81\BlogPost;
use JsonMapper\Tests\Implementation\Php81\Status;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class FeatureSupportsEnumsTest extends TestCase
{
    /**
     * @requires PHP >= 8.1
     */
    public function testItCanMapAnArrayUsingAVariadicSetter(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new BlogPost();
        $json = (object) ['status' => 'draft'];

        $mapper->mapObject($json, $object);

        self::assertSame(Status::DRAFT, $object->status);
    }
}
