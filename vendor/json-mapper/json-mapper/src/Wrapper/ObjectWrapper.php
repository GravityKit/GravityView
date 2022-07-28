<?php

declare(strict_types=1);

namespace JsonMapper\Wrapper;

use JsonMapper\Exception\TypeError;

class ObjectWrapper
{
    /** @var object */
    private $object;
    /** @var \ReflectionClass|null */
    private $reflectedObject;

    /** @param object $object */
    public function __construct($object)
    {
        if (! \is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 1, '$object');
        }

        $this->object = $object;
    }

    /** @return object */
    public function getObject()
    {
        return $this->object;
    }

    public function getReflectedObject(): \ReflectionClass
    {
        if ($this->reflectedObject === null) {
            $this->reflectedObject = new \ReflectionClass($this->object);
        }

        return $this->reflectedObject;
    }

    public function getName(): string
    {
        return $this->getReflectedObject()->getName();
    }
}
