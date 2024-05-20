<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Analyzer;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use function array_merge;
use function count;

readonly class Psr1Analyzer
{
    public function __construct(
    ) {
    }

    /** @param array<Stmt> $stmts */
    public function analyze(string $name, string $path, array $stmts): Psr1AnalyzeResult
    {
        $violations = [];

        [$declarations, $sideEffects] = $this->parseSideEffects($stmts);
        if (count($declarations) > 0 && count($sideEffects) > 0) {
            $violations[] = Psr1Violation::MIXED_SIDE_EFFECTS;
        }

        return new Psr1AnalyzeResult($name, $path, $violations);
    }

    /**
     * @param array<Stmt> $stmts
     * @return array{list<class-string<Node>>, list<class-string<Node>>}
     */
    public function parseSideEffects(array $stmts): array
    {
        $declarations = [];
        $sideEffects = [];
        foreach ($stmts as $stmt) {
            $nodeName = $stmt::class;
            if ($stmt instanceof Stmt\Use_
                || $stmt instanceof Stmt\Return_
            ) {
                continue;
            }

            if ($stmt instanceof Stmt\Declare_
                || $stmt instanceof Stmt\Namespace_
                || $stmt instanceof Stmt\Block
            ) {
                if ($stmt->stmts === null) {
                    continue;
                }
                [$insideDeclarations, $insideSideEffects] = $this->parseSideEffects($stmt->stmts);
                $declarations = array_merge($declarations, $insideDeclarations);
                $sideEffects = array_merge($sideEffects, $insideSideEffects);
            } elseif ($stmt instanceof Stmt\Function_ ||
                      $stmt instanceof Stmt\Class_ ||
                      $stmt instanceof Stmt\Enum_ ||
                      $stmt instanceof Stmt\Trait_ ||
                      $stmt instanceof Stmt\Interface_
            ) {
                $declarations[] = $nodeName;
            } elseif ($stmt instanceof Stmt\If_) {
                [$insideDeclarations, $insideSideEffects] = $this->parseSideEffects($stmt->stmts);
                $declarations = array_merge($declarations, $insideDeclarations);
                $sideEffects = array_merge($sideEffects, $insideSideEffects);
            } else {
                $sideEffects[] = $nodeName;
            }
        }

        return [$declarations, $sideEffects];
    }
}
