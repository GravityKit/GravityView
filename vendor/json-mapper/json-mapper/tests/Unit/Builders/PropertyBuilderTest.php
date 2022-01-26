<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Builders;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Enums\Visibility;
use JsonMapper\Tests\Helpers\AssertThatPropertyTrait;
use JsonMapper\ValueObjects\PropertyType;
use PHPUnit\Framework\TestCase;

class PropertyBuilderTest extends TestCase
{
    use AssertThatPropertyTrait;

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testCanBuildPropertyWithAllPropertiesSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('enabled')
            ->addType('boolean', false)
            ->setIsNullable(true)
            ->setVisibility(Visibility::PRIVATE())
            ->build();

        $this->assertThatProperty($property)
            ->hasName('enabled')
            ->hasType('boolean', false)
            ->hasVisibility(Visibility::PRIVATE())
            ->isNullable();
    }

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testCanBuildPropertyWithAllPropertiesSetUsingSetTypes(): void
    {
        $property = PropertyBuilder::new()
            ->setName('enabled')
            ->setTypes(new PropertyType('string', true), new PropertyType('int', false))
            ->setIsNullable(true)
            ->setVisibility(Visibility::PRIVATE())
            ->build();

        $this->assertThatProperty($property)
            ->hasName('enabled')
            ->hasType('string', true)
            ->hasType('int', false)
            ->hasVisibility(Visibility::PRIVATE())
            ->isNullable();
    }
}
