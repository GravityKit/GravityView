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
        eval('class ClassWithoutFile {}');
        UseStatementHelper::getImports(new \ReflectionClass(new \ClassWithoutFile()));
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsWithFileNotReadableThrowsException(): void
    {
        $fileName = '/some/non/readable/path';
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->method('isUserDefined')->willReturn(true);
        $reflectionMock->method('getFileName')->willReturn($fileName);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to read {$fileName}");
        UseStatementHelper::getImports($reflectionMock);
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsWithFileNotProvidingValidAstThrowsException(): void
    {
        $fileName = tempnam(sys_get_temp_dir(), __METHOD__);
        $handle = fopen($fileName, 'wb');
        fwrite($handle, "<?php some invalid php code");
        fclose($handle);
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->method('isUserDefined')->willReturn(true);
        $reflectionMock->method('getFileName')->willReturn($fileName);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Something went wrong when parsing {$fileName}");
        UseStatementHelper::getImports($reflectionMock);

        unlink($fileName);
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsWithBuiltinClassReturnsEmptyArray(): void
    {
        $imports = UseStatementHelper::getImports(new \ReflectionClass(\stdClass::class));

        self::assertEquals([], $imports);
    }
}
