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
(pass another name to `DatabaseStore`):

```sql
CREATE TABLE neuron_rate_limits (
  id int unsigned NOT NULL AUTO_INCREMENT,
  rl_key varchar(128) NOT NULL,
  window_start int unsigned NOT NULL,
  hits int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY neuron_rate_limits_key_window (rl_key, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

A table with `PRIMARY KEY (rl_key, window_start)` and no `id` works too.
