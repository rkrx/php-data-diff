<?php

use DataDiff\MemoryDiffStorage;
use function PHPStan\Testing\assertType;
use DataDiff\MemoryDiffStorageBuilderFactory;

$factory = new MemoryDiffStorageBuilderFactory();

$builder = $factory->createBuilder();
assertType('DataDiff\\Builders\\MemoryDiffStorageBuilder<array{}, array{}, array{}>', $builder);

$builder = $factory->createBuilder()->addIntKey('id')->addStringKey('uuid');
assertType('DataDiff\\Builders\\MemoryDiffStorageBuilder<array{id: int|null, uuid: string|null}, array{}, array{}>', $builder);

$builder = $factory->createBuilder()->addIntKey('id')->addStringValue('name')->addStringValue('description');
assertType('DataDiff\\Builders\\MemoryDiffStorageBuilder<array{id: int|null}, array{name: string|null, description: string|null}, array{}>', $builder);

$builder = $factory->createBuilder()->addIntKey('id')->addStringValue('name')->addStringExtra('test');
assertType('DataDiff\\Builders\\MemoryDiffStorageBuilder<array{id: int|null}, array{name: string|null}, array{test: string|null}>', $builder);

$ds = $factory->createBuilder()->addIntKey('id')->addStringValue('name')->build();
assertType('DataDiff\\MemoryDiffStorage<array{id: int|null}, array{name: string|null}, array{}>', $ds);

$storeA = $ds->storeA();
assertType('DataDiff\\DiffStorageStore<array{id: int|null}, array{name: string|null}, array{id: int|null, name: string|null}>', $storeA);

$storeB = $ds->storeB();
assertType('DataDiff\\DiffStorageStore<array{id: int|null}, array{name: string|null}, array{id: int|null, name: string|null}>', $storeA);

$storeA->addRow(['id' => 1, 'name' => 'test']);

$ds = new MemoryDiffStorage(['id' => MemoryDiffStorage::INT], ['name' => MemoryDiffStorage::STR]);
assertType('DataDiff\\MemoryDiffStorage<array<string, mixed>, array<string, mixed>, array<string, mixed>>', $ds);

$ds = new MemoryDiffStorage(['id' => MemoryDiffStorage::INT], ['name' => MemoryDiffStorage::STR]);
$store = $ds->storeA();
assertType('DataDiff\\DiffStorageStore<array<string, mixed>, array<string, mixed>, array<string, mixed>>', $store);

$store = $ds->storeB();
assertType('DataDiff\\DiffStorageStore<array<string, mixed>, array<string, mixed>, array<string, mixed>>', $store);

