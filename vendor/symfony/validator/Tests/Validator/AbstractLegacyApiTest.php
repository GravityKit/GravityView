<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Validator;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\MetadataFactoryInterface;
use Symfony\Component\Validator\Tests\Fixtures\Entity;
use Symfony\Component\Validator\Tests\Fixtures\Reference;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidatorInterface;

/**
 * Verifies that a validator satisfies the API of Symfony < 2.5.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @group legacy
 */
abstract class AbstractLegacyApiTest extends AbstractValidatorTest
{
    /**
     * @var LegacyValidatorInterface
     */
    protected $validator;

    /**
     * @param MetadataFactoryInterface $metadataFactory
     *
     * @return LegacyValidatorInterface
     */
    abstract protected function createValidator(MetadataFactoryInterface $metadataFactory, array $objectInitializers = array());

    protected function setUp()
    {
        parent::setUp();

        $this->validator = $this->createValidator($this->metadataFactory);
    }

    protected function validate($value, $constraints = null, $groups = null)
    {
        if (null === $constraints) {
            $constraints = new Valid();
        }

        if ($constraints instanceof Valid) {
            return $this->validator->validate($value, $groups, $constraints->traverse, $constraints->deep);
        }

        return $this->validator->validateValue($value, $constraints, $groups);
    }

    protected function validateProperty($object, $propertyName, $groups = null)
    {
        return $this->validator->validateProperty($object, $propertyName, $groups);
    }

