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

final class PhpClassExtractor
{
    private Parser $parser;

    public function __construct()
    {
        $factory = new ParserFactory();

        $this->parser = \method_exists($factory, 'createForNewestSupportedVersion')
            ? $factory->createForNewestSupportedVersion()
            : $factory->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @return list<DiscoveredClass>
     */
    public function extract(string $filePath): array
    {
        $source = \file_get_contents($filePath);

        if ($source === false || $source === '') {
            return [];
        }

        if (!\str_contains($source, 'class') || !\str_contains($source, 'extends')) {
            return [];
        }

        try {
            $statements = $this->parser->parse($source);
        } catch (Error) {
            return [];
        }

        if ($statements === null) {
            return [];
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($statements);

        $classes = [];
        $this->collectClasses($statements, $filePath, $classes);

        return $classes;
    }

    /**
     * @param array<Node> $nodes
     * @param list<DiscoveredClass> $classes
     */
    private function collectClasses(array $nodes, string $filePath, array &$classes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Class_ && $node->name !== null) {
                $className = $node->namespacedName?->toString();

                if ($className !== null) {
                    $parentClassName = null;

                    if ($node->extends !== null) {
                        $resolvedParent = $node->extends->getAttribute('resolvedName');
                        $parentClassName = $resolvedParent instanceof Node\Name
                            ? $resolvedParent->toString()
                            : $node->extends->toString();
                    }

                    $classes[] = new DiscoveredClass(
                        className: $className,
                        parentClassName: $parentClassName,
                        abstract: $node->isAbstract(),
                        filePath: $filePath,
                    );
                }
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $value = $node->{$subNodeName};

                if ($value instanceof Node) {
                    $this->collectClasses([$value], $filePath, $classes);
                } elseif (\is_array($value)) {
                    $childNodes = \array_values(\array_filter(
                        $value,
                        static fn (mixed $item): bool => $item instanceof Node,
                    ));

                    $this->collectClasses($childNodes, $filePath, $classes);
                }
            }
        }
    }
}
