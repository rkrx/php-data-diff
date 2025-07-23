<?php

namespace DataDiff\PhpStan;

use DataDiff\DiffStorage;
use DataDiff\DiffStorageStore;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\DynamicMethodReturnTypeExtension;

class MemoryDiffStorageTypeExtension implements DynamicMethodReturnTypeExtension {
	public function getClass(): string {
		return DiffStorage::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool {
		return in_array($methodReflection->getName(), ['storeA', 'storeB']);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type {
		$classTemplateMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

		$typeT = $classTemplateMap->getType('TKeySpec');
		$typeU = $classTemplateMap->getType('TValueSpec');
		$typeE = $classTemplateMap->getType('TExtraSpec');

		// @phpstan-ignore-next-line
		if(!($typeT instanceof ConstantArrayType)) {
			return null;
		}

		// @phpstan-ignore-next-line
		if(!($typeU instanceof ConstantArrayType)) {
			return null;
		}

		// @phpstan-ignore-next-line
		if(!($typeE instanceof ConstantArrayType)) {
			return null;
		}

		$mergedType = $this->mergeArrays($typeT, $typeU, $typeE);

		return new GenericObjectType(DiffStorageStore::class, [$typeT, $typeU, $mergedType]);
	}

	private function mergeArrays(ConstantArrayType $aType, ConstantArrayType $bType, ConstantArrayType $cType): Type {
		$constArrays1 = $aType->getConstantArrays();
		$constArrays2 = $bType->getConstantArrays();
		$constArrays3 = $cType->getConstantArrays();

		$mergedTypes = [];
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach($constArrays1 as $arr1) {
			foreach($constArrays2 as $arr2) {
				foreach($constArrays3 as $arr3) {
					// Add from arr1
					self::setOffsetValueTypeOnBuilder($builder, $arr1);

					// Add/override from arr2
					self::setOffsetValueTypeOnBuilder($builder, $arr2);

					// Add/override from arr3
					self::setOffsetValueTypeOnBuilder($builder, $arr3);

					$mergedTypes[] = $builder->getArray();
				}
			}
		}

		return TypeCombinator::union(...$mergedTypes);
	}

	private static function setOffsetValueTypeOnBuilder(ConstantArrayTypeBuilder $builder, ConstantArrayType $arrayType): void {
		foreach($arrayType->getKeyTypes() as $i => $keyType) {
			$isOptionalKey = $arrayType->isOptionalKey($i);
			$valueType = $arrayType->getValueTypes()[$i];

			$builder->setOffsetValueType(
				$keyType,
				$valueType,
				$isOptionalKey
			);
		}
	}
}
