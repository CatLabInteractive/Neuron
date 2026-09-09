<?php

namespace Neuron\RateLimit;

use Neuron\Exceptions\InvalidParameter;

/**
 * Fixed-window rate limiter with weighted attempts.
 *
 *   $limiter = new RateLimiter (new DatabaseStore ());
 *   if (!$limiter->attempt ('user:suggest:' . $userId, 1000, 3600, count ($items))) {
 *       // 429, Retry-After: $limiter->retryAfter (3600)
 *   }
 */
class RateLimiter
{
	/** @var Store */
	private $store;

	/** @var int|null */
	private $now = null;

	public function __construct (Store $store)
	{
		$this->store = $store;
	}

	/**
	 * Test seam: freeze the clock. null returns to time().
	 */
	public function setNow (?int $now): void
	{
		$this->now = $now;
	}

	public static function windowStart (int $windowSeconds, ?int $now = null): int
	{
		if ($windowSeconds < 1) {
			throw new InvalidParameter ('windowSeconds must be at least 1');
		}
		$now = $now === null ? time () : $now;
		return intdiv ($now, $windowSeconds) * $windowSeconds;
	}

	/**
	 * Charge $cost against $key in the current window.
	 * @return bool true when allowed (and charged), false when refused (nothing charged)
	 */
	public function attempt (string $key, int $max, int $windowSeconds, int $cost = 1): bool
	{
		if ($cost < 1) {
			throw new InvalidParameter ('cost must be at least 1');
		}
		return $this->store->attempt ($key, self::windowStart ($windowSeconds, $this->now), $max, $cost);
	}

	public function remaining (string $key, int $max, int $windowSeconds): int
	{
		$hits = $this->store->hits ($key, self::windowStart ($windowSeconds, $this->now));
		return max (0, $max - $hits);
	}

	/**
	 * Seconds until the next window opens (always >= 1). Suitable for a
	 * Retry-After header.
	 */
	public function retryAfter (int $windowSeconds): int
	{
		$now = $this->now === null ? time () : $this->now;
		return max (1, self::windowStart ($windowSeconds, $now) + $windowSeconds - $now);
	}

	/**
	 * Drop counters for windows that started more than $olderThanSeconds ago.
	 */
	public function cleanup (int $olderThanSeconds = 86400): void
	{
		$now = $this->now === null ? time () : $this->now;
		$this->store->cleanup ($now - $olderThanSeconds);
	}
}
