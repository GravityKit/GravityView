<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Enums;

use JsonMapper\Enums\ScalarType;
use PHPUnit\Framework\TestCase;

class ScalarTypeTest extends TestCase
{
    /**
     * @covers \JsonMapper\Enums\ScalarType
     * @dataProvider castOperationDataProvider
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testCastOperationReturnsTheCorrectValue($value, string $castTo, $expected): void
    {
        self::assertEquals($expected, (new ScalarType($castTo))->cast($value));
    }

    /**
     * @covers \JsonMapper\Enums\ScalarType
     */
    public function testCastOperationThrowsExceptionWhenCastOperationNotSupported(): void
    {
        $extension = new class ('random') extends ScalarType {
            protected const RANDOM = 'random';
        };

        $this->expectException(\LogicException::class);
        $extension->cast('');
    }

    public function castOperationDataProvider(): array
    {
        return [
            'cast to string' => [42, 'string', '42'],
            'cast to boolean true' => [1, 'bool', true],
            'cast to boolean false' => [0, 'bool', false],
            'cast to int' => ['42', 'int', 42],
            'cast to float' => ['34.567', 'float', 34.567],
            'cast to mixed' => ['34.567', 'mixed', '34.567'],
        ];
    }
}
