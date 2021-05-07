<?php

declare(strict_types=1);

namespace JsonMapper\Helpers;

use JsonMapper\Parser\UseNodeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class UseStatementHelper
{
    public static function getImports(\ReflectionClass $class): array
    {
        $filename = $class->getFileName();
        if ($filename === false) {
            throw new \RuntimeException("Class {$class->getName()} has no filename available");
        }

        if (!is_readable($filename)) {
            throw new \RuntimeException("Unable to read {$class->getFileName()}");
        }

        $contents = file_get_contents($filename);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read {$class->getFileName()}");
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($contents);

        if ($ast === null) {
            throw new \RuntimeException("Something went wrong when parsing {$class->getFileName()}");
        }

        $traverser = new NodeTraverser();
        $visitor = new UseNodeVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getImports();
    }
}
