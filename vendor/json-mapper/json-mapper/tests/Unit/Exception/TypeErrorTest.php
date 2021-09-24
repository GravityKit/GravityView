<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Exception;

use JsonMapper\Exception\TypeError;
use PHPUnit\Framework\TestCase;

class TypeErrorTest extends TestCase
{
    /**
     * @covers \JsonMapper\Exception\TypeError
     */
    public function testForObjectArgumentReturnsExpectedException(): void
    {
        $e = $this->createTypeError(__METHOD__, '', 1);

        self::assertEquals(
            sprintf(
                '%s(): Argument #1 ($object) must be of type object, string given, called in %s on line %d',
                __METHOD__,
                __FILE__,
                __LINE__ - 7
            ),
            $e->getMessage()
        );
    }

    private function createTypeError(string $method, $object, int $argumentNumber): TypeError
    {
        return TypeError::forObjectArgument($method, $object, $argumentNumber);
    }
}
