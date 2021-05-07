<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Handler;

use JsonMapper\Exception\ClassFactoryException;
use JsonMapper\Handler\ClassFactoryRegistry;
use PHPUnit\Framework\TestCase;

class ClassFactoryRegistryTest extends TestCase
{
    /**
     * @covers \JsonMapper\Handler\ClassFactoryRegistry
     */
    public function testloadNativePhpClassFactoriesAddsFactoriesForNativeClasses(): void
    {
        $classFactoryRegistry = new ClassFactoryRegistry();
        $classFactoryRegistry->loadNativePhpClassFactories();

        self::assertTrue($classFactoryRegistry->hasFactory(\DateTime::class));
        self::assertTrue($classFactoryRegistry->hasFactory(\DateTimeImmutable::class));
        self::assertEquals(new \DateTime('today'), $classFactoryRegistry->create(\DateTime::class, 'today'));
        self::assertEquals(
            new \DateTimeImmutable('today'),
            $classFactoryRegistry->create(\DateTimeImmutable::class, 'today')
        );
    }

    /**
     * @covers \JsonMapper\Handler\ClassFactoryRegistry
     */
    public function testAddFactoryAddsFactory(): void
    {
        $classFactoryRegistry = new ClassFactoryRegistry();
        $classFactoryRegistry->addFactory(__CLASS__, static function () {
        });

        self::assertTrue($classFactoryRegistry->hasFactory(__CLASS__));
    }

    /**
     * @covers \JsonMapper\Handler\ClassFactoryRegistry
     */
    public function testHasFactoryReturnsFalseWhenNoFactoryRegistered(): void
    {
        $classFactoryRegistry = new ClassFactoryRegistry();

        self::assertFalse($classFactoryRegistry->hasFactory(__CLASS__));
    }

    /**
     * @covers \JsonMapper\Handler\ClassFactoryRegistry
     */
    public function testAddFactoryThrowsExceptionWhenDuplicateClassNameIsAdded(): void
    {
        $classFactoryRegistry = new ClassFactoryRegistry();
        $classFactoryRegistry->addFactory(__CLASS__, static function () {
        });

        $this->expectExceptionObject(ClassFactoryException::forDuplicateClassname(__CLASS__));

        $classFactoryRegistry->addFactory(__CLASS__, static function () {
        });
    }

    /**
     * @covers \JsonMapper\Handler\ClassFactoryRegistry
     */
    public function testCreateReturnsValueFromCallable(): void
    {
        $classFactoryRegistry = new ClassFactoryRegistry();
        $object = new \stdClass();
        $classFactoryRegistry->addFactory(__CLASS__, static function () use ($object) {
            return $object;
        });

        self::assertSame($object, $classFactoryRegistry->create(__CLASS__, new \stdClass()));
    }

    /**
     * @covers \JsonMapper\Handler\ClassFactoryRegistry
     */
    public function testCreateCanHandleLeadingSlash(): void
    {
        $classFactoryRegistry = new ClassFactoryRegistry();
        $object = new \stdClass();
        $classFactoryRegistry->addFactory(\DateTimeImmutable::class, static function () use ($object) {
            return $object;
        });

        self::assertSame($object, $classFactoryRegistry->create('\DateTimeImmutable', new \stdClass()));
    }

    /**
     * @covers \JsonMapper\Handler\ClassFactoryRegistry
     */
    public function testCreateThrowsExceptionForMissingFactory(): void
    {
        $classFactoryRegistry = new ClassFactoryRegistry();

        $this->expectExceptionObject(ClassFactoryException::forMissingClassname(__CLASS__));

        $classFactoryRegistry->create(__CLASS__, new \stdClass());
    }
}
