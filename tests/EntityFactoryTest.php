<?php

namespace PugKong\Doctrine\EntityFactory\Tests;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PugKong\Doctrine\EntityFactory\EntityFactory
 */
class EntityFactoryTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;
    private UserFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->factory = new UserFactory(new Faker(), $this->entityManager);
    }

    public function testObject(): void
    {
        $john = $this->factory->object();
        self::assertSame('John', $john->getFirstName());
        self::assertSame('Doe', $john->getLastName());

        $twin = $this->factory->object();
        self::assertNotSame($john, $twin);
        self::assertSame('John', $john->getFirstName());
        self::assertSame('Doe', $john->getLastName());
    }

    public function testWith(): void
    {
        $johnDoe = $this->factory->object();
        $janeDoe = $this->factory->with(fn (User $u) => $u->setFirstName('Jane'))->object();
        self::assertNotSame($johnDoe, $janeDoe);
        self::assertSame('Jane', $janeDoe->getFirstName());
        self::assertSame('Doe', $janeDoe->getLastName());

        $toeFactory = $this->factory->with(fn (User $u) => $u->setLastName('Toe'));
        $johnToe = $toeFactory->object();
        self::assertSame('John', $johnToe->getFirstName());
        self::assertSame('Toe', $johnToe->getLastName());
        $janeToe = $toeFactory->with(fn (User $u) => $u->setFirstName('Jane'))->object();
        self::assertSame('Jane', $janeToe->getFirstName());
        self::assertSame('Toe', $janeToe->getLastName());
    }

    public function testPersisted(): void
    {
        $object = $this->factory->object();
        $persisted = null;

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (User $user) use ($object, &$persisted): bool {
                self::assertNotSame($object, $user);
                self::assertSame('John', $user->getFirstName());
                self::assertSame('Doe', $user->getLastName());
                $persisted = $user;

                return true;
            }))
        ;
        $this->entityManager->expects(self::never())->method('flush');

        $flushed = $this->factory->persisted();
        self::assertSame($persisted, $flushed);
    }

    public function testAttached(): void
    {
        $object = $this->factory->object();
        $persisted = null;

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (User $user) use ($object, &$persisted): bool {
                self::assertNotSame($object, $user);
                self::assertSame('John', $user->getFirstName());
                self::assertSame('Doe', $user->getLastName());
                $persisted = $user;

                return true;
            }))
        ;
        $this->entityManager->expects(self::once())->method('flush');

        $flushed = $this->factory->attached();
        self::assertSame($persisted, $flushed);
    }

    public function testDetached(): void
    {
        $object = $this->factory->object();
        $persisted = null;

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (User $user) use ($object, &$persisted): bool {
                self::assertNotSame($object, $user);
                self::assertSame('John', $user->getFirstName());
                self::assertSame('Doe', $user->getLastName());
                $persisted = $user;

                return true;
            }))
        ;
        $this->entityManager->expects(self::once())->method('flush');
        $this->entityManager
            ->expects(self::once())
            ->method('detach')
            ->with(self::callback(function (User $user) use (&$persisted): bool {
                self::assertSame($persisted, $user);

                return true;
            }))
        ;

        $flushed = $this->factory->detached();
        self::assertSame($persisted, $flushed);
    }

    public function testStates(): void
    {
        $activeJohn = $this->factory->active()->object();
        self::assertSame('John', $activeJohn->getFirstName());
        self::assertSame('Doe', $activeJohn->getLastName());
        self::assertTrue($activeJohn->isActive());
    }

    public function testComplexSetters(): void
    {
        $john = $this->factory->withPassword('test')->object();
        self::assertSame(sha1('test'), $john->getPassword());
    }

    public function testFakeData(): void
    {
        self::assertSame('+1-202-555-0117', $this->factory->object()->getPhone());
        self::assertSame('+1-202-555-0175', $this->factory->object()->getPhone());

        $toeFactory = $this->factory->with(fn (User $u) => $u->setLastName('Toe'));
        self::assertSame('+1-202-555-0117', $toeFactory->object()->getPhone());
        self::assertSame('+1-202-555-0175', $toeFactory->object()->getPhone());
    }

    public function testBatch(): void
    {
        $johns = [];
        foreach (['Smith', 'Johnson'] as $lastName) {
            $johns[] = $this->factory->with(fn (User $u) => $u->setLastName($lastName))->object();
        }

        self::assertSame('John', $johns[0]->getFirstName());
        self::assertSame('Smith', $johns[0]->getLastName());
        self::assertSame('John', $johns[1]->getFirstName());
        self::assertSame('Johnson', $johns[1]->getLastName());
    }
}
