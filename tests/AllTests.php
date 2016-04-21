<?php
namespace DataDiff;

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
		]);

		for($i=2; $i <= 501; $i++) {
			$row = ['client_id' => $i, 'description' => 'Dies ist ein Test', 'total' => $i === 50 ? 60 : 59.98999, 'test' => $i % 2];
			$this->ds->storeA()->addRow($row);
		}
		for($i=1; $i <= 500; $i++) {
			$row = ['client_id' => $i, 'description' => 'Dies ist ein Test', 'total' => 59.98999, 'test' => $i % 3];
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
		], function (array $newData, array $oldData) {
			$newData['value'] = $newData['value'] + $oldData['value'];
			return $newData;
		});

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
}

