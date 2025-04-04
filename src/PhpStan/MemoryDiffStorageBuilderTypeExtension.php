<?php

namespace DataDiff\PhpStan;

use DataDiff\Builders\MemoryDiffStorageBuilder;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PhpParser\Node\Expr\MethodCall;
use RuntimeException;

class MemoryDiffStorageBuilderTypeExtension implements DynamicMethodReturnTypeExtension {
	public function getClass(): string {
		return MemoryDiffStorageBuilder::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool {
		return in_array($methodReflection->getName(), [
			'addBoolKey', 'addIntKey', 'addFloatKey', 'addStringKey', 'addMoneyKey', 'addMd5Key',
			'addBoolValue', 'addIntValue', 'addFloatValue', 'addStringValue', 'addMoneyValue', 'addMd5Value',
			'addBoolExtra', 'addIntExtra', 'addFloatExtra', 'addStringExtra', 'addMoneyExtra', 'addMd5Extra',
		]);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type {
		return $this->prepareType(
			methodReflection: $methodReflection,
			methodCall: $methodCall,
			scope: $scope
		);
	}

	private function prepareType(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type {
		$args = $methodCall->getArgs();

		if (count($args) !== 1) {
			return $methodReflection->getVariants()[0]->getReturnType();
		}

		[$name, $categoryName] = match (true) {
			str_ends_with($methodReflection->getName(), 'Key') => [substr($methodReflection->getName(), 3, -3), 'key'],
			str_ends_with($methodReflection->getName(), 'Value') => [substr($methodReflection->getName(), 3, -5), 'value'],
			default => [substr($methodReflection->getName(), 3, -5), 'extra']
		};

		return $this->extendType(
			type: match ($name) {
				'Bool' => TypeCombinator::union(new BooleanType(), new NullType()),
				'Int' => TypeCombinator::union(new IntegerType(), new NullType()),
				'Float', 'Money' => TypeCombinator::union(new FloatType(), new NullType()),
				'String', 'Md5' => TypeCombinator::union(new StringType(), new NullType()),
				default => throw new RuntimeException("Can't determine type for {$name}")
			},
			extendCategory: $categoryName,
			methodReflection: $methodReflection,
			scope: $scope,
			args: $args
		);
	}

	/**
	 * @param 'key'|'value'|'extra' $extendCategory
	 * @param MethodReflection $methodReflection
	 * @param Scope $scope
	 * @param Arg[] $args
	 * @return Type|null
	 */
	public function extendType(Type $type, string $extendCategory, MethodReflection $methodReflection, Scope $scope, array $args): ?Type {
		$classTemplateMap = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap();

		/** @var ConstantStringType $keyType */
		// @phpstan-ignore-next-line
		$keyType = $scope->getType($args[0]->value);

		$typeK = $classTemplateMap->getType('TKeySpec');
		$typeV = $classTemplateMap->getType('TValueSpec');
		$typeE = $classTemplateMap->getType('TExtraSpec');

		$shapeExtension = new ConstantArrayType(
			[new ConstantStringType($keyType->getValue())],
			[$type]
		);

		// @phpstan-ignore-next-line
		if(!($typeK?->isConstantArray() && $typeK instanceof ConstantArrayType)) {
			return null;
		}

		// @phpstan-ignore-next-line
		if(!($typeV?->isConstantArray() && $typeV instanceof ConstantArrayType)) {
			return null;
		}

		// @phpstan-ignore-next-line
		if(!($typeE?->isConstantArray() && $typeE instanceof ConstantArrayType)) {
			return null;
		}

		if($extendCategory === 'key') {
			$mergedType = $this->mergeArrays($typeK, $shapeExtension);
			return new GenericObjectType($methodReflection->getDeclaringClass()->getName(), [$mergedType, $typeV, $typeE]);
		}

		if($extendCategory === 'value') {
			$mergedType = $this->mergeArrays($typeV, $shapeExtension);
			return new GenericObjectType($methodReflection->getDeclaringClass()->getName(), [$typeK, $mergedType, $typeE]);
		}

		$mergedType = $this->mergeArrays($typeE, $shapeExtension);
		return new GenericObjectType($methodReflection->getDeclaringClass()->getName(), [$typeK, $typeV, $mergedType]);

	}

	private function mergeArrays(ConstantArrayType $aType, ConstantArrayType $bType): Type {
		$constArrays1 = $aType->getConstantArrays();
		$constArrays2 = $bType->getConstantArrays();

		$mergedTypes = [];
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($constArrays1 as $arr1) {
			foreach ($constArrays2 as $arr2) {
				// Add from arr1
				self::setOffsetValueTypeOnBuilder($builder, $arr1);

				// Add/override from arr2
				self::setOffsetValueTypeOnBuilder($builder, $arr2);

				$mergedTypes[] = $builder->getArray();
			}
		}

		return TypeCombinator::union(...$mergedTypes);
	}

	private static function setOffsetValueTypeOnBuilder(ConstantArrayTypeBuilder $builder, ConstantArrayType $arrayType): void {
		foreach ($arrayType->getKeyTypes() as $i => $keyType) {
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
