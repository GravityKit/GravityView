<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Mapping;

use Symfony\Component\Validator\ClassBasedInterface;
use Symfony\Component\Validator\PropertyMetadataInterface as LegacyPropertyMetadataInterface;

/**
 * Stores all metadata needed for validating the value of a class property.
 *
 * Most importantly, the metadata stores the constraints against which the
 * property's value should be validated.
 *
 * Additionally, the metadata stores whether objects stored in the property
 * should be validated against their class' metadata and whether traversable
 * objects should be traversed or not.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see MetadataInterface
 * @see CascadingStrategy
 * @see TraversalStrategy
 */
interface PropertyMetadataInterface extends MetadataInterface, LegacyPropertyMetadataInterface, ClassBasedInterface
{
}
