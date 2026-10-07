<?php

namespace Neuron\Tests;

use Neuron\DB\Database;
use PHPUnit\Framework\TestCase;

class DbQueryLogTest extends TestCase
{
	protected function tearDown (): void
	{
		Database::setQueryLogEnabled (false);
	}

	public function testQueryLogIsOffByDefault ()
	{
		$this->assertFalse (Database::isQueryLogEnabled ());
	}

	public function testNothingIsStoredWhenTheLogIsOff ()
	{
		$db = new LoggingTestDatabase ();

		$db->query ("UPDATE users SET password = 'secret-hash' WHERE id = 1");
		$db->log ('a comment');

		$this->assertSame (array (), $db->getAllQueries ());
		$this->assertNull ($db->getLastQuery ());
	}

	public function testCountersKeepWorkingWhenTheLogIsOff ()
	{
		$db = new LoggingTestDatabase ();

		$db->query ('SELECT 1');
		$db->query ('SELECT 2');

		$this->assertSame (2, $db->getQueryCounter ());
		$this->assertCount (2, $db->getOriginCounters ());
	}

	public function testStatementsAreStoredWhenTheLogIsOn ()
	{
		Database::setQueryLogEnabled (true);
		$this->assertTrue (Database::isQueryLogEnabled ());

		$db = new LoggingTestDatabase ();
		$db->query ('SELECT 1');
		$db->log ('a comment');

		$queries = $db->getAllQueries ();
		$this->assertCount (2, $queries);
		$this->assertStringContainsString ('SELECT 1', $queries[0]);
		$this->assertStringContainsString ('/* a comment */', $queries[1]);
		$this->assertSame ($queries[1], $db->getLastQuery ());
	}

	public function testTurningTheLogOffStopsStoringButKeepsWhatWasLogged ()
	{
		Database::setQueryLogEnabled (true);
		$db = new LoggingTestDatabase ();
		$db->query ('SELECT 1');

		Database::setQueryLogEnabled (false);
		$db->query ('SELECT 2');

		$this->assertCount (1, $db->getAllQueries ());

		$db->flushLog ();
		$this->assertSame (array (), $db->getAllQueries ());
		$this->assertSame (0, $db->getQueryCounter ());
	}

	public function testTheLogKeepsOnlyTheMostRecentStatements ()
	{
		Database::setQueryLogEnabled (true);
		$db = new LoggingTestDatabase ();

		$total = Database::QUERY_LOG_LIMIT + 3;
		for ($i = 1; $i <= $total; $i ++) {
			$db->query ('SELECT ' . $i);
		}

		$queries = $db->getAllQueries ();
		$this->assertSame (5000, Database::QUERY_LOG_LIMIT);
		$this->assertCount (Database::QUERY_LOG_LIMIT, $queries);
		$this->assertStringEndsWith ('SELECT 4', $queries[0]);
		$this->assertStringEndsWith ('SELECT ' . $total, $queries[count ($queries) - 1]);
		$this->assertSame ($total, $db->getQueryCounter ());
	}

	public function testALoggerStillReceivesStatementsWhenTheLogIsOff ()
	{
		$logger = new RecordingLogger ();
		$db = new LoggingTestDatabase ();
		$db->setLogger ($logger);

		$db->query ('SELECT 1');

		$this->assertCount (1, $logger->lines);
		$this->assertStringContainsString ('SELECT 1', $logger->lines[0]);
		$this->assertSame (array (), $db->getAllQueries ());
	}
}
