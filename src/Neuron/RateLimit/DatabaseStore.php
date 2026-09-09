<?php

namespace Neuron\RateLimit;

use Neuron\DB\Database;
use Neuron\DB\Query;

/**
 * MySQL-backed Store. Expected table (see README, "Rate limiting"):
 *
 *   rl_key varchar(128) NOT NULL, window_start int unsigned NOT NULL,
 *   hits int unsigned NOT NULL DEFAULT 0, UNIQUE (rl_key, window_start)
 *
 * The cap check lives inside the UPDATE's WHERE clause, so concurrent
 * callers serialise on the row lock and an over-cap attempt matches no
 * row and writes nothing.
 */
class DatabaseStore implements Store
{
	/** @var string */
	private $table;

	public function __construct (string $table = 'neuron_rate_limits')
	{
		$this->table = $table;
	}

	public function attempt (string $key, int $windowStart, int $max, int $cost): bool
	{
		$insert = new Query ("INSERT IGNORE INTO {$this->table} (rl_key, window_start, hits) VALUES (?, ?, 0)");
		$insert->bindValue (1, $key, Query::PARAM_STR);
		$insert->bindValue (2, $windowStart, Query::PARAM_NUMBER);
		$insert->execute ();

		$update = new Query ("UPDATE {$this->table} SET hits = hits + ? WHERE rl_key = ? AND window_start = ? AND hits + ? <= ?");
		$update->bindValue (1, $cost, Query::PARAM_NUMBER);
		$update->bindValue (2, $key, Query::PARAM_STR);
		$update->bindValue (3, $windowStart, Query::PARAM_NUMBER);
		$update->bindValue (4, $cost, Query::PARAM_NUMBER);
		$update->bindValue (5, $max, Query::PARAM_NUMBER);
		$update->execute ();

		return intval (Database::getInstance ()->getAffectedRows ()) === 1;
	}

	public function hits (string $key, int $windowStart): int
	{
		$query = new Query ("SELECT hits FROM {$this->table} WHERE rl_key = ? AND window_start = ?");
		$query->bindValue (1, $key, Query::PARAM_STR);
		$query->bindValue (2, $windowStart, Query::PARAM_NUMBER);
		$result = $query->execute ();
		if (!is_countable ($result) || count ($result) === 0) {
			return 0;
		}
		return intval ($result[0]['hits']);
	}

	public function cleanup (int $olderThanWindowStart): void
	{
		$query = new Query ("DELETE FROM {$this->table} WHERE window_start < ?");
		$query->bindValue (1, $olderThanWindowStart, Query::PARAM_NUMBER);
		$query->execute ();
	}
}
