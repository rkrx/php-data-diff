# data-diff

[![Build Status](https://travis-ci.org/rkrx/data-diff.svg)](https://travis-ci.org/rkrx/data-diff)
[![Latest Stable Version](https://poser.pugx.org/rkr/data-diff/v/stable)](https://packagist.org/packages/rkr/data-diff)
[![License](https://poser.pugx.org/rkr/data-diff/license)](https://packagist.org/packages/rkr/data-diff)

A handy tool for comparing structured data quickly in a key-value manner

## composer

[See here](https://packagist.org/packages/rkr/data-diff)

## WTF

This component is interesting for you, if you have a lot of structured data to import into a local database (for example) and you don't want to overwrite everything on each run. Instead, you want to know, what has changed actually and act accordingly.

## Usage

In the beginning, you have two two-dimensional data-lists you want to compare. Normally, some of the columns of such a datalist are subjected to tell, what the actual difference in terms of added and missing rows is. And some columns tell, that only changes to existing rows have happened. You could also have columns, that would not cause any action, but their data will be needed in the subsequent processing.

Let's say, you have some article meta-data that should be imported from an external data source and you have that article-data in a local database. The external data should be imported into that local database and you want to act on (e.g. logging), whenever a dataset was added, removed or changed:

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

In each list, we have three data-rows here. But in each list you have a row, that is not available in the other list and the only common rows (`A Hairdryer;C0001` and `A Pencil;D0001`) have some differences in columns `price` and `stock` while the `name` is equal in both lists. Whatever is in the column `current-datetime` should not be compared, but in case of an insertion or an update it should be considered as well. The final goal is to bring all the changes from the external data-source to the local database. _It could be important to know that a `current-datetime` has changed while all other columns remain unchanged, but in this case I want to show how to handle a case, were this is not important._

An actual compare-result is computed comparing two distinct key-value lists. A comparison is made through three methods that could find added keys, missing keys and changed data where keys are equal. So, in order to get this information, you need to get an idea of how to say, that a particular row was added, removed or changed. This is not always a clear task and is subject to the data in question. In this example, I will set some rules that _could_ be different in your scenario.

In this example, we will only consider the `reference` to tell if a row is new in a list, or has been removed. So, the local database has a `reference` to a article `A0001` that is not included in the external data. Because of that, we want to remove `A0001` from our local data because of this. `B0001` is not present in our local data, so it should be added. The _Hairdryer_ has a different stock and the _Pencil_ has a slightly different price. Since, we locally store our prices with a decimal precision of two, the two pencil-prices are actually equal and the comparison should not report a change to the row `D0001`.

You first need to tell the `Storage` what exactly is a key and what is a value to define the schema of what the Storage should understand as a key-value-list. We don't want to transform the list, since the data is already fine.

So, let's give some meaning to the columns:

* The `reference`-Column tells us, that a particular row is present, or not. This is the identity of each row. A row could have more that one column as a identifier (like a `reference` and an `environment-id`) but in this case I have only one identifier.
* The `name`-Column should only be considered when a row is already present in the other list.
* The `price`-Column should only be considered when a row is already present in the other list.
* The `stock`-Column should only be considered when a row is already present in the other list.
* The `last-change`-Column should not be checked at all.

So when we build a key-value-array to make the actual comparison, the key-part is made of the `reference` and the value-part is represented by the columns `name`, `price` and `stock`.

The key-value-array of the first list would then look like this:

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

You have all information to match all differences between the two lists.

We have a special case here. The pencil has a price of `2.9499` in the first list. But since we only want to compare the price with a decimal precision of two, the prices are actually identical, because the computed price of `D0001` is in both cases `2.95`. This is where the `Schema` is this component comes in place.

When you define a `MemoryDiffStorage` you specify two schemas. One for the key-part and one for the value-part:

```PHP
<?php
use DataDiff\MemoryDiffStorage;

$ds = new MemoryDiffStorage([
	'reference' => 'STRING',
], [
	'name' => 'STRING',
	'price' => 'MONEY',
	'stock' => 'INT',
]);
```

A `MemoryDiffStorage` consists of two storages: `StoreA` and `StoreB`. You can insert as many rows with as many columns into each store as you want as long as the rows contain at least the columns defined in the schema. _The columns also need to have appropriate names since these names are not translated anyhow. This means, if your columns have different names in the database and the other source, you have to normalize those keys, before you put the data into each `Store`._

Here is a example:

```PHP
<?php
use DataDiff\MemoryDiffStorage;

require 'vendor/autoload.php';

$ds = new MemoryDiffStorage([
	'reference' => 'STRING',
], [
	'name' => 'STRING',
	'price' => 'MONEY',
	'stock' => 'INT',
]);

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

As you may notice, `D0001` is not present in the result-set. This is because the schema already normalized the decimal-precision of the column `price` so, that there did not occur any differences.

## Example

```PHP
<?php
use DataDiff\MemoryDiffStorage;

require 'vendor/autoload.php';

$ds = new MemoryDiffStorage([
	'client_id' => 'INT',
], [
	'description' => 'STRING',
	'total' => 'MONEY',
]);

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
