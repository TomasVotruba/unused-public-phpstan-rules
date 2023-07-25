<?php

declare(strict_types=1);

namespace TomasVotruba\UnusedPublic\Tests\Rules\UnusedPublicPropertyRule\Source;

use PHPUnit\Framework\TestCase;
use TomasVotruba\UnusedPublic\Tests\Rules\UnusedPublicClassMethodRule\Fixture\UsedInTestCaseOnly;

final class TestCaseUser extends TestCase
{
    private function go()
    {
        $o = new \TomasVotruba\UnusedPublic\Tests\Rules\UnusedPublicPropertyRule\Fixture\UsedInTestCaseOnly();
        $o->property = 'a value';
    }
}
