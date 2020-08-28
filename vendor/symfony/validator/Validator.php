<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator;

@trigger_error('The '.__NAMESPACE__.'\Validator class is deprecated since Symfony 2.5 and will be removed in 3.0. Use the Symfony\Component\Validator\Validator\RecursiveValidator class instead.', E_USER_DEPRECATED);

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Default implementation of {@link ValidatorInterface}.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.5, to be removed in 3.0.
 *             Use {@link Validator\RecursiveValidator} instead.
 */
class Validator implements ValidatorInterface, Mapping\Factory\MetadataFactoryInterface
{
    private $metadataFactory;
    private $validatorFactory;
    private $translator;
    private $translationDomain;
    private $objectInitializers;

    public function __construct(MetadataFactoryInterface $metadataFactory, ConstraintValidatorFactoryInterface $validatorFactory, TranslatorInterface $translator, $translationDomain = 'validators', array $objectInitializers = array())
    {
        $this->metadataFactory = $metadataFactory;
        $this->validatorFactory = $validatorFactory;
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
        $this->objectInitializers = $objectInitializers;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($value)
    {
        return $this->metadataFactory->getMetadataFor($value);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataFor($value)
    {
        return $this->metadataFactory->hasMetadataFor($value);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, $groups = null, $traverse = false, $deep = false)
    {
        $visitor = $this->createVisitor($value);

        foreach ($this->resolveGroups($groups) as $group) {
            $visitor->validate($value, $group, '', $traverse, $deep);
        }

        return $visitor->getViolations();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ValidatorException if the metadata for the value does not support properties
     */
    public function validateProperty($containingValue, $property, $groups = null)
    {
        $visitor = $this->createVisitor($containingValue);
        $metadata = $this->metadataFactory->getMetadataFor($containingValue);

        if (!$metadata instanceof PropertyMetadataContainerInterface) {
            $valueAsString = is_scalar($containingValue)
                ? '"'.$containingValue.'"'
                : 'the value of type '.\gettype($containingValue);

            throw new ValidatorException(sprintf('The metadata for %s does not support properties.', $valueAsString));
        }

        foreach ($this->resolveGroups($groups) as $group) {
            if (!$metadata->hasPropertyMetadata($property)) {
                continue;
            }

            foreach ($metadata->getPropertyMetadata($property) as $propMeta) {
                $propMeta->accept($visitor, $propMeta->getPropertyValue($containingValue), $group, $property);
            }
        }

        return $visitor->getViolations();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ValidatorException if the metadata for the value does not support properties
     */
    public function validatePropertyValue($containingValue, $property, $value, $groups = null)
    {
        $visitor = $this->createVisitor(\is_object($containingValue) ? $containingValue : $value);
        $metadata = $this->metadataFactory->getMetadataFor($containingValue);

        if (!$metadata instanceof PropertyMetadataContainerInterface) {
            $valueAsString = is_scalar($containingValue)
                ? '"'.$containingValue.'"'
                : 'the value of type '.\gettype($containingValue);

            throw new ValidatorException(sprintf('The metadata for %s does not support properties.', $valueAsString));
        }

        // If $containingValue is passed as class name, take $value as root
        // and start the traversal with an empty property path
        $propertyPath = \is_object($containingValue) ? $property : '';

        foreach ($this->resolveGroups($groups) as $group) {
            if (!$metadata->hasPropertyMetadata($property)) {
                continue;
            }

            foreach ($metadata->getPropertyMetadata($property) as $propMeta) {
                $propMeta->accept($visitor, $value, $group, $propertyPath);
            }
        }

        return $visitor->getViolations();
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $constraints, $groups = null)
    {
        $context = new ExecutionContext($this->createVisitor($value), $this->translator, $this->translationDomain);

        $constraints = \is_array($constraints) ? $constraints : array($constraints);

        foreach ($constraints as $constraint) {
            if ($constraint instanceof Valid) {
                // Why can't the Valid constraint be executed directly?
                //
                // It cannot be executed like regular other constraints, because regular
                // constraints are only executed *if they belong to the validated group*.
                // The Valid constraint, on the other hand, is always executed and propagates
                // the group to the cascaded object. The propagated group depends on
                //
                //  * Whether a group sequence is currently being executed. Then the default
                //    group is propagated.
                //
                //  * Otherwise the validated group is propagated.

                throw new ValidatorException(sprintf('The constraint %s cannot be validated. Use the method validate() instead.', \get_class($constraint)));
            }

            $context->validateValue($value, $constraint, '', $groups);
        }

        return $context->getViolations();
    }

    /**
     * @param mixed $root
     *
     * @return ValidationVisitor
     */
    private function createVisitor($root)
    {
        return new ValidationVisitor(
            $root,
            $this->metadataFactory,
            $this->validatorFactory,
            $this->translator,
            $this->translationDomain,
            $this->objectInitializers
        );
    }

    /**
     * @param string|string[]|null $groups
     *
     * @return string[]
     */
    private function resolveGroups($groups)
    {
        return $groups ? (array) $groups : array(Constraint::DEFAULT_GROUP);
    }
}
