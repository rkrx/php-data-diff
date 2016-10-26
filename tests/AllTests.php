<?php
namespace DataDiff;

use DataDiff\Exceptions\InvalidSchemaException;
use DataDiff\Helpers\JsonSerializeTestObj;

class AllTests extends \PHPUnit_Framework_TestCase {
	/** @var MemoryDiffStorage */
	private $ds = null;

	/**
	 */
	public function setUp() {
		parent::setUp();

		$this->ds = new MemoryDiffStorage([
			'client_id' => 'INT',
		], [
			'client_id' => 'INT',
			'description' => 'STRING',
			'total' => 'MONEY',
			'a' => 'INT',
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
			$this->assertEquals('Changed client_id: 50 => total: 59.99 -> 60.00', (string) $row);
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
	public function testHasAnyChnges() {
		$this->assertTrue($this->ds->storeB()->hasAnyChanges());
		$emptyDs = new MemoryDiffStorage(['key' => 'INT'], ['val' => 'INT']);
		$this->assertFalse($emptyDs->storeB()->hasAnyChanges());
	}

	/**
	 */
	public function testDuplicateBehavior() {
		$ds = new MemoryDiffStorage([
			'key' => 'INT',
		], [
			'value' => 'INT',
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
	public function testDuplicateKeyHandlerBehavior() {
		$ds = new MemoryDiffStorage([
			'key' => 'INT',
		], [
			'value' => 'INT',
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
			'key' => 'INT',
		], [
			'value' => 'MD5',
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
			'key' => 'INT',
		], [
			'value' => 'MD5',
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
			'key' => 'STRING',
		], [
			'value' => 'STRING',
		]);

		$ds->storeA()->addRow(['key' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'value' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ']);

		foreach($ds->storeA()->getNew() as $row) {
			$this->assertEquals('New key: "ABCDEFGHIJKLMNOPQRSTUVWXYZ" (value: "ABCDEFGHIJKLMNOP ...")', (string) $row);
			return;
		}
		$this->assertTrue(false);
	}

	/**
	 */
	public function testCount() {
		$ds = new MemoryDiffStorage([
			'key' => 'INT',
		], [
			'value' => 'MD5',
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
			'key' => 'INT',
		], [
			'a' => 'STRING',
			'b' => 'STRING',
			'c' => 'STRING',
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
			'key' => 'INT',
		], [
			'a' => 'STRING',
			'b' => 'STRING',
			'c' => 'STRING',
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
			'key' => 'INT',
		], [
			'a' => 'STRING',
			'b' => 'STRING',
			'c' => 'STRING',
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
			'key' => 'INT',
		], [
			'value' => 'STRING',
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
			'key' => 'INT',
		], [
			'value' => 'STRING',
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
			'key' => 'INT',
		], [
			'a' => 'INTEGR',
			'b' => 'STRING',
		]);
	}
}

