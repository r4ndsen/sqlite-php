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

    public function getMethod(object $object, string $methodName, ?bool $setAccessible = null): ReflectionMethod
    {
        $reflection = $this->getReflection($object);
        $method = $reflection->getMethod($methodName);
        if (isset($setAccessible)) {
            $method->setAccessible($setAccessible);
        }

        return $method;
    }

    public function getProperty(object $object, string $propertyName, ?bool $setAccessible = null): ReflectionProperty
    {
        $reflection = $this->getReflection($object);

        if ($reflection->hasProperty($propertyName)) {
            $property = $reflection->getProperty($propertyName);
            if ($setAccessible !== null) {
                $property->setAccessible($setAccessible);
            }
        } else {
            $property = new ReflectionProperty($object, $propertyName);
        }

        return $property;
    }

    public function getPropertyValue(object $object, string $propertyName): mixed
    {
        return $this
            ->getProperty($object, $propertyName, true)
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
