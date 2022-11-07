<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

abstract class BaseTestClient extends TestCase
{

    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

}