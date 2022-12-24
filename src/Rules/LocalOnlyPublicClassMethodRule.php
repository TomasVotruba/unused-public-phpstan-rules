<?php

declare(strict_types=1);

namespace TomasVotruba\UnusedPublic\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use TomasVotruba\UnusedPublic\CollectorMapper\MethodCallCollectorMapper;
use TomasVotruba\UnusedPublic\Collectors\MethodCallCollector;
use TomasVotruba\UnusedPublic\Collectors\PublicClassMethodCollector;
use TomasVotruba\UnusedPublic\Collectors\StaticMethodCallCollector;
use TomasVotruba\UnusedPublic\Configuration;
use TomasVotruba\UnusedPublic\Enum\RuleTips;
use TomasVotruba\UnusedPublic\Twig\PossibleTwigMethodCallsProvider;
use TomasVotruba\UnusedPublic\Twig\UsedMethodAnalyzer;
use TomasVotruba\UnusedPublic\ValueObject\LocalAndExternalMethodCallReferences;

/**
 * @see \TomasVotruba\UnusedPublic\Tests\Rules\LocalOnlyPublicClassMethodRule\LocalOnlyPublicClassMethodRuleTest
 */
final class LocalOnlyPublicClassMethodRule implements Rule
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = 'Public method "%s::%s()" is used only locally and should turned protected/private';

    public function __construct(
        private readonly Configuration $configuration,
        private readonly UsedMethodAnalyzer $usedMethodAnalyzer,
        private readonly PossibleTwigMethodCallsProvider $possibleTwigMethodCallsProvider,
        private readonly MethodCallCollectorMapper $methodCallCollectorMapper
    ) {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->configuration->isLocalMethodEnabled()) {
            return [];
        }

        $twigMethodNames = $this->possibleTwigMethodCallsProvider->provide();

        $completeMethodCallCollector = $this->methodCallCollectorMapper->mapToMethodCallReferences(
            $node->get(MethodCallCollector::class),
            $node->get(StaticMethodCallCollector::class)
        );

        //        $methodCallCollector = $node->get(MethodCallCollector::class);
        //        $staticMethodCallCollector = $node->get(StaticMethodCallCollector::class);
        //        $completeMethodCallCollector = array_merge_recursive($methodCallCollector, $staticMethodCallCollector);

        $localAndExternalMethodCallReferences = $this->methodCallCollectorMapper->mapToLocalAndExternal(
            $completeMethodCallCollector
        );

        $publicClassMethodCollector = $node->get(PublicClassMethodCollector::class);

        $ruleErrors = [];

        foreach ($publicClassMethodCollector as $filePath => $declarations) {
            foreach ($declarations as [$className, $methodName, $line]) {
                if (! $this->isUsedOnlyLocally(
                    $className,
                    $methodName,
                    $localAndExternalMethodCallReferences,
                    $twigMethodNames
                )) {
                    continue;
                }

                /** @var string $methodName */
                $errorMessage = sprintf(self::ERROR_MESSAGE, $className, $methodName);

                $ruleErrors[] = RuleErrorBuilder::message($errorMessage)
                    ->file($filePath)
                    ->line($line)
                    ->tip(RuleTips::NARROW_SCOPE)
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * @param string[] $twigMethodNames
     */
    private function isUsedOnlyLocally(
        string $className,
        string $methodName,
        LocalAndExternalMethodCallReferences $localAndExternalMethodCallReferences,
        array $twigMethodNames
    ): bool {
        if ($this->usedMethodAnalyzer->isUsedInTwig($methodName, $twigMethodNames)) {
            return true;
        }

        $publicMethodReference = $className . '::' . $methodName;

        if (in_array(
            $publicMethodReference,
            $localAndExternalMethodCallReferences->getExternalMethodCallReferences(),
            true
        )) {
            return false;
        }

        return in_array(
            $publicMethodReference,
            $localAndExternalMethodCallReferences->getLocalMethodCallReferences(),
            true
        );
    }
}
