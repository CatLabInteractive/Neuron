<?php

namespace Neuron\Tests\RateLimit;

use Neuron\RateLimit\Store;

/**
 * In-memory Store for unit tests: same semantics as DatabaseStore
 * (refused attempts write nothing) without a database.
 */
class ArrayStore implements Store
{
	/** @var array<string, int> "key|windowStart" => hits */
	public $rows = [];

	public function attempt (string $key, int $windowStart, int $max, int $cost): bool
	{
		$id = $key . '|' . $windowStart;
		$hits = isset ($this->rows[$id]) ? $this->rows[$id] : 0;
		if ($hits + $cost > $max) {
			return false;
		}
		$this->rows[$id] = $hits + $cost;
		return true;
	}

	public function hits (string $key, int $windowStart): int
	{
		$id = $key . '|' . $windowStart;
		return isset ($this->rows[$id]) ? $this->rows[$id] : 0;
	}

	public function cleanup (int $olderThanWindowStart): void
	{
		foreach (array_keys ($this->rows) as $id) {
			$windowStart = (int) substr ($id, strrpos ($id, '|') + 1);
			if ($windowStart < $olderThanWindowStart) {
				unset ($this->rows[$id]);
			}
		}
	}
}
