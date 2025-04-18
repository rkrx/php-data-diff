<?php

namespace DataDiff\Tools;

use DataDiff\Attributes\DataDiffProp;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;

/**
 * @internal
 */
class ModelTools {
	/** @var array<string, array<int, array{ReflectionProperty, DataDiffProp}>> */
	private static array $classPropertyCache = [];

	/**
	 * @param class-string $fqClassName
	 * @return array{array<string, string>, array<string, string>}
	 */
	public static function getSchemaFromModel(string $fqClassName): array {
		$keySchema = [];
		$valueSchema = [];
		foreach(self::getAnnotationsFromClass($fqClassName) as [$property, $attribute]) {
			$key = $attribute->fieldName ?? $property->getName();
			/** @var DataDiffProp $attribute */
			if($attribute->isKey) {
				$keySchema[$key] = $attribute->type;
			} else {
				$valueSchema[$key] = $attribute->type;
			}
		}

		return [$keySchema, $valueSchema];
	}

	/**
	 * @template T of object
	 * @param object $model
	 * @param class-string<T>|null $className
	 * @return array<string, mixed>
	 */
	public static function getValuesFromModel($model, ?string $className = null) {
		$values = [];
		foreach(self::getAnnotationsFromObject($model, $className) as [$property, $attribute]) {
			$key = $attribute->fieldName ?? $property->getName();
			$value = $property->getValue($model);
			$values[$key] = $value;
		}

		return $values;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return array<int, array{ReflectionProperty, DataDiffProp}>
	 */
	private static function getAnnotationsFromClass(string $className): array {
		if(!array_key_exists($className, self::$classPropertyCache)) {
			$refClass = new ReflectionClass($className);
			$result = self::getAnnotationsFromClassOrObject($refClass);
			self::$classPropertyCache[$className] = $result;
		}

		return self::$classPropertyCache[$className];
	}

	/**
	 * @template T of object
	 * @param object $model
	 * @param null|class-string<T> $className
	 * @return array<int, array{ReflectionProperty, DataDiffProp}>
	 */
	private static function getAnnotationsFromObject($model, ?string $className = null): array {
		if($className !== null && array_key_exists($className, self::$classPropertyCache)) {
			return self::$classPropertyCache[$className];
		}
		$refObj = new ReflectionObject($model);
		$result = self::getAnnotationsFromClassOrObject($refObj);
		if($className !== null && !array_key_exists($className, self::$classPropertyCache)) {
			self::$classPropertyCache[$className] = $result;
		}

		return $result;
	}

	/**
	 * @template T of object
	 * @param ReflectionObject|ReflectionClass<T> $input
	 * @return array<int, array{ReflectionProperty, DataDiffProp}>
	 */
	private static function getAnnotationsFromClassOrObject(ReflectionObject|ReflectionClass $input): array {
		$result = [];
		foreach($input->getProperties() as $refProperty) {
			// @phpstan-ignore-next-line
			if(!method_exists($refProperty, 'getAttributes')) {
				continue;
			}
			foreach($refProperty->getAttributes() as $refAttribute) {
				$attribute = $refAttribute->newInstance();
				if($attribute instanceof DataDiffProp) {
					$result[] = [$refProperty, $attribute];
				}
			}
		}

		return $result;
	}
}
