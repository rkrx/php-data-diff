<?php

namespace DataDiff\PhpStan;

use DataDiff\DiffStorage;
use DataDiff\DiffStorageStore;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class DiffStorageStoreReturnTypeExtension implements DynamicMethodReturnTypeExtension {
	public function getClass(): string {
		return DiffStorage::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool {
		return in_array($methodReflection->getName(), ['storeA', 'storeB'], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type {
		$templateTypes = $this->resolveStorageTemplateTypes($scope->getType($methodCall->var));
		if($templateTypes === null) {
			return null;
		}

		if(count($templateTypes) < 3) {
			return null;
		}

		$keySpec = $templateTypes[0];
		$valueSpec = $templateTypes[1];
		$extraSpec = $templateTypes[2];

		$valueFullSpec = $this->mergeArrayTypes($valueSpec, $extraSpec);
		$fullSpec = $this->mergeArrayTypes($keySpec, $valueFullSpec);

		return new GenericObjectType(DiffStorageStore::class, [$keySpec, $valueFullSpec, $fullSpec]);
	}

	/**
	 * @return array<int, Type>|null
	 */
	private function resolveStorageTemplateTypes(Type $type): ?array {
		foreach($type->getObjectClassReflections() as $classReflection) {
			if($classReflection->getName() !== DiffStorage::class && !$classReflection->isSubclassOf(DiffStorage::class)) {
				continue;
			}

			$templateTypes = $classReflection->typeMapToList($classReflection->getActiveTemplateTypeMap());
			if(count($templateTypes) >= 3) {
				return $templateTypes;
			}
		}

		return null;
	}

	private function mergeArrayTypes(Type $left, Type $right): Type {
		$leftConstantArrays = $left->getConstantArrays();
		$rightConstantArrays = $right->getConstantArrays();
		if($leftConstantArrays !== [] && $rightConstantArrays !== []) {
			return $this->mergeConstantArrays($leftConstantArrays, $rightConstantArrays);
		}

		if($left->isArray()->yes() && $right->isArray()->yes()) {
			return new ArrayType(
				TypeCombinator::union($left->getIterableKeyType(), $right->getIterableKeyType()),
				TypeCombinator::union($left->getIterableValueType(), $right->getIterableValueType())
			);
		}

		return TypeCombinator::union($left, $right);
	}

	/**
	 * @param ConstantArrayType[] $leftArrays
	 * @param ConstantArrayType[] $rightArrays
	 */
	private function mergeConstantArrays(array $leftArrays, array $rightArrays): Type {
		$mergedTypes = [];
		foreach($leftArrays as $left) {
			foreach($rightArrays as $right) {
				$builder = ConstantArrayTypeBuilder::createFromConstantArray($left);
				$builder->disableArrayDegradation();
				$this->appendConstantArray($builder, $right);
				$mergedTypes[] = $builder->getArray();
			}
		}

		return TypeCombinator::union(...$mergedTypes);
	}

	private function appendConstantArray(ConstantArrayTypeBuilder $builder, ConstantArrayType $array): void {
		foreach($array->getKeyTypes() as $index => $keyType) {
			$builder->setOffsetValueType($keyType, $array->getValueTypes()[$index], $array->isOptionalKey($index));
		}
	}
}
