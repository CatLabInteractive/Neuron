# Claude AI Assistant Guide for Neuron Framework

This document provides context for AI assistants working with the Neuron framework codebase.

## Project Documentation

- [Project Summary](.ai/project-summary.md) - Architecture overview and component descriptions
- [Coding Conventions](.ai/conventions.md) - Code style, patterns, and testing practices

## Quick Start

### Install Dependencies
```bash
composer install
```

### Run Tests
```bash
# Run all tests (excluding database-dependent tests)
vendor/bin/phpunit --exclude-group=database

# Run specific test file
vendor/bin/phpunit tests/ToolsTest.php

# Run with coverage
vendor/bin/phpunit --exclude-group=database --coverage-text
```

## Key Areas

### Input Validation (`src/Neuron/Core/Tools.php`)
The primary input validation layer. All user input should be validated through `Tools::checkInput()` before use. The `Tools::getInput()` method combines validation with data retrieval and type-specific processing.

**Important**: Date validation uses `ctype_digit()` and `checkdate()` to ensure all date parts are valid integers representing a real date. The `getInput()` method casts date parts to `(int)` before passing to `mktime()` as a defence-in-depth measure.

### Database Queries (`src/Neuron/DB/Query.php`)
Always use parameterized queries via the `Query` class to prevent SQL injection. Never concatenate user input directly into SQL strings.

### Query log and connection errors (`src/Neuron/DB/Database.php`, `MySQL.php`)
The query log (`getAllQueries()`, `getLastQuery()`) is opt-in: call `Database::setQueryLogEnabled(true)` where the application shows it; it keeps the most recent `Database::QUERY_LOG_LIMIT` (5000) statements. `getQueryCounter()` and `getOriginCounters()` work either way. A connection failure throws a `DbException` with the fixed message `DbException::CONNECTION_FAILED` (`isConnectionError()`); the driver's error is the previous exception, for logging only.

### Testing
Tests are located in `tests/` and use PHPUnit. Database-dependent tests are grouped with `#[Group('database')]` and require a MySQL connection. CI runs tests excluding this group.

## CI/CD
GitHub Actions workflow runs tests on PHP 8.1, 8.2, and 8.3. See `.github/workflows/tests.yml`.
