# data-diff
A handy tool for comparing structured data quickly in a key-value manner

```PHP
<?php
use DataDiff\DiffStorage;

require 'vendor/autoload.php';

$ds = new DiffStorage();
$ds->storeA(['a' => 123]);
$ds->storeA(['b' => 123], 123);
$ds->storeA(['c' => 123], 123.3300);
$ds->storeA(['e1' => 123, 'e2' => 456]);

$ds->storeB(['b' => 123], 456);
$ds->storeB(['c' => 123], 123.33);
$ds->storeB(['d' => 123]);
$ds->storeB(['e2' => 456, 'e1' => 123]);

foreach($ds->getNewA() as $key => $value) {
	printf("New: %s => %s\n", json_encode($key), json_encode($value));
}

foreach($ds->getChangedA() as $key => $value) {
	printf("Changed: %s => %s\n", json_encode($key), json_encode($value));
}

foreach($ds->getRemovedB() as $key => $value) {
	printf("Removed: %s => %s\n", json_encode($key), json_encode($value));
}
```