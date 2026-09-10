Neuron
======

A very light php framework.
Will probably not help you much.

Rate limiting
-------------

`Neuron\RateLimit\RateLimiter` is a fixed-window counter with weighted
attempts. The cap check happens inside one conditional `UPDATE`, so bursts
serialise on the row lock and a refused attempt never consumes budget.

```php
use Neuron\RateLimit\DatabaseStore;
use Neuron\RateLimit\RateLimiter;

$limiter = new RateLimiter (new DatabaseStore ());          // table neuron_rate_limits
if (!$limiter->attempt ('user:export:' . $userId, 100, 3600, $itemCount)) {
    header ('Retry-After: ' . $limiter->retryAfter (3600));
    // 429
}
$limiter->cleanup ();  // from a daily cron: drops windows older than a day
```

The application owns the table. Default name `neuron_rate_limits`
(pass another name to `DatabaseStore`; it must match `/^[A-Za-z0-9_]+$/`
and is quoted with backticks in every statement):

```sql
CREATE TABLE neuron_rate_limits (
  id int unsigned NOT NULL AUTO_INCREMENT,
  rl_key varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  window_start int unsigned NOT NULL,
  hits int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY neuron_rate_limits_key_window (rl_key, window_start),
  KEY neuron_rate_limits_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

`rl_key` uses a binary (case-sensitive) collation so keys match exactly;
a case-insensitive collation would merge keys that differ only by case
into the same bucket. `neuron_rate_limits_window` keeps the cleanup
`DELETE` (which scans by `window_start`) indexed. A table with
`PRIMARY KEY (rl_key, window_start)` and no `id` works too.

A few contract details worth knowing:

- **Key length.** `rl_key` is `varchar(128)`. `INSERT IGNORE` silently
  truncates a longer key to fit instead of erroring, so two distinct
  keys that only differ past the 128th character would share a bucket.
  Keep keys short, or hash the long/variable part before using it as a key.
- **`max < 1` refuses every attempt.** There's no special-casing: a cap
  below 1 can never be satisfied. If `0` should mean "disabled" (skip
  rate limiting entirely) in your application, guard for that case
  before calling `attempt()`.
- **`remaining()` is a non-atomic read.** It issues a plain `SELECT`
  with no lock, so its result can be stale relative to a concurrent
  `attempt()` against the same key and window.
- **Don't call `attempt()` inside a long-running explicit transaction.**
  A rollback refunds the budget the `INSERT`/`UPDATE` charged, and
  holding that row's locks open for the rest of a long transaction can
  deadlock against other writers hitting the same key.
- **`window_start` is an unsigned 32-bit epoch bucket** (seconds since
  the epoch, rounded down to a window boundary), matching the column's
  `int unsigned` type above.
