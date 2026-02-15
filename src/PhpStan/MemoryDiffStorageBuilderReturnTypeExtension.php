<?php

namespace DataDiff\PhpStan;

use DataDiff\Builders\MemoryDiffStorageBuilder;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class MemoryDiffStorageBuilderReturnTypeExtension implements DynamicMethodReturnTypeExtension {
	/**
	 * @var array<string, array{index: int, type: string}>
	 */
	private const METHOD_MAP = [
		'addBoolKey' => ['index' => 0, 'type' => 'bool'],
		'addIntKey' => ['index' => 0, 'type' => 'int'],
		'addFloatKey' => ['index' => 0, 'type' => 'float'],
		'addStringKey' => ['index' => 0, 'type' => 'string'],
		'addMoneyKey' => ['index' => 0, 'type' => 'money'],
		'addMd5Key' => ['index' => 0, 'type' => 'md5'],
		'addBoolValue' => ['index' => 1, 'type' => 'bool'],
		'addIntValue' => ['index' => 1, 'type' => 'int'],
		'addFloatValue' => ['index' => 1, 'type' => 'float'],
		'addStringValue' => ['index' => 1, 'type' => 'string'],
		'addMoneyValue' => ['index' => 1, 'type' => 'money'],
		'addMd5Value' => ['index' => 1, 'type' => 'md5'],
		'addBoolExtra' => ['index' => 2, 'type' => 'bool'],
		'addIntExtra' => ['index' => 2, 'type' => 'int'],
		'addFloatExtra' => ['index' => 2, 'type' => 'float'],
		'addStringExtra' => ['index' => 2, 'type' => 'string'],
		'addMoneyExtra' => ['index' => 2, 'type' => 'money'],
		'addMd5Extra' => ['index' => 2, 'type' => 'md5'],
	];

	public function getClass(): string {
		return MemoryDiffStorageBuilder::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool {
		return array_key_exists($methodReflection->getName(), self::METHOD_MAP);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type {
		$methodName = $methodReflection->getName();
		$methodSpec = self::METHOD_MAP[$methodName] ?? null;
		if($methodSpec === null) {
			return null;
		}

		return $this->updateForType($scope->getType($methodCall->var), $methodSpec, $methodCall, $scope);
	}

	/**
	 * @param array{index: int, type: string} $methodSpec
	 */
	private function updateForType(Type $targetType, array $methodSpec, MethodCall $methodCall, Scope $scope): ?Type {
		$templateTypes = $this->resolveBuilderTemplateTypes($targetType);
		if($templateTypes === null) {
			return null;
		}

		$args = $methodCall->getArgs();
		if(count($args) < 1) {
			return null;
		}

		$keyType = $this->resolveConstantKeyType($scope->getType($args[0]->value));
		if($keyType === null) {
			return null;
		}

		$fieldType = $this->resolveFieldType($methodSpec['type']);
		if($fieldType === null) {
			return null;
		}

		return $this->updateBuilderType($templateTypes, $methodSpec['index'], $keyType, $fieldType);
	}

	/**
	 * @return array<int, Type>|null
	 */
	private function resolveBuilderTemplateTypes(Type $type): ?array {
		$classNames = $type->getObjectClassNames();
		if(!in_array(MemoryDiffStorageBuilder::class, $classNames, true)) {
			return null;
		}

		foreach($type->getObjectClassReflections() as $classReflection) {
			if($classReflection->getName() !== MemoryDiffStorageBuilder::class) {
				continue;
			}

			$templateTypes = $classReflection->typeMapToList($classReflection->getActiveTemplateTypeMap());
			if(count($templateTypes) >= 3) {
				return $templateTypes;
			}
		}

		return null;
	}

	private function resolveConstantKeyType(Type $type): ?Type {
		$constants = $type->getConstantStrings();
		if(count($constants) === 1) {
			return $constants[0];
		}

		return null;
	}

	private function resolveFieldType(string $typeName): ?Type {
		switch($typeName) {
			case 'bool':
				return TypeCombinator::union(new BooleanType(), new ConstantIntegerType(0), new ConstantIntegerType(1), new NullType());
			case 'int':
				return TypeCombinator::addNull(new IntegerType());
			case 'float':
			case 'money':
				return TypeCombinator::addNull(new FloatType());
			case 'string':
			case 'md5':
				return TypeCombinator::addNull(new StringType());
			default:
				return null;
		}
	}

	/**
	 * @param array<int, Type> $templateTypes
	 */
	private function updateBuilderType(array $templateTypes, int $index, Type $keyType, Type $fieldType): Type {
		if(!isset($templateTypes[$index])) {
			return new GenericObjectType(MemoryDiffStorageBuilder::class, $templateTypes);
		}

		$templateTypes[$index] = $this->addKeyToArrayType($templateTypes[$index], $keyType, $fieldType);

		return new GenericObjectType(MemoryDiffStorageBuilder::class, $templateTypes);
	}

	private function addKeyToArrayType(Type $arrayType, Type $keyType, Type $valueType): Type {
		if($arrayType->isArray()->no()) {
			return $arrayType;
		}

		$constantArrays = $arrayType->getConstantArrays();
		if($constantArrays !== []) {
			$updated = [];
			foreach($constantArrays as $array) {
				$builder = ConstantArrayTypeBuilder::createFromConstantArray($array);
				$builder->disableArrayDegradation();
				$builder->setOffsetValueType($keyType, $valueType, false);
				$updated[] = $builder->getArray();
			}

			return TypeCombinator::union(...$updated);
		}

		return new ArrayType(
			TypeCombinator::union($arrayType->getIterableKeyType(), $keyType),
			TypeCombinator::union($arrayType->getIterableValueType(), $valueType)
		);
	}
}
