<?php

namespace PugKong\Doctrine\EntityFactory\Tests;

use function count;

class Faker
{
    private const PHONES = ['+1-202-555-0117', '+1-202-555-0175'];

    /**
     * @psalm-var non-negative-int
     */
    private int $calls = 0;

    public function phone(): string
    {
        $phone = self::PHONES[$this->calls % count(self::PHONES)];
        ++$this->calls;

        return $phone;
    }
}
