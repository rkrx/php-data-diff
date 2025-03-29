# data-diff

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rkrx/data-diff/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rkrx/data-diff/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/rkr/data-diff/v/stable)](https://packagist.org/packages/rkr/data-diff)
[![License](https://poser.pugx.org/rkr/data-diff/license)](https://packagist.org/packages/rkr/data-diff)

A handy tool for comparing structured data quickly in a key-value manner

## composer

[See here](https://packagist.org/packages/rkr/data-diff)

## Support for PHPStan

Add the following to your `phpstan.neon` file:

```neon
includes:
	- vendor/rkr/data-diff/extension.neon
```

## WTF

This component is useful if you have a large amount of structured data to import into a local database and you want to identify changes without overwriting everything on each run. Instead, you can determine what has actually changed and take appropriate actions.

## Usage

Initially, you have two two-dimensional data lists that you want to compare. Typically, some columns in such a data list indicate the actual differences in terms of new and missing rows. Other columns may indicate changes in existing rows. Additionally, some columns may not trigger any actions but their data could be necessary for subsequent processing.

For example, consider having some article metadata from an external data source that you would like to import into a local database. The external data should be imported into the local database, and you want to take action whenever a dataset is added, removed, or changed (e.g., logging).

External Data:

```
name;reference;price;stock;last-change
Some Notebook;B0001;1499.90;1254;2016-04-01T10:00:00+02:00
A Hairdryer;C0001;49.95;66;2016-04-01T10:00:00+02:00
A Pencil;D0001;2.9499;2481;2016-04-01T10:00:00+02:00
```

Local data:

```
name;reference;price;stock
A shiny Smartphone;A0001;519.99;213
A Hairdryer;C0001;49.95;12
A Pencil;D0001;2.95;2481
```

Each list contains three data rows. Both lists have a row that is not present in the other list, and the only common rows (`A Hairdryer;C0001` and `A Pencil;D0001`) exhibit differences in the `price` and `stock` columns, while the `name` column remains identical. The `current-datetime` column should not be compared, but it should be present in case of an insertion or update. The primary objective is to synchronize all changes from the external data source to the local database. Although it might be important to track changes in the `current-datetime` column while other columns remain unchanged, this example demonstrates how to handle a scenario where this is not a priority.

The comparison result is derived by comparing two distinct key-value lists. The comparison involves three methods to identify added keys, missing keys, and changed data where keys are equal. To achieve this, it is essential to determine whether a particular row was added, removed, or changed. This task can be complex and depends on the specific data. In this example, certain rules are established, which may vary in different scenarios.

In this example, only the `reference` column is used to determine if a row is new or has been removed. For instance, the local database contains a reference to an article `A0001` that is not present in the external data, necessitating its removal from the local data. Conversely, `B0001` is absent in the local data and should be added. The _Hairdryer_ has a different stock, and the _Pencil_ has a slightly different price. Since prices are stored locally with a decimal precision of two, the two pencil prices are considered equal, and the comparison should not report a change for the row `D0001`.

First, it is necessary to define what constitutes a key and a value for the `Storage` to understand the key-value list schema. The data is already in the correct format, so no transformation is required.

So, let's give some meaning to the columns:

* The `reference` column indicates whether a particular row is present or not. This serves as the unique identifier for each row. A row may have more than one identifier column (such as `reference` and `environment-id`), but in this case, there is only one identifier.
* The `name` column should only be considered when a row is already present in the other list.
* The `price` column should only be considered when a row is already present in the other list.
* The `stock` column should only be considered when a row is already present in the other list.
* The `last-change` column should not be checked at all.

Therefore, when constructing a key-value array for comparison, the key part is composed of the `reference` column, and the value part is represented by the `name`, `price`, and `stock` columns.

The key-value array of the first list would then appear as follows:

```
'B0001' => ['Some Notebook', 1499.90, 1254]
'C0001' => ['A Hairdryer', 49.95, 66]
'D0001' => ['A Pencil', 2.9499, 2481]
```

The key-value-array of the second-list would look like this:

```
'A0001' => ['A shiny Smartphone', 519.99, 213]
'C0001' => ['A Hairdryer', 49.95, 12]
'D0001' => ['A Pencil', 2.95, 2481]
```

Now, let's compare those arrays in three distinct ways:

What rows are present in the first list, but not in the second:

```
'B0001' => ['Some Notebook', 1499.90, 1254]
```

What rows are present in the second list, but not in the first:

```
'A0001' => ['A shiny Smartphone', 519.99, 213]
```

What rows are present in the first list, but have changed values compared to the second list?

```
'C0001' => ['A Hairdryer', 49.95, 66]
'D0001' => ['A Pencil', 2.9499, 2481]
```

You now have all the necessary information to identify the differences between the two lists.

Consider a special case: the pencil has a price of `2.9499` in the first list. However, since we only compare prices with a decimal precision of two, the prices are effectively identical, as the computed price for `D0001` is `2.95` in both cases. This is where the `Schema` component becomes relevant.

When defining a `MemoryDiffStorage`, you specify two schemas: one for the key part and one for the value part:

```PHP
<?php
use DataDiff\MemoryDiffStorageBuilderFactory;
use DataDiff\MemoryDiffStorage;

$factory = new MemoryDiffStorageBuilderFactory();
$ds = $factory->createBuilder()
    ->addStringKey('reference')
    ->addStringValue('name')
    ->addMoneyValue('price')
    ->addIntValue('stock')
    ->build();
```

A `MemoryDiffStorage` consists of two stores: `StoreA` and `StoreB`. You can insert as many rows with as many columns into each store as you want, provided the rows contain at least the columns defined in the schema. The columns must have appropriate names since these names are not translated automatically. However, you can specify a translation when adding rows using the second parameter of `addRow` and `addRows`. This means that if your columns have different names in the database and the other source, you must normalize those keys before inserting the data into each store.

Here is a example:

```PHP
<?php
use DataDiff\MemoryDiffStorageBuilderFactory;
use DataDiff\MemoryDiffStorage;

require 'vendor/autoload.php';

$factory = new MemoryDiffStorageBuilderFactory();
$ds = $factory->createBuilder()
    ->addStringKey('reference')
    ->addStringValue('name')
    ->addMoneyValue('price')
    ->addIntValue('stock')
    ->build();

$ds->storeA()->addRow(['name' => 'Some Notebook', 'reference' => 'B0001', 'price' => '1499.90', 'stock' => '1254', 'last-change' => '2016-04-01T10:00:00+02:00']);
$ds->storeA()->addRow(['name' => 'A Hairdryer', 'reference' => 'C0001', 'price' => '49.95', 'stock' => '66', 'last-change' => '2016-04-01T10:00:00+02:00']);
$ds->storeA()->addRow(['name' => 'A Pencil', 'reference' => 'D0001', 'price' => '2.9499', 'stock' => '2481', 'last-change' => '2016-04-01T10:00:00+02:00']);

$ds->storeB()->addRow(['name' => 'A shiny Smartphone', 'reference' => 'A0001', 'price' => '519.99', 'stock' => '213']);
$ds->storeB()->addRow(['name' => 'A Hairdryer', 'reference' => 'C0001', 'price' => '49.95', 'stock' => '12']);
$ds->storeB()->addRow(['name' => 'A Pencil', 'reference' => 'D0001', 'price' => '2.95', 'stock' => '2481']);
```

A good rule of thumb is to use `store a` for the data, you already have and to use `store b` for the data to compare to (e.g. the data to import from an external data-source).

Next, we can query one of the stores to find differences in the lists. Since `store a` holds our local data, we use `store b` to query the differences:

Get all data-sets that are present in `store b` but not in `store a`:

```PHP
foreach($ds->storeB()->getNew() as $row) {
	$data = $row->getData();
	printf("This row is not present in store a: %s\n", $data['reference']);
}
```

The result is `This row is not present in store b: B0001`.

Get all data-sets that are present in `store a` but not in `store b`:

```PHP
foreach($ds->storeB()->getMissing() as $row) {
	$data = $row->getForeignData();
	printf("This row is not present in store a: %s\n", $data['reference']);
}
```

The result is `This row is not present in store a: A0001`.

Get all changed data-sets:

```PHP
foreach($ds->storeB()->getChanged() as $row) {
	printf("This row is not present in store a: %s\n", $row->getDiffFormatted());
}
```

The result is `This row is not present in store a: stock: 12 -> 66, last-change:  -> 2016-04-01T10:00:00+02:00`.

Note that `D0001` is absent from the result set. This is because the schema has normalized the decimal precision of the `price` column, resulting in no detected differences.

Additionally, you can access the data divided into keys and values as defined in each schema. This is useful for constructing SQL statements, where keys can be used as `WHERE` conditions in an `UPDATE` statement, and values can represent the data to be changed (`SET`).

```
print_r($row->getLocal()->getKeyData());
print_r($row->getLocal()->getValueData());
```

## Example

```PHP
<?php
use DataDiff\MemoryDiffStorageBuilderFactory;
use DataDiff\MemoryDiffStorage;

require 'vendor/autoload.php';

$factory = new MemoryDiffStorageBuilderFactory();
$ds = $factory->createBuilder()
    ->addIntKey('client_id')
    ->addStringValue('description')
    ->addMoneyValue('total')
    ->build();

for($i=2; $i <= 501; $i++) {
	$row = ['client_id' => $i, 'description' => 'This is a test', 'total' => $i === 50 ? 60 : 59.98999, 'test' => $i % 2];
	$ds->storeA()->addRow($row);
}
for($i=1; $i <= 500; $i++) {
	$row = ['client_id' => $i, 'description' => 'This is a test', 'total' => 59.98999, 'test' => $i % 3];
	$ds->storeB()->addRow($row);
}

$res = $ds->storeA()->getNew();
foreach($res as $key => $value) {
	printf("Added  : %s\n", $value['client_id']);
}

$res = $ds->storeA()->getChanged();
foreach($res as $key => $value) {
	printf("Changed: %s\n", $value['client_id']);
}

$res = $ds->storeA()->getMissing();
foreach($res as $key => $value) {
	printf("Removed: %s\n", $value['client_id']);
}

echo "\n";

$res = $ds->storeA()->getChanged();
foreach($res as $key => $value) {
	print_r($value->getDiff());
}
```

Output:
```
Added  : 501
Changed: 50
Removed: 1

Array
(
    [total] => Array
        (
            [local] => 60
            [foreign] => 59.98999
        )

    [test] => Array
        (
            [local] => 0
            [foreign] => 2
        )

)
```
