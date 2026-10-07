<?php

namespace Neuron\Tests;

use Neuron\DB\Database;
use Neuron\SessionHandlers\DbSessionHandler;
use Neuron\Tests\SessionHandlers\FixedClockDbSessionHandler;
use Neuron\Tests\SessionHandlers\SessionsDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Expiry and id validation of the database session handler, against a
 * stub database. No session is started.
 */
class DbSessionHandlerTest extends TestCase
{
	const ID = 'abcdef0123456789abcdef0123456789';

	/** @var SessionsDatabase */
	private $db;

	/** @var FixedClockDbSessionHandler */
	private $handler;

	protected function setUp (): void
	{
		$this->db = new SessionsDatabase ();
		Database::setInstance ($this->db);

		// now = 100000, lifetime = 1000: rows set before 99000 are expired.
		$this->handler = new FixedClockDbSessionHandler ();
		$this->handler->open ('', 'PHPSESSID');
	}

	protected function tearDown (): void
	{
		Database::setInstance (null);
	}

	public function testExpiryDecision ()
	{
		$this->assertFalse (DbSessionHandler::isExpired (99000, 100000, 1000));
		$this->assertFalse (DbSessionHandler::isExpired ('99500', 100000, 1000));
		$this->assertTrue (DbSessionHandler::isExpired (98999, 100000, 1000));
		$this->assertTrue (DbSessionHandler::isExpired ('1', 100000, 1000));
	}

	public function testReadReturnsTheDataOfALiveSession ()
	{
		$this->db->rows = array (array ('data' => 'user|i:7;', 'set_time' => '99500'));

		$this->assertSame ('user|i:7;', $this->handler->read (self::ID));
		$this->assertSame (
			array ("SELECT data, set_time FROM `sessions` WHERE id = '" . self::ID . "'"),
			$this->db->queries
		);
	}

	public function testReadTreatsAnExpiredSessionAsAbsentAndRemovesIt ()
	{
		$this->db->rows = array (array ('data' => 'user|i:7;', 'set_time' => '98999'));

		$this->assertSame ('', $this->handler->read (self::ID));
		$this->assertSame ("DELETE FROM `sessions`WHERE id = '" . self::ID . "'", $this->db->queries[1]);
	}

	public function testReadReturnsAnEmptyStringWithoutARow ()
	{
		$this->assertSame ('', $this->handler->read (self::ID));
	}

	public function testReadDoesNotQueryForAnInvalidId ()
	{
		$this->db->rows = array (array ('data' => 'user|i:7;', 'set_time' => '99500'));

		$this->assertSame ('', $this->handler->read ("x' OR '1'='1"));
		$this->assertSame (array (), $this->db->queries);
	}

	public function testValidateIdAcceptsOnlyAStoredLiveSession ()
	{
		$this->db->rows = array (array ('data' => '', 'set_time' => '99500'));
		$this->assertTrue ($this->handler->validateId (self::ID));
	}

	public function testValidateIdRejectsAnIdWithoutARow ()
	{
		$this->assertFalse ($this->handler->validateId (self::ID));
	}

	public function testValidateIdRejectsAnExpiredSession ()
	{
		$this->db->rows = array (array ('data' => 'user|i:7;', 'set_time' => '98999'));
		$this->assertFalse ($this->handler->validateId (self::ID));
	}

	public function testValidateIdRejectsAnInvalidFormatWithoutQuerying ()
	{
		$this->db->rows = array (array ('data' => '', 'set_time' => '99500'));

		$this->assertFalse ($this->handler->validateId ('../../etc/passwd-aaaaaaaaaaaaaa'));
		$this->assertSame (array (), $this->db->queries);
	}

	public function testValidateAndReadShareOneQuery ()
	{
		$this->db->rows = array (array ('data' => 'user|i:7;', 'set_time' => '99500'));

		$this->assertTrue ($this->handler->validateId (self::ID));
		$this->assertSame ('user|i:7;', $this->handler->read (self::ID));
		$this->assertCount (1, $this->db->queries);
	}

	public function testANewlyWrittenSessionIsValid ()
	{
		$this->assertFalse ($this->handler->validateId (self::ID));
		$this->assertSame ('', $this->handler->read (self::ID));

		$this->assertTrue ($this->handler->write (self::ID, ''));

		$this->assertTrue ($this->handler->validateId (self::ID));
		$this->assertSame (
			"REPLACE sessions SET id = '" . self::ID . "', set_time = '100000', data = ''",
			$this->db->queries[1]
		);
	}

	public function testUpdateTimestampStoresTheRowWithTheCurrentTime ()
	{
		$this->assertTrue ($this->handler->updateTimestamp (self::ID, 'user|i:7;'));
		$this->assertSame (
			array ("REPLACE sessions SET id = '" . self::ID . "', set_time = '100000', data = 'user|i:7;'"),
			$this->db->queries
		);
	}

	public function testADestroyedSessionIsNoLongerValid ()
	{
		$this->handler->write (self::ID, 'user|i:7;');
		$this->assertTrue ($this->handler->destroy (self::ID));

		$this->assertFalse ($this->handler->validateId (self::ID));
		$this->assertSame ('', $this->handler->read (self::ID));
	}

	public function testGcHonoursTheGivenLifetime ()
	{
		$this->handler->gc (3600);
		$this->assertSame (array ('DELETE FROM `sessions`WHERE set_time < 96400'), $this->db->queries);
	}
}
