<?php

namespace Neuron\RateLimit;

/**
 * Storage for fixed-window counters. attempt() must be atomic: the cap
 * check and the increment happen in one operation, and a refused attempt
 * must not change the stored count.
 */
interface Store
{
	/**
	 * Add $cost hits to ($key, $windowStart) if the result stays <= $max.
	 * @return bool true when the hits were recorded (allowed)
	 */
	public function attempt (string $key, int $windowStart, int $max, int $cost): bool;

	public function hits (string $key, int $windowStart): int;

	/**
	 * Drop rows whose window started before $olderThanWindowStart.
	 */
	public function cleanup (int $olderThanWindowStart): void;
}
