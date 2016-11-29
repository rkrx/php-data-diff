<?php
namespace DataDiff;

use DataDiff\Helpers\JsonSerializeTestObj;
use DataDiff\Tools\StringTools;

class AllTests extends \PHPUnit_Framework_TestCase {
	/** @var MemoryDiffStorage */
	private $ds = null;

	/**
	 */
	public function setUp() {
		parent::setUp();

		$this->ds = new MemoryDiffStorage([
			'client_id' => MemoryDiffStorage::INT,
		], [
			'client_id' => MemoryDiffStorage::INT,
			'description' => MemoryDiffStorage::STR,
			'total' => MemoryDiffStorage::MONEY,
			'a' => MemoryDiffStorage::INT,
		]);

		for($i=2; $i <= 501; $i++) {
			$row = ['client_id' => $i, 'description' => 'Dies ist ein Test', 'total' => $i === 50 ? 60 : 59.98999, 'a' => null, 'test' => $i % 2];
			$this->ds->storeA()->addRow($row);
		}
		for($i=1; $i <= 500; $i++) {
			$row = ['client_id' => $i, 'description' => 'Dies ist ein Test', 'total' => 59.98999, 'a' => null, 'test' => $i % 3];
			$this->ds->storeB()->addRow($row);
		}
	}

	/**
	 */
	public function testNew() {
		$res = $this->ds->storeA()->getNew();
		foreach($res as $key => $value) {
			$this->assertEquals(501, $value['client_id']);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testNewStringRepresentation() {
		foreach($this->ds->storeA()->getNew() as $row) {
			$this->assertEquals('New client_id: 501 (client_id: 501, description: "Dies ist ein Test", total: 59.98999, a: null)', (string) $row);
		}
		$ds = new MemoryDiffStorage(['a' => 'STRING'], ['b' => 'STRING']);
		$ds->storeA()->addRow(['a' => 1, 'b' => '0']);
		$ds->storeB()->addRow(['a' => 1, 'b' => null]);
		foreach($ds->storeB()->getChanged() as $row) {
			$this->assertEquals('Changed a: 1 => b: "0" -> null', (string) $row);
		}
	}

	/**
	 */
	public function testChanges() {
		$res = $this->ds->storeA()->getChanged();
		foreach($res as $key => $value) {
			$this->assertEquals(50, $value['client_id']);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testChangeStringRepresentation() {
		foreach($this->ds->storeA()->getChanged() as $row) {
			$this->assertEquals('Changed client_id: 50 => total: 59.99 -> 60', (string) $row);
		}
	}

	/**
	 */
	public function testMissing() {
		$res = $this->ds->storeA()->getMissing();
		foreach($res as $key => $value) {
			$this->assertEquals(1, $value['client_id']);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testMissingStringRepresentation() {
		foreach($this->ds->storeA()->getMissing() as $row) {
			$this->assertEquals('Missing client_id: 1 (client_id: 1, description: "Dies ist ein Test", total: 59.98999, a: null)', (string) $row);
		}
	}

	/**
	 */
	public function testGetNewOrChangedOrMissing() {
		$res = $this->ds->storeA()->getNewOrChangedOrMissing();
		foreach($res as $idx => $value) {
			if($idx === 0) {
				$this->assertEquals(['client_id' => 50, 'description' => 'Dies ist ein Test', 'total' => 60, 'a' => null, 'test' => 0], $value->getData());
			}
			if($idx === 1) {
				$this->assertEquals(['client_id' => 501, 'description' => 'Dies ist ein Test', 'total' => 59.98999, 'a' => null, 'test' => 1], $value->getData());
			}
			if($idx === 2) {
				$this->assertEquals(['client_id' => 1, 'description' => 'Dies ist ein Test', 'total' => 59.98999, 'a' => null, 'test' => 1], $value->getForeignData());
				return;
			}
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testHasAnyChnges() {
		$this->assertTrue($this->ds->storeB()->hasAnyChanges());
		$emptyDs = new MemoryDiffStorage(['key' => MemoryDiffStorage::INT], ['val' => MemoryDiffStorage::INT]);
		$this->assertFalse($emptyDs->storeB()->hasAnyChanges());
	}

	/**
	 */
	public function testGetData() {
		$ds = new MemoryDiffStorage(['a' => MemoryDiffStorage::INT], ['b' => MemoryDiffStorage::INT]);
		$ds->storeA()->addRow(['a' => 1, 'b' => 1, 'c' => 1]);
		$ds->storeA()->addRow(['a' => 2, 'b' => 2, 'c' => 1]);
		$ds->storeA()->addRow(['a' => 3, 'b' => 1, 'c' => 2]);
		$ds->storeB()->addRow(['a' => 1, 'b' => 1, 'c' => 1]);
		$ds->storeB()->addRow(['a' => 2, 'b' => 2, 'c' => 2]);
		$ds->storeB()->addRow(['a' => 3, 'b' => 2, 'c' => 1]);
		foreach($ds->storeB()->getChanged() as $row) {
			$this->assertEquals(['a' => 3, 'b' => 2, 'c' => 1], $row->getData());
		}
		foreach($ds->storeB()->getChanged() as $row) {
			$this->assertEquals(['b' => 2, 'c' => 1], $row->getData(['only-differences' => true]));
		}
		foreach($ds->storeB()->getChanged() as $row) {
			$this->assertEquals(['a' => 3, 'b' => 2], $row->getData(['only-schema-fields' => true]));
		}
		foreach($ds->storeB()->getChanged() as $row) {
			$this->assertEquals(['b' => 2], $row->getData(['only-differences' => true, 'only-schema-fields' => true]));
		}
	}

	/**
	 */
	public function testDuplicateBehavior() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::INT,
		]);

		$ds->storeA()->addRow(['key' => 10, 'value' => 20]);
		$ds->storeA()->addRow(['key' => 10, 'value' => 30]);

		foreach($ds->storeA() as $key => $value) {
			$this->assertEquals(10, $value['key']);
			$this->assertEquals(30, $value['value']);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testGetArguments() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::INT,
		]);

		for($i = 0; $i < 100; $i++) {
			$ds->storeA()->addRow(['key' => $i, 'value' => $i]);
			$ds->storeB()->addRow(['key' => $i * 2, 'value' => $i]);
		}
		
		$this->assertCount(50, iterator_to_array($ds->storeB()->getNew()));
		$this->assertCount(49, iterator_to_array($ds->storeB()->getChanged()));
		$this->assertCount(99, iterator_to_array($ds->storeB()->getNewOrChanged()));
		$this->assertCount(50, iterator_to_array($ds->storeB()->getMissing()));
		
		$this->assertCount(10, iterator_to_array($ds->storeB()->getNew(['limit' => 10])));
		$this->assertCount(10, iterator_to_array($ds->storeB()->getChanged(['limit' => 10])));
		$this->assertCount(10, iterator_to_array($ds->storeB()->getNewOrChanged(['limit' => 10])));
		$this->assertCount(10, iterator_to_array($ds->storeB()->getMissing(['limit' => 10])));
	}

	/**
	 */
	public function testDuplicateKeyHandlerBehavior() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::INT,
		], [
			'duplicate_key_handler' => function (array $newData, array $oldData) {
				$newData['value'] = $newData['value'] + $oldData['value'];
				return $newData;
			}
		]);

		$ds->storeA()->addRow(['key' => 10, 'value' => 20]);
		$ds->storeA()->addRow(['key' => 10, 'value' => 30]);

		foreach($ds->storeA() as $key => $value) {
			$this->assertEquals(10, $value['key']);
			$this->assertEquals(50, $value['value']);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testMD5() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::MD5,
		]);

		$ds->storeA()->addRow(['key' => 10, 'value' => 'Hello World']);

		foreach($ds->storeA() as $key => $value) {
			$this->assertEquals(10, $value['key']);
			$this->assertEquals('Hello World', $value['value']);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testTranslation() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::MD5,
		]);

