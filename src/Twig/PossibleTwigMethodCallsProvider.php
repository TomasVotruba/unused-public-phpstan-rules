<?php

declare(strict_types=1);

namespace TomasVotruba\UnusedPublic\Twig;

use Nette\Utils\Strings;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TomasVotruba\UnusedPublic\Configuration;
use Webmozart\Assert\Assert;

final class PossibleTwigMethodCallsProvider
{
    /**
     * @see https://regex101.com/r/3gLWCt/1
     * @var string
     */
    private const TWIG_INNER_REGEX = '#\{\{(?<contents>.*?)\}\}#';

    /**
     * @see https://regex101.com/r/G7zAue/1
     * @var string
     */
    private const TWIG_METHOD_CALL_REGEX = '#\w+\.(?<method_name>\w+)#';

    /**
     * @var string[]
     */
    private array $resolvedTwigMethodNames = [];

    public function __construct(
        private readonly Configuration $configuration
    ) {
    }

    /**
     * @return string[]
     */
    public function provide(): array
    {
        if ($this->resolvedTwigMethodNames !== []) {
            return $this->resolvedTwigMethodNames;
        }

        $twigMethodNames = [];

        foreach ($this->configuration->getTwigTemplatePaths() as $twigTemplatePath) {
            $absoluteTwigTemplatePath = getcwd() . '/' . $twigTemplatePath;
            Assert::directory($absoluteTwigTemplatePath);
            Assert::fileExists($absoluteTwigTemplatePath);

            $fileInfos = $this->findTwigFileInfos($absoluteTwigTemplatePath);

            foreach ($fileInfos as $fileInfo) {
                $matches = Strings::matchAll($fileInfo->getContents(), self::TWIG_INNER_REGEX);
                foreach ($matches as $match) {
                    $twigContents = $match['contents'];

                    $methodNamesMatches = Strings::matchAll($twigContents, self::TWIG_METHOD_CALL_REGEX);
                    foreach ($methodNamesMatches as $methodNameMatch) {
                        $twigMethodNames[] = $methodNameMatch['method_name'];
                    }
                }
            }
        }

        $this->resolvedTwigMethodNames = $twigMethodNames;

        return $twigMethodNames;
    }

    /**
     * @return SplFileInfo[]
     */
    private function findTwigFileInfos(string $directory): array
    {
        $twigFinder = Finder::create()
            ->files()
            ->in($directory)
            ->name('*.twig');

        return iterator_to_array($twigFinder->getIterator());
    }
}
