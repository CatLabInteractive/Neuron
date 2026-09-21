<?php

namespace Neuron\Tests;

use Neuron\DB\MySQL;
use Neuron\Exceptions\DbException;
use PHPUnit\Framework\TestCase;

/**
 * Errors from a real MySQL surface as DbException, whatever the mysqli
 * report mode. Needs DB_HOST, DB_USERNAME, DB_PASSWORD and DB_DATABASE.
 *
 * @group database
 */
class MySQLErrorTest extends TestCase
{
	/** @var MySQL */
	private $db;

	/** @var int */
	private $reportMode;

	protected function setUp (): void
	{
		if (!getenv ('DB_HOST') || !class_exists ('mysqli')) {
			$this->markTestSkipped ('Set DB_HOST, DB_USERNAME, DB_PASSWORD and DB_DATABASE to run.');
		}

		$this->reportMode = (new \mysqli_driver ())->report_mode;

		$connection = new \mysqli (getenv ('DB_HOST'), getenv ('DB_USERNAME'), getenv ('DB_PASSWORD'), getenv ('DB_DATABASE'));
		$this->db = new MySQL ();
		$property = new \ReflectionProperty (MySQL::class, 'connection');
		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible (true);
		}
		$property->setValue ($this->db, $connection);

		$this->db->query ('DROP TABLE IF EXISTS neuron_error_test');
		$this->db->query ('CREATE TABLE neuron_error_test (id INT NOT NULL, PRIMARY KEY (id))');
	}

	protected function tearDown (): void
	{
		if ($this->db) {
			mysqli_report ($this->reportMode);
			$this->db->query ('DROP TABLE IF EXISTS neuron_error_test');
		}
	}

	public static function reportModes ()
	{
		return [
			'strict (PHP 8.1+ default)' => [ MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT ],
			'off (pre-8.1 default)' => [ MYSQLI_REPORT_OFF ],
		];
	}

	/**
	 * @dataProvider reportModes
	 */
	public function testDuplicateKeyIsDbException ($mode)
	{
		mysqli_report ($mode);
		$this->db->query ('INSERT INTO neuron_error_test (id) VALUES (1)');

		try {
			$this->db->query ('INSERT INTO neuron_error_test (id) VALUES (1)');
			$this->fail ('Expected a DbException');
		} catch (DbException $e) {
			$this->assertTrue ($e->isDuplicateEntry ());
			$this->assertSame (1062, intval ($e->getErrorCode ()));
			$this->assertSame ('INSERT INTO neuron_error_test (id) VALUES (1)', $e->getQuery ());
		}
	}

	/**
	 * @dataProvider reportModes
	 */
	public function testOtherErrorsAreDbExceptionToo ($mode)
	{
		mysqli_report ($mode);

		try {
			$this->db->query ('SELECT * FROM neuron_error_test_missing');
			$this->fail ('Expected a DbException');
		} catch (DbException $e) {
			$this->assertFalse ($e->isDuplicateEntry ());
			$this->assertSame (1146, intval ($e->getErrorCode ()));
		}
	}

	/**
	 * @dataProvider reportModes
	 */
	public function testMultiQueryErrorIsDbException ($mode)
	{
		mysqli_report ($mode);

		try {
			$this->db->multiQuery ('INSERT INTO neuron_error_test (id) VALUES (2); INSERT INTO neuron_error_test (id) VALUES (2)');
			$this->fail ('Expected a DbException');
		} catch (DbException $e) {
			$this->assertTrue ($e->isDuplicateEntry ());
		}
	}
}
