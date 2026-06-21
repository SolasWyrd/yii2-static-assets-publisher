<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SolasWyrd\Yii2StaticAssets\Exception\AssetDiscoveryException;

/** @internal */
final class PhpClassExtractor
{
    private readonly Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    /** @return list<DiscoveredClass> */
    public function extract(string $filePath): array
    {
        if (!\is_file($filePath)) {
            throw new AssetDiscoveryException(
                \sprintf('PHP file "%s" does not exist.', $filePath),
            );
        }

        if (!\is_readable($filePath)) {
            throw new AssetDiscoveryException(
                \sprintf('PHP file "%s" is not readable.', $filePath),
            );
        }

        $source = \file_get_contents($filePath);

        if ($source === false) {
            throw new AssetDiscoveryException(
                \sprintf('Unable to read PHP file "%s".', $filePath),
            );
        }

        if (!\str_contains($source, 'class') || !\str_contains($source, 'extends')) {
            return [];
        }

        try {
            $statements = $this->parser->parse($source);
        } catch (Error $error) {
            throw new AssetDiscoveryException(
                \sprintf('Unable to parse PHP file "%s".', $filePath),
                previous: $error,
            );
        }

        if ($statements === null) {
            return [];
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $classes = [];

        $resolvedStatements = \array_values(
            $traverser->traverse($statements),
        );

        $this->collect(
            $resolvedStatements,
            $filePath,
            $classes,
        );

        return $classes;
    }

    /**
     * @param list<Node>            $nodes
     * @param list<DiscoveredClass> $classes
     */
    private function collect(array $nodes, string $filePath, array &$classes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Class_ && $node->name !== null && isset($node->namespacedName)) {
                $resolvedParent = $node->extends?->getAttribute('resolvedName');

                $classes[] = new DiscoveredClass(
                    className: $node->namespacedName->toString(),
                    parentClassName: $resolvedParent instanceof Node\Name
                        ? $resolvedParent->toString()
                        : $node->extends?->toString(),
                    abstract: $node->isAbstract(),
                    filePath: $filePath,
                );
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $value = $node->{$subNodeName};

                if ($value instanceof Node) {
                    $this->collect([$value], $filePath, $classes);

                    continue;
                }

                if (!\is_array($value)) {
                    continue;
                }

                $childNodes = [];

                foreach ($value as $childNode) {
                    if ($childNode instanceof Node) {
                        $childNodes[] = $childNode;
                    }
                }

                if ($childNodes !== []) {
                    $this->collect($childNodes, $filePath, $classes);
                }
            }
        }
    }
}
