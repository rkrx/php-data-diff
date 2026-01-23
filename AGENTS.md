# Information for AI Agents

## About this project
- Purpose: key/value diff engine for structured data using SQLite-backed stores.
- Main entry points: `MemoryDiffStorage` (in-memory SQLite) and `FileDiffStorage` (SQLite file), both extending `DiffStorage`.
- Schema: define key fields (identity) and value fields (change detection); types `BOOL|INT|FLOAT|DOUBLE|MONEY|STR|MD5` normalize in SQLite (money=2dp, float=6dp, double=12dp, bool true/false, strings trimmed+hexed, md5 hashed).
- Stores: two paired stores `storeA()` and `storeB()`; querying from one store compares against the other (`getNew`, `getMissing`, `getChanged`, `getUnchanged`, `getNewOrChanged`, `getNewOrChangedOrMissing`).
- Rows: `addRow`/`addRows` accept arrays, `stdClass`, `JsonSerializable`; optional translation map for column names; extra fields are stored in row data but never affect comparison.
- Row API: `DiffStorageStoreRow` supports `getData`/`getForeignData`, `getLocal()->getKeyData`/`getValueData`, `getDiff`/`getDiffFormatted`, ArrayAccess, `__toString`; `getData` options: `keys`, `ignore`, `only-differences`, `only-schema-fields`.
- Duplicate keys: default is replace; optional `duplicate_key_handler` in options or per `addRow` to merge/resolve collisions.
- Attributes: `DataDiffProp` + `MemoryDiffStorage::fromModelWithAttributes()` + `addAnnotatedModel()` map PHP 8 attributes to schema and data (only `type`, `fieldName`, `key` used; `options` currently unused).
- Array helpers: `DiffValues` (value-only list diffs) and `DiffKeyValue` (associative diff; see below).

### Related classes

#### DiffStorageStore

- Full class name: `\DataDiff\DiffStorageStore`
- Backing store for one side of the diff; writes rows into SQLite and queries against the opposite store.
- `addRow`/`addRows` handle translation, `JsonSerializable`/`stdClass` inputs, and duplicate-key resolution.
- Query methods (`getNew`, `getMissing`, `getChanged`, `getUnchanged`, `getNewOrChanged`, `getNewOrChangedOrMissing`) yield `DiffStorageStoreRow` objects.

#### DiffStorageStoreRow

- Full class name: `\DataDiff\DiffStorageStoreRow`
- Wrapper returned from store queries; exposes both local and foreign row views.
- `getLocal()`/`getForeign()` return `DiffStorageStoreRowData` for each side.
- `getDiff`/`getDiffFormatted` summarize value changes; supports ArrayAccess and `__toString`.

#### DiffStorageStoreRowData

- Full class name: `\DataDiff\DiffStorageStoreRowData`
- Holds row data for one side plus its counterpart; can filter via `keys`, `ignore`, `only-differences`, `only-schema-fields`.
- `getKeyData`/`getValueData` split key vs value fields; `getDiff`/`getDiffFormatted` compare to foreign data.

#### MemoryDiffStorageBuilderFactory

- Full class name: `\DataDiff\MemoryDiffStorageBuilderFactory`
- Creates `MemoryDiffStorageBuilder` instances for fluent schema definitions.

#### MemoryDiffStorageBuilder

- Full class name: `\DataDiff\Builders\MemoryDiffStorageBuilder`
- Fluent schema builder with `add*Key`, `add*Value`, and `add*Extra` helpers; `build()` returns a `MemoryDiffStorage`.

## DiffKeyValue

- Full class name: `\DataDiff\DiffKeyValue`
- Static utilities for associative arrays keyed by IDs.
- `computeDifferencesInSecond($first, $second)`: returns `new` (in second only), `missing` (in first only), `changed` (shared keys with different values, from second), `unchanged` (shared keys with same values, from second).
- `computeChangedKeysInSecond(...)`: same buckets, but lists of keys only.
- `getEntriesMissingInSecond`/`getKeysMissingInSecondArray`: basic key diff.
- `getDifferencesInCommonKeysFromSecond`: changed entries for shared keys.
- `getUnchangedEntries`: equal entries for shared keys.

## DiffValues

- Full class name: `\DataDiff\DiffValues`
- Static utilities for plain value lists (non-associative or associative values, keys ignored).
- `computeDifferencesInSecond($first, $second)`: returns `new` (values in second not in first) and `missing` (values in first not in second).
- `getValuesMissingInSecondArray`: values present in first but not in second.
- `getValuesNewInSecondArray`: values present in second but not in first.

## Translate from Old Instantiation to Instantiation via Factory

### Old and deprecated method: 

```php
$ds = new MemoryDiffStorage([
    'username' => MemoryDiffStorage::STRING,
    'is_active' => MemoryDiffStorage::BOOL,
    'user_id' => MemoryDiffStorage::INT,
    'account_balance' => MemoryDiffStorage::FLOAT,
    'email' => MemoryDiffStorage::STRING,
    'price' => MemoryDiffStorage::MONEY,
    'checksum' => MemoryDiffStorage::MD5,
], [
    'is_verified' => MemoryDiffStorage::BOOL,
    'login_attempts' => MemoryDiffStorage::INT,
    'temperature' => MemoryDiffStorage::FLOAT,
    'first_name' => MemoryDiffStorage::STRING,
    'total_cost' => MemoryDiffStorage::MONEY,
    'file_hash' => MemoryDiffStorage::MD5,
]);
```

> [!INFO] Currently, there is no way to defined _Extra Columns_. _Extra Columns_ are values, that are not used in comparison, but are accessible later on. This way it is possible to carry extra values around needed in the application, but not needed in the comparison. For example: `product_id` is used for comparison, but `product_created_at` is not. The latter is an _Extra Column_.


### New method:

```php
use DataDiff\MemoryDiffStorageBuilderFactory;

$factory = new MemoryDiffStorageBuilderFactory();
$factory->createBuilder()
    ->addStringKey('username')
    ->addBoolKey('is_active')
    ->addIntKey('user_id')
    ->addFloatKey('account_balance')
    ->addStringKey('email')
    ->addMoneyKey('price')
    ->addMd5Key('checksum')
    ->addBoolValue('is_verified')
    ->addIntValue('login_attempts')
    ->addFloatValue('temperature')
    ->addStringValue('first_name')
    ->addMoneyValue('total_cost')
    ->addMd5Value('file_hash')
    ->addBoolExtra('has_discount')
    ->addIntExtra('order_quantity')
    ->addFloatExtra('shipping_fee')
    ->addStringExtra('notes')
    ->addMoneyExtra('tax')
    ->addMd5Extra('transaction_id')
    ->build();
```

## Translating from CREATE-TABLE-Schema to a Builder

### `CREATE TABLE`

```mysql
CREATE TABLE `trade__brands` (
    `brand_key` varchar(128) NOT NULL,
    `brand_name` varchar(256) NOT NULL,
    `brand_gpsr` text DEFAULT NULL,
    PRIMARY KEY (`brand_key`)
) ENGINE=InnoDB
```

### Builder-based instantiation

A `PRIMARY KEY` is the key here.

```php
use DataDiff\MemoryDiffStorageBuilderFactory;

$factory = new MemoryDiffStorageBuilderFactory();
$factory->createBuilder()
    ->addStringKey('brand_key')
    ->addStringValue('brand_name')
    ->addStringValue('brand_gpsr')
    ->build();
```
