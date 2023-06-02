# Doctrine Entity Factory

[![build](https://github.com/PugKong/wsl-wrapper/actions/workflows/ci.yml/badge.svg)](https://github.com/PugKong/doctrine-entity-factory/actions/workflows/ci.yml)
[![License: WTFPL](https://img.shields.io/badge/License-WTFPL-brightgreen.svg)](http://www.wtfpl.net/about/)
[![codecov](https://codecov.io/gh/PugKong/doctrine-entity-factory/branch/master/graph/badge.svg?token=AATGXW20YL)](https://codecov.io/gh/PugKong/doctrine-entity-factory)
[![Latest Version](https://img.shields.io/packagist/v/pugkong/doctrine-entity-factory.svg)](https://packagist.org/packages/pugkong/doctrine-entity-factory)

Tiny library to help with creating and persisting Doctrine's entities in tests.
No magic, arrays or yaml inside, it has a simple API with only 5 methods.

## Why

Modern fixtures solutions are very complex and lack of IDE support. Here are some examples

- [nelmio/alice](https://github.com/nelmio/alice) is a great tool.
  But you're forced to use yaml, json or php array to define your data (no ide support).
  It has lots of features and huge documentation, which will take some time to learn and master.
- [zenstruck/foundry](https://github.com/zenstruck/foundry) is another great tool.
  But again you're forced to use arrays and have tons of features.

I've got tired of these complex solutions without IDE support and created this tiny library for myself.

## Installation

```sh
$ composer require --dev pugkong/doctrine-entity-factory
```

## Examples

*You can check these examples in `tests` folder.*

Assume we have some `User` entity.

```php
namespace App\Entity;

class User {
    private string $firstName = '';
    private string $lastName = '';
    private bool $active = true;
    private string $password = '';
    private string $phone = '';
    
    // bunch of getters and setters
}
```

A factory for this entity might look like

```php
namespace Factory;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PugKong\Doctrine\EntityFactory\EntityFactory;

/**
 * @extends EntityFactory<User>
 */
class UserFactory extends EntityFactory
{
    public function __construct(
        // factory is a plain old service, so you can safely use DI here 
        private readonly Faker $faker,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    // every factory should implement the new() method
    protected function new(): User
    {
        $user = new User();
        // every new user will be inactive John Doe with empty password 
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('');
        $user->setActive(false);
        // and a random phone
        $user->setPhone($this->faker->phone());

        return $user;
    }

    // some libraries have a so-called "states" feature
    // you can achieve it by creating your own method
    public function active(): self
    {
        return $this->with(fn (User $u) => $u->setActive(true));
    }

    // sometimes you want to simplify setting some values
    // so, again, you can achieve it by creating your own method
    public function withPassword(string $password): self
    {
        // in Symfony you'll probably use UserPasswordHasherInterface here
        return $this->with(fn (User $u) => $u->setPassword(sha256($password)));
    }
}
```

Now let's create and store some entities

```php
use App\Entity\User;
use Factory\UserFactory;

/** @var UserFactory $factory */
$johnDoe = $factory->object(); // will return an object from new() method
$johnDoePersisted = $factory->persisted(); // will return a persisted but not flushed object
$johnDoeFlushed = $factory->attached(); // will return a persisted and flushed object
$johnDoeDetached = $factory->detached(); // will return a flushed and detached object

// this will create a new factory with another default lastName
$toeFactory = $factory->with(fn (User $u) => $u->setLastName('Toe'));
$johnToe = $toeFactory->object(); // will create John Toe
// will create Jane Toe
$janeToe = $toeFactory->with(fn (User $u) => $u->setFirstName('Jane'))->object();

// will create another John Doe, but in active "state" one
$activeJohn = $factory->active()->object();

// will create a new user with hashed password
$johnWithPassword = $factory->withPassword('test')->object();

// batch operations of any form are also supported (thanks god we have loops in PHP)
// let's create some Johns
$johns = [];
foreach (['Doe', 'Smith', 'Johnson', 'Williams', 'Brown'] as $lastName) {
    $johns[] = $factory->with(fn (User $u) => $u->setLastName($lastName))->object();
}
// here we'll have five namesakes
```
