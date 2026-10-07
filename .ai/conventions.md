# Neuron Framework - Coding Conventions

## Code Style
- Tab indentation (not spaces)
- Opening braces on same line for class declarations, new line for methods/control structures
- Spaces before parentheses in function calls: `function_name ($arg)`
- Spaces around operators and after commas
- PHP closing tag `?>` used in source files
- PSR-4 autoloading under `Neuron\` namespace mapped to `src/Neuron/`

## Input Validation Pattern
All user input goes through `Tools::checkInput()` for validation, then `Tools::getInput()` for retrieval:
1. `checkInput($value, $type)` returns bool - validates format
2. `getInput($data, $key, $type, $default)` returns validated+processed value or default

Supported types: text, varchar, string, html, name, email, username, password, date, datetime, number, int, md5, base64, url, bool, raw

## Database Queries
- Use `Query` class for parameterized queries
- Parameter types: `PARAM_STR`, `PARAM_NUMBER`, `PARAM_DATE`, `PARAM_POINT`
- Supports named parameters (`:name`) and positional (`?`) placeholders
- A value without a type is always written as a quoted string; use `PARAM_NUMBER` where SQL needs a bare number (`LIMIT ?`)
- Builder tuples are validated: `[ value, type, nullOnEmpty ]` in SET, `[ value, type, comparator ]` in WHERE, with a `PARAM_*` type and a comparator out of `=`, `!=`, `NOT`, `<`, `>`, `<=`, `>=`, `LIKE`, `IN`. Anything else throws `InvalidParameter`
- A list of values is only read as one inside a tuple: `[ $ids, Query::PARAM_NUMBER, 'IN' ]`
- `Query::update()` and `Query::delete()` need at least one condition

## Testing
- Tests extend `PHPUnit\Framework\TestCase`
- Test namespace: `Neuron\Tests`
- Database-dependent tests use `#[Group('database')]` attribute
- Run: `vendor/bin/phpunit --exclude-group=database`

## Collections
- Base `Collection` class is observable (extends `Observable`)
- Triggers events: 'add', 'set', 'unset'
- Implements Iterator, ArrayAccess, Countable interfaces
