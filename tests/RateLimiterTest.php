<?php

namespace Neuron\Tests;

use Neuron\Exceptions\InvalidParameter;
use Neuron\RateLimit\RateLimiter;
use Neuron\Tests\RateLimit\ArrayStore;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
	private function limiter (ArrayStore $store, int $now): RateLimiter
	{
		$limiter = new RateLimiter ($store);
		$limiter->setNow ($now);
		return $limiter;
	}

	public function testWindowStartIsTruncatedToTheWindow ()
	{
		$this->assertSame (3600, RateLimiter::windowStart (3600, 3600));
		$this->assertSame (3600, RateLimiter::windowStart (3600, 7199));
		$this->assertSame (7200, RateLimiter::windowStart (3600, 7200));
	}

	public function testWindowBelowOneSecondIsRejected ()
	{
		$this->expectException (InvalidParameter::class);
		RateLimiter::windowStart (0, 1000);
	}

	public function testAttemptsUpToMaxAreAllowedThenRefused ()
	{
		$store = new ArrayStore ();
		$limiter = $this->limiter ($store, 10000);
		for ($i = 0; $i < 3; $i ++) {
			$this->assertTrue ($limiter->attempt ('k', 3, 60));
		}
		$this->assertFalse ($limiter->attempt ('k', 3, 60));
		$this->assertSame (3, $store->hits ('k', 9960), 'a refused attempt must not consume budget');
	}

	public function testCostIsWeighted ()
	{
		$store = new ArrayStore ();
		$limiter = $this->limiter ($store, 10000);
		$this->assertTrue ($limiter->attempt ('k', 100, 3600, 60));
		$this->assertFalse ($limiter->attempt ('k', 100, 3600, 50), '60 + 50 exceeds 100');
		$this->assertTrue ($limiter->attempt ('k', 100, 3600, 40), '60 + 40 fits exactly');
		$this->assertSame (0, $limiter->remaining ('k', 100, 3600));
	}

	public function testCostAboveMaxIsAlwaysRefused ()
	{
		$limiter = $this->limiter (new ArrayStore (), 10000);
		$this->assertFalse ($limiter->attempt ('k', 10, 60, 11));
	}

	public function testCostBelowOneIsRejected ()
	{
		$this->expectException (InvalidParameter::class);
		$this->limiter (new ArrayStore (), 10000)->attempt ('k', 10, 60, 0);
	}

	public function testRemainingNeverGoesNegative ()
	{
		$store = new ArrayStore ();
		$store->rows['k|9960'] = 50;
		$limiter = $this->limiter ($store, 10000);
		$this->assertSame (0, $limiter->remaining ('k', 10, 60));
		$this->assertSame (10, $limiter->remaining ('other', 10, 60));
	}

	public function testANewWindowResetsTheBudget ()
	{
		$store = new ArrayStore ();
		$limiter = $this->limiter ($store, 10000);
		$this->assertTrue ($limiter->attempt ('k', 1, 60));
		$this->assertFalse ($limiter->attempt ('k', 1, 60));
		$limiter->setNow (10020); // next 60 s window starts at 10020
		$this->assertTrue ($limiter->attempt ('k', 1, 60));
	}

	public function testRetryAfterCountsDownToTheNextWindow ()
	{
		$limiter = $this->limiter (new ArrayStore (), 10000); // window 9960..10020
		$this->assertSame (20, $limiter->retryAfter (60));
		$limiter->setNow (10019);
		$this->assertSame (1, $limiter->retryAfter (60));
		$limiter->setNow (10020);
		$this->assertSame (60, $limiter->retryAfter (60));
	}

	public function testCleanupDropsOldWindows ()
	{
		$store = new ArrayStore ();
		$store->rows['k|1000'] = 1;
		$store->rows['k|9960'] = 1;
		$limiter = $this->limiter ($store, 10000);
		$limiter->cleanup (3600);
		$this->assertSame ([ 'k|9960' => 1 ], $store->rows);
	}
}