		$ds->storeA()->addRow(['id' => 10, 'greeting' => 'Hello World'], ['id' => 'key', 'greeting' => 'value']);

		foreach($ds->storeA() as $key => $value) {
			$this->assertEquals(10, $value['key']);
			$this->assertEquals('Hello World', $value['value']);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testRowStringRepresentation() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::STR,
		], [
			'value' => MemoryDiffStorage::STR,
		]);

		$ds->storeA()->addRow(['key' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr', 'value' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr']);

		foreach($ds->storeA()->getNew() as $row) {
			$this->assertEquals('New key: "Lorem ipsum dolor sit amet, consetetur sadipscing elitr" (value: "Lorem ipsum do...adipscing elitr")', (string) $row);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testCount() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::MD5,
		]);

		for($i=1; $i<=100; $i++) {
			$ds->storeA()->addRow(['key' => $i, 'value' => 'Hello World']);
		}

		$this->assertEquals(100, $ds->storeA()->count());
		$this->assertEquals(100, count($ds->storeA()));
	}

	/**
	 */
	public function testGetDataOptionKeys() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'a' => MemoryDiffStorage::STR,
			'b' => MemoryDiffStorage::STR,
			'c' => MemoryDiffStorage::STR,
		]);
		$ds->storeA()->addRow(['key' => 1, 'a' => 1, 'b' => 2, 'c' => 3]);
		$rows = $ds->storeA()->getNew();
		foreach($rows as $row) {
			$this->assertEquals(['a' => 1, 'b' => 2], $row->getData(['keys' => ['a', 'b']]));
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testGetDataOptionIgnore() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'a' => MemoryDiffStorage::STR,
			'b' => MemoryDiffStorage::STR,
			'c' => MemoryDiffStorage::STR,
		]);
		$ds->storeA()->addRow(['key' => 1, 'a' => 1, 'b' => 2, 'c' => 3]);
		$rows = $ds->storeA()->getNew();
		foreach($rows as $row) {
			$this->assertEquals(['key' => 1, 'c' => 3], $row->getData(['ignore' => ['a', 'b']]));
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testLocalData() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'a' => MemoryDiffStorage::STR,
			'b' => MemoryDiffStorage::STR,
			'c' => MemoryDiffStorage::STR,
		]);
		$ds->storeA()->addRow(['key' => 1, 'a' => 1, 'b' => 2, 'c' => 3]);
		$rows = $ds->storeA()->getNew();
		foreach($rows as $row) {
			$this->assertEquals(['key' => 1], $row->getLocal()->getKeyData());
			$this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $row->getLocal()->getValueData());
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testStdClass() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::STR,
		]);
		$srcData = [
			['key' => 1, 'value' => 'TEST1'],
			['key' => 2, 'value' => 'TEST2'],
			['key' => 3, 'value' => 'TEST3']
		];
		$ds->storeA()->addRows(array_map(function ($entry) { return (object) $entry; }, $srcData));
		$data = iterator_to_array($ds->storeA());
		$this->assertEquals($srcData, $data);
	}

	/**
	 */
	public function testJsonSerialize() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::STR,
		]);
		$srcData = [
			['key' => 1, 'value' => 'TEST1'],
			['key' => 2, 'value' => 'TEST2'],
			['key' => 3, 'value' => 'TEST3']
		];
		$ds->storeA()->addRows(array_map(function ($entry) { return new JsonSerializeTestObj($entry); }, $srcData));
		$data = iterator_to_array($ds->storeA());
		$this->assertEquals($srcData, $data);
	}

	/**
	 */
	public function testFalsySchema() {
		$this->setExpectedException("DataDiff\\Exceptions\\InvalidSchemaException");
		new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'a' => 'INTEGR',
			'b' => MemoryDiffStorage::STR,
		]);
	}

	/**
	 */
	public function testCorruptUtf8() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::STR,
		]);
		$this->assertEquals('null', StringTools::jsonEncode(null));
		$this->assertEquals('123', StringTools::jsonEncode(123));
		$this->assertEquals('123.45', StringTools::jsonEncode(123.45));
		$this->assertEquals('"abc"', StringTools::jsonEncode('abc'));
		$this->assertEquals('[1,2,3]', StringTools::jsonEncode([1,2,3]));
		$this->assertEquals('"bbb' . chr(0xEF) . chr(0xBF) . chr(0xBD) . 'xx"', StringTools::jsonEncode('bbb' . chr(197) . 'xxx'));
		$ds->storeA()->addRow(['key' => 1, 'value' => 'aaa' . chr(197)]);
		$ds->storeB()->addRow(['key' => 1, 'value' => 'bbb' . chr(197)]);
		foreach($ds->storeB()->getChanged() as $row) {
			$this->assertEquals(1, $row['key']);
			$this->assertEquals('bbb' . chr(197), $row['value']);
			$this->assertEquals(['value' => ['local' => 'bbb' . chr(197), 'foreign' => 'aaa' . chr(197)]], $row->getDiff());
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testHandleMissingKeys() {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'a' => MemoryDiffStorage::INT,
			'b' => MemoryDiffStorage::INT,
			'c' => MemoryDiffStorage::INT,
		]);
		$ds->storeA()->addRow([
			'key' => 1,
			'a' => 1,
			'b' => 2,
			'c' => 3,
		]);
		$ds->storeB()->addRow([
			'key' => 1,
			'a' => 3,
			'b' => 2,
		]);
		call_user_func(function () use ($ds) {
			foreach($ds->storeB()->getChanged() as $row) {
				$expectedResult = [
					'a' => [
						'local' => 3,
						'foreign' => 1,
					],
					'c' => [
						'local' => null,
						'foreign' => 3,
					]
				];
				$this->assertEquals($expectedResult, $row->getDiff());
				return;
			}
			$this->assertTrue(false);
		});
		call_user_func(function () use ($ds) {
			foreach($ds->storeA()->getChanged() as $row) {
				$expectedResult = [
					'a' => [
						'local' => 1,
						'foreign' => 3,
					],
					'c' => [
						'local' => 3,
						'foreign' => null,
					]
				];
				$this->assertEquals($expectedResult, $row->getDiff());
				return;
			}
			$this->assertTrue(false);
		});
	}
}

