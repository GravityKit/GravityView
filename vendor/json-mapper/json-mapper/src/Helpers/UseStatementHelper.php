<?php

declare(strict_types=1);

namespace JsonMapper\Helpers;

use JsonMapper\Parser\UseNodeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class UseStatementHelper
{
    /** @var string */
    private static $evaldCodeFileNameEnding = "eval()'d code";

    public static function getImports(\ReflectionClass $class): array
    {
        if (!$class->isUserDefined()) {
            return [];
        }

        $filename = $class->getFileName();
        if ($filename === false || \substr($filename, -13) === self::$evaldCodeFileNameEnding) {
            throw new \RuntimeException("Class {$class->getName()} has no filename available");
        }

        if (! \is_readable($filename)) {
            throw new \RuntimeException("Unable to read {$class->getFileName()}");
        }

        $contents = \file_get_contents($filename);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read {$class->getFileName()}");
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse($contents);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Something went wrong when parsing {$class->getFileName()}", 0, $e);
        }

        $traverser = new NodeTraverser();
        $visitor = new UseNodeVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getImports();
    }
}
