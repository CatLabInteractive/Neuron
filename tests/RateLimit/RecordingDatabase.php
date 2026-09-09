<?php

namespace Neuron\Tests\RateLimit;

use Neuron\Tests\TestDatabase;

/**
 * Records every query passed to query() and stubs getAffectedRows().
 *
 * TestDatabase::query() is declared `query ($sSQL): int` (no $log
 * parameter), so this override must keep that exact signature. That
 * also means it can only ever return an int: DatabaseStore's queries
 * (INSERT IGNORE / UPDATE / DELETE) don't need anything else, since
 * DatabaseStore::attempt() reads the outcome from getAffectedRows(),
 * not from query()'s return value. A SELECT (as used by
 * DatabaseStore::hits()) can't have its row data carried back through
 * this int-typed override; hits() is not covered by this double.
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
