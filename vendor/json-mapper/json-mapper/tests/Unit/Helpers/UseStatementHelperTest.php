<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Helpers;

use JsonMapper\Helpers\UseStatementHelper;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;

class UseStatementHelperTest extends TestCase
{
    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testCanGetImports(): void
    {
        $imports = UseStatementHelper::getImports(new \ReflectionClass($this));

        self::assertEquals([UseStatementHelper::class, SimpleObject::class, TestCase::class], $imports);
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsForReflectedClassWithoutFileThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        UseStatementHelper::getImports(new \ReflectionClass(new \DateTimeImmutable()));
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsWithFileNotReadableThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        UseStatementHelper::getImports(new \ReflectionClass($this->createMock(SimpleObject::class)));
    }
}
