<?php

declare(strict_types=1);

namespace TomasVotruba\UnusedPublic\Tests\Rules\UnusedPublicPropertyRule\Fixture;

use TomasVotruba\UnusedPublic\Tests\Rules\UnusedPublicPropertyRule\Source\PublicPropertyClass;

class NullableProperty
{
    public function getBar(): ?PublicPropertyClass
    {
        if (rand(0, 1)) {
            return null;
        }
        return new PublicPropertyClass();
    }

    public function doFoo(self $foo)
    {
        $x = $foo->getBar();
        echo $x->property;
    }
}
