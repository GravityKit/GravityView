<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\ValueObjects;

use JsonMapper\ValueObjects\AnnotationMap;
use PHPUnit\Framework\TestCase;

class AnnotationMapTest extends TestCase
{
    /**
     * @covers \JsonMapper\ValueObjects\AnnotationMap
     */
    public function testGettersReturnConstructorValues(): void
    {
        $params = ['name' => 'string', 'age' => 'int'];
        $property = new AnnotationMap('varName', $params, 'int');

        self::assertSame('varName', $property->getVar());
        self::assertSame($params, $property->getParams());
        self::assertSame('int', $property->getReturn());
    }

    /**
     * @covers \JsonMapper\ValueObjects\AnnotationMap
     */
    public function testGettersReturnConstructorValuesWithDefaults(): void
    {
        $property = new AnnotationMap();

        self::assertFalse($property->hasVar());
        self::assertEmpty($property->getParams());
        self::assertFalse($property->hasReturn());
    }

    /**
     * @covers \JsonMapper\ValueObjects\AnnotationMap
     */
    public function testGetVarThrowsErrorWhenNoVarAvailable(): void
    {
        $property = new AnnotationMap();

        self::assertFalse($property->hasVar());
        $this->expectException(\Exception::class);
        $property->getVar();
    }

    /**
     * @covers \JsonMapper\ValueObjects\AnnotationMap
     */
    public function testGetReturnThrowsErrorWhenNoReturnAvailable(): void
    {
        $property = new AnnotationMap();

        self::assertFalse($property->hasReturn());
        $this->expectException(\Exception::class);
        $property->getReturn();
    }
}
