# Neuron Framework - Project Summary

## Overview
Neuron is a lightweight PHP framework by CatLab Interactive. It provides core utilities for web applications including input validation, database query building, collections, encryption, URL building, filtering, and localization.

## Architecture

### Core Components
- **Application** (`src/Neuron/Application.php`) - Main application dispatcher, singleton pattern
- **Config** (`src/Neuron/Config.php`) - Configuration loader with dot-notation access and environment overrides
- **Router** (`src/Neuron/Router.php`) - URL routing
- **URLBuilder** (`src/Neuron/URLBuilder.php`) - Static URL construction utilities

### Input Handling
- **Tools** (`src/Neuron/Core/Tools.php`) - Input validation (`checkInput`) and retrieval (`getInput`) for types: text, varchar, string, html, name, email, username, password, date, datetime, number, int, md5, base64, url, bool, raw

### Database
- **Query** (`src/Neuron/DB/Query.php`) - Parameterized SQL query builder with INSERT, UPDATE, DELETE, SELECT support
- **Database** (`src/Neuron/DB/Database.php`) - Database interface
- **MySQL** (`src/Neuron/DB/MySQL.php`) - MySQL implementation

### Collections
- **Collection** (`src/Neuron/Collections/Collection.php`) - Observable collection implementing Iterator, ArrayAccess, Countable
- **ModelCollection** (`src/Neuron/Collections/ModelCollection.php`) - Model-specific collection with ID indexing
- **ErrorCollection** (`src/Neuron/Collections/ErrorCollection.php`) - Error message collection

### Security
- **SimpleCrypt** (`src/Neuron/Encryption/SimpleCrypt.php`) - AES-256-CBC encryption/decryption
- **TokenGenerator** (`src/Neuron/Tools/TokenGenerator.php`) - Random token generation

### Filtering
- **Filter Parser/Scanner** (`src/Neuron/Filter/`) - Expression-based filtering with context support

## Testing
- Tests are in `tests/` directory using PHPUnit 10/11
- Bootstrap in `tests/bootstrap.php`
- Database tests are grouped with `#[Group('database')]` and excluded from CI
- Run tests: `vendor/bin/phpunit --exclude-group=database`

## Build & Dependencies
- PHP >= 8.1
- Composer for dependency management
- Key dependencies: `ext-gettext`
- Dev dependency: `phpunit/phpunit`
