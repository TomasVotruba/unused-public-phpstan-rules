<?php

declare(strict_types=1);

namespace TomasVotruba\UnusedPublic;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ThisType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use TomasVotruba\UnusedPublic\ValueObject\MethodCallReference;

final class ClassMethodCallReferenceResolver
{
    /**
     * @return iterable<MethodCallReference>
     */
    public function resolve(MethodCall $methodCall, Scope $scope): iterable
    {
        if ($methodCall->name instanceof Expr) {
            return;
        }

        $callerType = $scope->getType($methodCall->var);

        // remove optional nullable type
        if (TypeCombinator::containsNull($callerType)) {
            $callerType = TypeCombinator::removeNull($callerType);
        }

        // unwrap this type, as method is used
        $isLocal = false;

        if ($callerType instanceof ThisType) {
            $callerType = $callerType->getStaticObjectType();
            $isLocal = true;
        }

        foreach($callerType->getReferencedClasses() as $className) {
            yield new MethodCallReference($className, $methodCall->name->toString(), $isLocal);
        }
    }
}
