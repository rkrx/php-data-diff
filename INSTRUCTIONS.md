# Instructions

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
