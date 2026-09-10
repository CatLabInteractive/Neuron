<?php

namespace Neuron\Tests;

use Neuron\DB\Database;
use Neuron\Exceptions\InvalidParameter;
use Neuron\RateLimit\DatabaseStore;
use Neuron\Tests\RateLimit\RecordingDatabase;
use PHPUnit\Framework\TestCase;

class RateLimitDatabaseStoreTest extends TestCase
{
	/** @var RecordingDatabase */
	private $db;

	protected function setUp (): void
	{
		$this->db = new RecordingDatabase ();
		Database::setInstance ($this->db);
	}

	protected function tearDown (): void
	{
		Database::setInstance (null);
	}

	public function testAttemptInsertsIgnoreThenConditionallyUpdates ()
	{
		$this->db->affectedRows = 1;
		$store = new DatabaseStore ();
		$this->assertTrue ($store->attempt ("user:1", 9960, 100, 7));

		$this->assertCount (2, $this->db->queries);
		$this->assertStringStartsWith ('INSERT IGNORE INTO `neuron_rate_limits`', $this->db->queries[0]);
		$this->assertStringContainsString ("'user:1'", $this->db->queries[0]);
		$this->assertStringContainsString ('9960', $this->db->queries[0]);
		$this->assertSame (
			"UPDATE `neuron_rate_limits` SET hits = hits + 7 WHERE rl_key = 'user:1' AND window_start = 9960 AND hits + 7 <= 100",
			$this->db->queries[1]
		);
	}

	public function testRefusedWhenNoRowWasUpdated ()
	{
		$this->db->affectedRows = 0;
		$this->assertFalse ((new DatabaseStore ())->attempt ('k', 9960, 1, 1));
	}

	public function testTableNameIsConfigurable ()
	{
		$this->db->affectedRows = 1;
		(new DatabaseStore ('rate_limit_counters'))->attempt ('k', 9960, 1, 1);
		$this->assertStringStartsWith ('INSERT IGNORE INTO `rate_limit_counters`', $this->db->queries[0]);
		$this->assertStringStartsWith ('UPDATE `rate_limit_counters`', $this->db->queries[1]);
	}

	public function testInvalidTableNameIsRejected ()
	{
		$this->expectException (InvalidParameter::class);
		new DatabaseStore ('rate limits; drop');
	}

	public function testValidCustomTableNameIsAccepted ()
	{
		$store = new DatabaseStore ('rate_limit_counters_2');
		$this->assertInstanceOf (DatabaseStore::class, $store);
	}

	public function testKeysAreEscaped ()
	{
		$this->db->affectedRows = 1;
		(new DatabaseStore ())->attempt ("a'b", 9960, 1, 1);
		$this->assertStringContainsString ("'a\\'b'", $this->db->queries[1]);
	}

	public function testCleanupDeletesOlderWindows ()
	{
		(new DatabaseStore ())->cleanup (5000);
		$this->assertSame ('DELETE FROM `neuron_rate_limits` WHERE window_start < 5000', $this->db->queries[0]);
	}

	/**
	 * RecordingDatabase can't carry row data back through query()'s
	 * `int` return type (see its docblock), so this only asserts the
	 * SQL shape hits() sends; it always sees an empty result and
	 * returns 0.
	 */
	public function testHitsSelectsTheWindowRow ()
	{
		$this->assertSame (0, (new DatabaseStore ())->hits ('user:1', 9960));
		$this->assertSame (
			"SELECT hits FROM `neuron_rate_limits` WHERE rl_key = 'user:1' AND window_start = 9960",
			$this->db->queries[0]
		);
	}
}
