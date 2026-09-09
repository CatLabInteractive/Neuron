<?php

namespace Neuron\Tests\RateLimit;

use Neuron\Tests\TestDatabase;

/**
 * Records every query passed to query() and stubs getAffectedRows().
 *
 * TestDatabase::query() is declared `query ($sSQL): int` (no $log
 * parameter), so this override must keep that exact signature. It
 * records the SQL for every statement (SELECT included) regardless of
 * type, but can only ever return an int: DatabaseStore's INSERT IGNORE /
 * UPDATE / DELETE queries don't need anything else, since
 * DatabaseStore::attempt() reads the outcome from getAffectedRows(),
 * not from query()'s return value. A SELECT's row data can't be carried
 * back through this int-typed override, so tests against a SELECT (see
 * DatabaseStore::hits()) can only assert the recorded SQL shape, not a
 * returned result.
 */
class RecordingDatabase extends TestDatabase
{
	/** @var string[] */
	public $queries = [];

	/** @var int */
	public $affectedRows = 0;

	public function query ($sSQL): int
	{
		$this->queries[] = preg_replace ('/\s+/', ' ', trim ($sSQL));
		return 0;
	}

	public function getAffectedRows (): int
	{
		return $this->affectedRows;
	}
}
