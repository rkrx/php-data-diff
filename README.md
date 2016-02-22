# data-diff
A handy tool for comparing structured data quickly in a key-value manner

## composer

[See here](https://packagist.org/packages/rkr/data-diff)

## Example

```PHP
<?php
use DataDiff\DiffStorage;

require 'vendor/autoload.php';

$ds = new DiffStorage([
	'client_id' => 'integer',
], [
	'client_id' => 'integer',
	'description' => 'string',
	'total' => 'money',
]);

for($i=2; $i <= 501; $i++) {
	$row = ['client_id' => $i, 'description' => 'Dies ist ein Test', 'total' => $i === 50 ? 60 : 59.98999, 'test' => $i % 2];
	$ds->storeA()->addRow($row);
}
for($i=1; $i <= 500; $i++) {
	$row = ['client_id' => $i, 'description' => 'Dies ist ein Test', 'total' => 59.98999, 'test' => $i % 3];
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