    protected function validatePropertyValue($object, $propertyName, $value, $groups = null)
    {
        return $this->validator->validatePropertyValue($object, $propertyName, $value, $groups);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\NoSuchMetadataException
     */
    public function testTraversableTraverseDisabled()
    {
        $test = $this;
        $entity = new Entity();
        $traversable = new \ArrayIterator(array('key' => $entity));

        $callback = function () use ($test) {
            $test->fail('Should not be called');
        };

        $this->metadata->addConstraint(new Callback(array(
            'callback' => $callback,
            'groups' => 'Group',
        )));

        $this->validator->validate($traversable, 'Group');
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\NoSuchMetadataException
     */
    public function testRecursiveTraversableRecursiveTraversalDisabled()
    {
        $test = $this;
        $entity = new Entity();
        $traversable = new \ArrayIterator(array(
            2 => new \ArrayIterator(array('key' => $entity)),
        ));

        $callback = function () use ($test) {
            $test->fail('Should not be called');
        };

        $this->metadata->addConstraint(new Callback(array(
            'callback' => $callback,
            'groups' => 'Group',
        )));

        $this->validator->validate($traversable, 'Group');
    }

    public function testValidateInContext()
    {
        $test = $this;
        $entity = new Entity();
        $entity->reference = new Reference();

        $callback1 = function ($value, ExecutionContextInterface $context) use ($test) {
            $previousValue = $context->getValue();
            $previousMetadata = $context->getMetadata();
            $previousPath = $context->getPropertyPath();
            $previousGroup = $context->getGroup();

            $context->validate($value->reference, 'subpath');

            // context changes shouldn't leak out of the validate() call
            $test->assertSame($previousValue, $context->getValue());
            $test->assertSame($previousMetadata, $context->getMetadata());
            $test->assertSame($previousPath, $context->getPropertyPath());
            $test->assertSame($previousGroup, $context->getGroup());
        };

        $callback2 = function ($value, ExecutionContextInterface $context) use ($test, $entity) {
            $test->assertSame($test::REFERENCE_CLASS, $context->getClassName());
            $test->assertNull($context->getPropertyName());
            $test->assertSame('subpath', $context->getPropertyPath());
            $test->assertSame('Group', $context->getGroup());
            $test->assertSame($test->referenceMetadata, $context->getMetadata());
            $test->assertSame($test->metadataFactory, $context->getMetadataFactory());
            $test->assertSame($entity, $context->getRoot());
            $test->assertSame($entity->reference, $context->getValue());
            $test->assertSame($entity->reference, $value);

            $context->addViolation('Message %param%', array('%param%' => 'value'));
        };

        $this->metadata->addConstraint(new Callback(array(
            'callback' => $callback1,
            'groups' => 'Group',
        )));
        $this->referenceMetadata->addConstraint(new Callback(array(
            'callback' => $callback2,
            'groups' => 'Group',
        )));

        $violations = $this->validator->validate($entity, 'Group');

        /* @var ConstraintViolationInterface[] $violations */
        $this->assertCount(1, $violations);
        $this->assertSame('Message value', $violations[0]->getMessage());
        $this->assertSame('Message %param%', $violations[0]->getMessageTemplate());
        $this->assertSame(array('%param%' => 'value'), $violations[0]->getParameters());
        $this->assertSame('subpath', $violations[0]->getPropertyPath());
        $this->assertSame($entity, $violations[0]->getRoot());
        $this->assertSame($entity->reference, $violations[0]->getInvalidValue());
        $this->assertNull($violations[0]->getPlural());
        $this->assertNull($violations[0]->getCode());
    }

    public function testValidateArrayInContext()
    {
        $test = $this;
        $entity = new Entity();
        $entity->reference = new Reference();

        $callback1 = function ($value, ExecutionContextInterface $context) use ($test) {
            $previousValue = $context->getValue();
            $previousMetadata = $context->getMetadata();
            $previousPath = $context->getPropertyPath();
            $previousGroup = $context->getGroup();

            $context->validate(array('key' => $value->reference), 'subpath');

            // context changes shouldn't leak out of the validate() call
            $test->assertSame($previousValue, $context->getValue());
            $test->assertSame($previousMetadata, $context->getMetadata());
            $test->assertSame($previousPath, $context->getPropertyPath());
            $test->assertSame($previousGroup, $context->getGroup());
        };

        $callback2 = function ($value, ExecutionContextInterface $context) use ($test, $entity) {
            $test->assertSame($test::REFERENCE_CLASS, $context->getClassName());
            $test->assertNull($context->getPropertyName());
            $test->assertSame('subpath[key]', $context->getPropertyPath());
            $test->assertSame('Group', $context->getGroup());
            $test->assertSame($test->referenceMetadata, $context->getMetadata());
            $test->assertSame($test->metadataFactory, $context->getMetadataFactory());
            $test->assertSame($entity, $context->getRoot());
            $test->assertSame($entity->reference, $context->getValue());
            $test->assertSame($entity->reference, $value);

            $context->addViolation('Message %param%', array('%param%' => 'value'));
        };

        $this->metadata->addConstraint(new Callback(array(
            'callback' => $callback1,
            'groups' => 'Group',
        )));
        $this->referenceMetadata->addConstraint(new Callback(array(
            'callback' => $callback2,
            'groups' => 'Group',
        )));

        $violations = $this->validator->validate($entity, 'Group');

        /* @var ConstraintViolationInterface[] $violations */
        $this->assertCount(1, $violations);
        $this->assertSame('Message value', $violations[0]->getMessage());
        $this->assertSame('Message %param%', $violations[0]->getMessageTemplate());
        $this->assertSame(array('%param%' => 'value'), $violations[0]->getParameters());
        $this->assertSame('subpath[key]', $violations[0]->getPropertyPath());
        $this->assertSame($entity, $violations[0]->getRoot());
        $this->assertSame($entity->reference, $violations[0]->getInvalidValue());
        $this->assertNull($violations[0]->getPlural());
        $this->assertNull($violations[0]->getCode());
    }

    public function testAddCustomizedViolation()
    {
        $entity = new Entity();

        $callback = function ($value, ExecutionContextInterface $context) {
            $context->addViolation(
                'Message %param%',
                array('%param%' => 'value'),
                'Invalid value',
                2,
                'Code'
            );
        };

        $this->metadata->addConstraint(new Callback($callback));

        $violations = $this->validator->validate($entity);

        /* @var ConstraintViolationInterface[] $violations */
        $this->assertCount(1, $violations);
        $this->assertSame('Message value', $violations[0]->getMessage());
        $this->assertSame('Message %param%', $violations[0]->getMessageTemplate());
        $this->assertSame(array('%param%' => 'value'), $violations[0]->getParameters());
        $this->assertSame('', $violations[0]->getPropertyPath());
        $this->assertSame($entity, $violations[0]->getRoot());
        $this->assertSame('Invalid value', $violations[0]->getInvalidValue());
        $this->assertSame(2, $violations[0]->getPlural());
        $this->assertSame('Code', $violations[0]->getCode());
    }

    public function testInitializeObjectsOnFirstValidation()
    {
        $test = $this;
        $entity = new Entity();
        $entity->initialized = false;

        // prepare initializers that set "initialized" to true
        $initializer1 = $this->getMockBuilder('Symfony\\Component\\Validator\\ObjectInitializerInterface')->getMock();
        $initializer2 = $this->getMockBuilder('Symfony\\Component\\Validator\\ObjectInitializerInterface')->getMock();

        $initializer1->expects($this->once())
            ->method('initialize')
            ->with($entity)
            ->will($this->returnCallback(function ($object) {
                $object->initialized = true;
            }));

        $initializer2->expects($this->once())
            ->method('initialize')
            ->with($entity);

        $this->validator = $this->createValidator($this->metadataFactory, array(
            $initializer1,
            $initializer2,
        ));

        // prepare constraint which
        // * checks that "initialized" is set to true
        // * validates the object again
        $callback = function ($object, ExecutionContextInterface $context) use ($test) {
            $test->assertTrue($object->initialized);

            // validate again in same group
            $context->validate($object);

            // validate again in other group
            $context->validate($object, '', 'SomeGroup');
        };

        $this->metadata->addConstraint(new Callback($callback));

        $this->validate($entity);

        $this->assertTrue($entity->initialized);
    }

    public function testGetMetadataFactory()
    {
        $this->assertSame($this->metadataFactory, $this->validator->getMetadataFactory());
    }
}
