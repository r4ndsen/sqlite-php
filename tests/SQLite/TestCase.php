<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use r4ndsen\SQLite;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected SQLite $SQLite;

    protected function setUp(): void
    {
        // coverage for mutation testing on protected methods and properties
        $this->SQLite = new class extends SQLite {};
    }

    public function getMethod(object $object, string $methodName): ReflectionMethod
    {
        return $this->getReflection($object)->getMethod($methodName);
    }

    public function getProperty(object $object, string $propertyName): ReflectionProperty
    {
        $reflection = $this->getReflection($object);

        if ($reflection->hasProperty($propertyName)) {
            return $reflection->getProperty($propertyName);
        }

        return new ReflectionProperty($object, $propertyName);
    }

    public function getPropertyValue(object $object, string $propertyName): mixed
    {
        return $this
            ->getProperty($object, $propertyName)
            ->getValue($object)
        ;
    }

    /** @return ReflectionClass<object> */
    public function getReflection(object $object): ReflectionClass
    {
        return new ReflectionClass($object);
    }

    public function invokeArgs(object $object, string $method, array $args = []): mixed
    {
        return $this
            ->getMethod($object, $method, true)
            ->invokeArgs($object, $args)
        ;
    }
}
