<?php

declare(strict_types=1);

namespace JsonMapper\Handler;

use JsonMapper\Exception\ClassFactoryException;

class ClassFactoryRegistry
{
    /** @var callable[] */
    private $factories = [];

    public function loadNativePhpClassFactories(): self
    {
        $this->addFactory(\DateTime::class, static function (string $value) {
            return new \DateTime($value);
        });
        $this->addFactory(\DateTimeImmutable::class, static function (string $value) {
            return new \DateTimeImmutable($value);
        });

        return $this;
    }

    public function addFactory(string $className, callable $factory): self
    {
        if ($this->hasFactory($className)) {
            throw ClassFactoryException::forDuplicateClassname($className);
        }

        $this->factories[$this->sanatizeClassName($className)] = $factory;

        return $this;
    }

    public function hasFactory(string $className): bool
    {
        return array_key_exists($this->sanatizeClassName($className), $this->factories);
    }

    /**
     * @param mixed $params
     * @return mixed
     */
    public function create(string $className, $params)
    {
        if (!$this->hasFactory($className)) {
            throw ClassFactoryException::forMissingClassname($className);
        }

        $factory = $this->factories[$this->sanatizeClassName($className)];

        return $factory($params);
    }

    private function sanatizeClassName(string $className): string
    {
        /* Erase leading slash as ::class doesnt contain leading slash */
        if (strpos($className, '\\') === 0) {
            $className = substr($className, 1);
        }

        return $className;
    }
}
