<?php

namespace PugKong\Doctrine\EntityFactory\Tests;

use Doctrine\ORM\EntityManagerInterface;
use PugKong\Doctrine\EntityFactory\EntityFactory;

/**
 * @extends EntityFactory<User>
 */
class UserFactory extends EntityFactory
{
    public function __construct(
        private readonly Faker $faker,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    protected function new(): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('');
        $user->setActive(false);
        $user->setPhone($this->faker->phone());

        return $user;
    }

    public function active(): self
    {
        return $this->with(fn (User $u) => $u->setActive(true));
    }

    public function withPassword(string $password): self
    {
        return $this->with(fn (User $u) => $u->setPassword(sha1($password)));
    }
}
