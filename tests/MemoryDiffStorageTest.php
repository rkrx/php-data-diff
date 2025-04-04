<?php
namespace DataDiff;

use DataDiff\Builders\MemoryDiffStorageBuilder;
use DataDiff\Exceptions\InvalidSchemaException;
use DataDiff\Helpers\JsonSerializeTestObj;
use DataDiff\TestData\AnnotatedModel;
use DataDiff\Tools\Json;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class MemoryDiffStorageTest extends TestCase {
	/** @var MemoryDiffStorage<array{client_id: int|null}, array{description: string|null, total: float|null, a: int|null}, array{test: int|null}> */
	private MemoryDiffStorage $ds;

	/**
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ds = (new MemoryDiffStorageBuilderFactory())
			->createBuilder()
			->addIntKey('client_id')
			->addStringValue('description')
			->addMoneyValue('total')
			->addIntValue('a')
			->addIntExtra('test')
			->build();

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
	public function testUnchanged(): void {
		$ds = new MemoryDiffStorage(['key' => MemoryDiffStorage::INT], ['val' => MemoryDiffStorage::INT]);
		$ds->storeA()->addRow(['key' => 1, 'val' => 1]);
		$ds->storeA()->addRow(['key' => 2, 'val' => 1]);
		$ds->storeA()->addRow(['key' => 3, 'val' => 1]);
		$ds->storeB()->addRow(['key' => 1, 'val' => 1]);
		$ds->storeB()->addRow(['key' => 2, 'val' => 2]);
		$ds->storeB()->addRow(['key' => 3, 'val' => 2]);
		$res = iterator_to_array($ds->storeB()->getUnchanged());
		self::assertCount(1, $res);
		foreach($res as $value) {
			self::assertEquals(1, $value['key']);
			self::assertEquals('Unchanged key: 1', (string) $value);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testNew(): void {
		$res = $this->ds->storeA()->getNew();
		foreach($res as $key => $value) {
			self::assertEquals(501, $value['client_id']);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testNewStringRepresentation(): void {
//		foreach($this->ds->storeA()->getNew() as $row) {
//			self::assertEquals('New client_id: 501 (description: "Dies ist ein Test", total: 59.98999, a: null)', (string) $row);
//		}
		$ds = new MemoryDiffStorage(['a' => 'STRING'], ['b' => 'STRING']);
		$ds->storeA()->addRow(['a' => 1, 'b' => '0']);
		$ds->storeB()->addRow(['a' => 1, 'b' => null]);
		foreach($ds->storeB()->getChanged() as $row) {
			self::assertEquals('Changed a: 1 => b: "0" -> null', (string) $row);
		}
	}

	/**
	 */
	public function testChanges(): void {
		$res = $this->ds->storeA()->getChanged();
		foreach($res as $key => $value) {
			self::assertEquals(50, $value['client_id']);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testChangeStringRepresentation(): void {
		foreach($this->ds->storeA()->getChanged() as $row) {
			self::assertEquals('Changed client_id: 50 => total: "59.99" -> "60.00"', (string) $row);
		}
	}

	/**
	 */
	public function testMissing(): void {
		$res = $this->ds->storeA()->getMissing();
		foreach($res as $key => $value) {
			self::assertEquals(1, $value['client_id']);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testMissingStringRepresentation(): void {
		foreach($this->ds->storeA()->getMissing() as $row) {
			self::assertEquals('Missing client_id: 1 (description: "Dies ist ein Test", total: 59.98999, a: null)', (string) $row);
		}
	}

	/**
	 */
	public function testGetNewOrChangedOrMissing(): void {
		$res = $this->ds->storeA()->getNewOrChangedOrMissing();
		foreach($res as $idx => $value) {
			if($idx === 0) {
				self::assertEquals(['client_id' => 50, 'description' => 'Dies ist ein Test', 'total' => 60, 'a' => null, 'test' => 0], $value->getData());
			}
			if($idx === 1) {
				self::assertEquals(['client_id' => 501, 'description' => 'Dies ist ein Test', 'total' => 59.98999, 'a' => null, 'test' => 1], $value->getData());
			}
			if($idx === 2) {
				self::assertEquals(['client_id' => 1, 'description' => 'Dies ist ein Test', 'total' => 59.98999, 'a' => null, 'test' => 1], $value->getForeignData());
				return;
			}
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testHasAnyChnges(): void {
		self::assertTrue($this->ds->storeB()->hasAnyChanges());
		$emptyDs = new MemoryDiffStorage(['key' => MemoryDiffStorage::INT], ['val' => MemoryDiffStorage::INT]);
		self::assertFalse($emptyDs->storeB()->hasAnyChanges());
	}

	/**
	 */
	public function testGetData(): void {
		$ds = new MemoryDiffStorage(['a' => MemoryDiffStorage::INT], ['b' => MemoryDiffStorage::INT]);
		$ds->storeA()->addRow(['a' => 1, 'b' => 1, 'c' => 1]);
		$ds->storeA()->addRow(['a' => 2, 'b' => 2, 'c' => 1]);
		$ds->storeA()->addRow(['a' => 3, 'b' => 1, 'c' => 2]);
		$ds->storeB()->addRow(['a' => 1, 'b' => 1, 'c' => 1]);
		$ds->storeB()->addRow(['a' => 2, 'b' => 2, 'c' => 2]);
		$ds->storeB()->addRow(['a' => 3, 'b' => 2, 'c' => 1]);
		foreach($ds->storeB()->getChanged() as $row) {
			self::assertEquals(['a' => 3, 'b' => 2, 'c' => 1], $row->getData());
		}
		foreach($ds->storeB()->getChanged() as $row) {
			self::assertEquals(['b' => 2, 'c' => 1], $row->getData(['only-differences' => true]));
		}
		foreach($ds->storeB()->getChanged() as $row) {
			self::assertEquals(['a' => 3, 'b' => 2], $row->getData(['only-schema-fields' => true]));
		}
		foreach($ds->storeB()->getChanged() as $row) {
			self::assertEquals(['b' => 2], $row->getData(['only-differences' => true, 'only-schema-fields' => true]));
		}
	}

	/**
	 */
	public function testDuplicateBehavior(): void {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::INT,
		]);

		$ds->storeA()->addRow(['key' => 10, 'value' => 20]);
		$ds->storeA()->addRow(['key' => 10, 'value' => 30]);

		foreach($ds->storeA() as $key => $value) {
			self::assertEquals(10, $value['key']);
			self::assertEquals(30, $value['value']);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testGetArguments(): void {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::INT,
		]);

		for($i = 0; $i < 100; $i++) {
			$ds->storeA()->addRow(['key' => $i, 'value' => $i]);
			$ds->storeB()->addRow(['key' => $i * 2, 'value' => $i]);
		}

		self::assertCount(50, iterator_to_array($ds->storeB()->getNew()));
		self::assertCount(49, iterator_to_array($ds->storeB()->getChanged()));
		self::assertCount(99, iterator_to_array($ds->storeB()->getNewOrChanged()));
		self::assertCount(50, iterator_to_array($ds->storeB()->getMissing()));

		self::assertCount(10, iterator_to_array($ds->storeB()->getNew(['limit' => 10])));
		self::assertCount(10, iterator_to_array($ds->storeB()->getChanged(['limit' => 10])));
		self::assertCount(10, iterator_to_array($ds->storeB()->getNewOrChanged(['limit' => 10])));
		self::assertCount(10, iterator_to_array($ds->storeB()->getMissing(['limit' => 10])));
	}

	/**
	 */
	public function testDuplicateKeyHandlerBehavior(): void {
		/** @var MemoryDiffStorage<array{key: int}, array{value: int}, array{}> $ds */
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::INT,
		], [
			'duplicate_key_handler' => function (array $newData, array $oldData) {
				// @phpstan-ignore-next-line
				$newData['value'] = $newData['value'] + $oldData['value'];
				return $newData;
			}
		]);

		$ds->storeA()->addRow(['key' => 10, 'value' => 20]);
		$ds->storeA()->addRow(['key' => 10, 'value' => 30]);

		foreach($ds->storeA() as $key => $value) {
			self::assertEquals(10, $value['key']);
			self::assertEquals(50, $value['value']);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testMD5(): void {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::MD5,
		]);

		$ds->storeA()->addRow(['key' => 10, 'value' => 'Hello World']);

		foreach($ds->storeA() as $key => $value) {
			self::assertEquals(10, $value['key']);
			self::assertEquals('Hello World', $value['value']);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testTranslation(): void {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::MD5,
		]);

		$ds->storeA()->addRow(['id' => 10, 'greeting' => 'Hello World'], ['id' => 'key', 'greeting' => 'value']);

		foreach($ds->storeA() as $key => $value) {
			self::assertEquals(10, $value['key']);
			self::assertEquals('Hello World', $value['value']);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testRowStringRepresentation(): void {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::STR,
		], [
			'value' => MemoryDiffStorage::STR,
		]);

		$ds->storeA()->addRow(['key' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr', 'value' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr']);

		foreach($ds->storeA()->getNew() as $row) {
			self::assertEquals('New key: "Lorem ipsum dolor sit amet, consetetur sadipscing elitr" (value: "Lorem ipsum do...adipscing elitr")', (string) $row);
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testCount(): void {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::MD5,
		]);

		for($i=1; $i<=100; $i++) {
			$ds->storeA()->addRow(['key' => $i, 'value' => 'Hello World']);
		}

		self::assertEquals(100, $ds->storeA()->count());
		self::assertCount(100, $ds->storeA());
	}

	/**
	 */
	public function testGetDataOptionKeys(): void {
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
			self::assertEquals(['a' => 1, 'b' => 2], $row->getData(['keys' => ['a', 'b']]));
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testGetDataOptionIgnore(): void {
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
			self::assertEquals(['key' => 1, 'c' => 3], $row->getData(['ignore' => ['a', 'b']]));
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testLocalData(): void {
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
			self::assertEquals(['key' => 1], $row->getLocal()->getKeyData());
			self::assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $row->getLocal()->getValueData());
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testStdClass(): void {
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
		$ds->storeA()->addRows(array_map(static fn($entry) => (object) $entry, $srcData));
		$data = iterator_to_array($ds->storeA());
		self::assertEquals($srcData, $data);
	}

	/**
	 */
	public function testJsonSerialize(): void {
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
		$ds->storeA()->addRows(array_map(static fn($entry) => new JsonSerializeTestObj($entry), $srcData));
		$data = iterator_to_array($ds->storeA());
		self::assertEquals($srcData, $data);
	}

	/**
	 */
	public function testFalsySchema(): void {
		$this->expectException(InvalidSchemaException::class);
		new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'a' => 'INTEGR',
			'b' => MemoryDiffStorage::STR,
		]);
	}

	/**
	 */
	public function testCorruptUtf8(): void {
		$ds = new MemoryDiffStorage([
			'key' => MemoryDiffStorage::INT,
		], [
			'value' => MemoryDiffStorage::STR,
		]);
		self::assertEquals('null', Json::encode(null));
		self::assertEquals('123', Json::encode(123));
		self::assertEquals('123.45', Json::encode(123.45));
		self::assertEquals('"abc"', Json::encode('abc'));
		self::assertEquals('[1,2,3]', Json::encode([1,2,3]));
		self::assertEquals('"bbb?xxx"', Json::encode('bbb' . chr(197) . 'xxx'));
		$ds->storeA()->addRow(['key' => 1, 'value' => 'aaa' . chr(197)]);
		$ds->storeB()->addRow(['key' => 1, 'value' => 'bbb' . chr(197)]);
		foreach($ds->storeB()->getChanged() as $row) {
			self::assertEquals(1, $row['key']);
			self::assertEquals('bbb' . chr(197), $row['value']);
			self::assertEquals(['value' => ['local' => 'bbb' . chr(197), 'foreign' => 'aaa' . chr(197)]], $row->getDiff());
			return;
		}
		// @phpstan-ignore-next-line
		self::assertTrue(false);
	}

	/**
	 */
	public function testHandleMissingKeys(): void {
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
		call_user_func(static function () use ($ds) {
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
				self::assertEquals($expectedResult, $row->getDiff());
				return;
			}
			// @phpstan-ignore-next-line
			self::assertTrue(false);
		});
		call_user_func(static function () use ($ds) {
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
				self::assertEquals($expectedResult, $row->getDiff());
				return;
			}
			// @phpstan-ignore-next-line
			self::assertTrue(false);
		});
	}

	public function testMemoryDiffStoreFromModel(): void {
		$ds = MemoryDiffStorage::fromModelWithAttributes(AnnotatedModel::class);
		$model = new AnnotatedModel('abc', 5, true, 9.99, new DateTimeImmutable('2022-07-26 12:00:00'));
		self::assertEquals(['id'], $ds->getKeys());
		$ds->storeA()->addAnnotatedModel($model, AnnotatedModel::class);
	}

	private static function create(): MemoryDiffStorageBuilder {
		return (new MemoryDiffStorageBuilderFactory())->createBuilder();
	}
}
