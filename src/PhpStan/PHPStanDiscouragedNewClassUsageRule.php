<?php

namespace DataDiff\PhpStan;

use DataDiff\Builders\MemoryDiffStorageBuilder;
use DataDiff\FileDiffStorage;
use DataDiff\MemoryDiffStorage;
use DataDiff\MemoryDiffStorageBuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<New_>
 */
class PHPStanDiscouragedNewClassUsageRule implements Rule {
	public function __construct() {}

	public function getClass(): string {
		return '*';
	}

	public function getNodeType(): string {
		return Node\Expr\New_::class;
	}

	/**
	 * @param Node $node
	 * @param Scope $scope
	 * @return IdentifierRuleError[]
	 */
	public function processNode(Node $node, Scope $scope): array {
		if($node instanceof Node\Expr\New_) {
			if($node->class instanceof Node\Stmt\Class_) {
				$result = [];
				if($node->class->extends !== null) {
					$result = $this->processData($node->class->extends->toString(), $scope);
				}
				foreach($node->class->implements as $class) {
					$result = [...$result, ...$this->processData($class->toString(), $scope)];
				}

				return $result;
			}

			if($node->class instanceof Node\Expr\Variable) {
				return $this->processData($node->class->name, $scope);
			}

			// @phpstan-ignore-next-line
			return $this->processData($node->class->toString(), $scope);
		}

		return [];
	}

	/**
	 * @param Node\Expr|string $className
	 * @param Scope $scope
	 * @return IdentifierRuleError[]
	 */
	protected function processData(Node\Expr|string $className, Scope $scope): array {
		$scopes = [];
		$currentScope = $scope;
		while($currentScope !== null) {
			$scopeName = $currentScope->getClassReflection()?->getName();
			if($scopeName !== null) {
				$scopes[] = $scopeName;
			}
			$currentScope = $currentScope->getParentScope();
		}

		$ignoreOccurrencesInClasses = [
			self::class,
			MemoryDiffStorageBuilder::class,
		];

		foreach($ignoreOccurrencesInClasses as $ignoreOccurrencesInClass) {
			foreach($scopes as $scopeClassName) {
				if($scopeClassName === $ignoreOccurrencesInClass) {
					return [];
				}
			}
		}

		foreach([MemoryDiffStorage::class, FileDiffStorage::class] as $discouragedClassName) {
			if($className === $discouragedClassName) {
				$message = $this->sprintf(
					$scope,
					'Using %s is not allowed. Use %s via %s instead.',
					$discouragedClassName,
					MemoryDiffStorageBuilder::class,
					MemoryDiffStorageBuilderFactory::class
				);

				return [
					RuleErrorBuilder::message($message)
						->identifier('DiffStorageRules.discouragedClassName')
						->build(),
				];
			}
		}

		return [];
	}

	/**
	 * @param Scope $scope
	 * @param string $format
	 * @param scalar ...$params
	 * @return string
	 */
	private function sprintf(Scope $scope, string $format, ...$params): string {
		return vsprintf($format, $params);
	}
}
