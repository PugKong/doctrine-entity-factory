<?php

namespace PugKong\Doctrine\EntityFactory;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @template T of object
 */
abstract class EntityFactory
{
    /**
     * @param array<callable(T):mixed> $mutators
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private array $mutators = [],
    ) {
    }

    /**
     * Creates an object filled with some default values.
     *
     * @return T
     */
    abstract protected function new(): mixed;

    /**
     * Returns a new copy of the factory with given mutator appended.
     *
     * @param callable(T):mixed $mutator
     *
     * @return static<T>
     */
    public function with(callable $mutator): static
    {
        $self = clone $this;
        $self->mutators[] = $mutator;

        return $self;
    }

    /**
     * Returns a new object instance with all mutators applied.
     *
     * @return T
     */
    public function object(): mixed
    {
        $object = clone $this->new();
        foreach ($this->mutators as $mutator) {
            $mutator($object);
        }

        return $object;
    }

    /**
     * Same as object(), but the object is persisted to the entity manager.
     *
     * @return T
     */
    public function persisted(): mixed
    {
        $object = $this->object();
        $this->entityManager->persist($object);

        return $object;
    }

    /**
     * Same as persisted(), but the entity manager is flushed.
     *
     * @return T
     */
    public function attached(): mixed
    {
        $object = $this->persisted();
        $this->entityManager->flush();

        return $object;
    }

    /**
     * Same as attached(), but the object is detached from the entity manager.
     *
     * @return T
     */
    public function detached(): mixed
    {
        $object = $this->attached();
        $this->entityManager->detach($object);

        return $object;
    }
}
