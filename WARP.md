# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is `r4ndsen/sqlite-php`, a high-level PHP library providing batteries-included utilities for SQLite3 database operations. The library focuses on schema introspection, pragma management, and convenient query utilities with a fluent API.

**Key Requirements:**
- PHP 8.2 or newer
- `ext-sqlite3` and `ext-mbstring` extensions
- Uses PSR-4 autoloading under the `r4ndsen\` namespace

## Development Commands

### Primary Development Workflow
```bash
# Run all quality checks and tests (recommended for pre-commit)
just all

# Individual commands
just test        # PHPUnit with current PHP version
just coverage    # Generate HTML coverage report
just stan        # PHPStan static analysis
just csfix       # Apply PHP-CS-Fixer formatting
just csdiff      # Show formatting differences without applying
```

### Testing Across PHP Versions
```bash
just tests       # Run tests against Docker matrix (PHP 8.2-8.5)
just test82      # Test specifically on PHP 8.2
just test83      # Test specifically on PHP 8.3
just test84      # Test specifically on PHP 8.4
just test85      # Test specifically on PHP 8.5
```

### Additional Quality Tools
```bash
just infection   # Mutation testing (requires Xdebug)
just bench       # Performance benchmarks
just rector      # Code refactoring with Rector
just testdox     # Human-readable test output
```

### Direct Tool Access
```bash
vendor/bin/phpunit --color=always
vendor/bin/phpstan analyze --memory-limit 2G
vendor/bin/php-cs-fixer fix --verbose
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html ./build/coverage
```

## Architecture Overview

### Core Components

**Main Entry Point (`SQLite.php`)**
- Primary database connection and coordination class
- Implements magic `__get()` for table access and pragma operations
- Handles database attachment/detachment for multi-database operations
- Provides validation via integrity checks
- Loads custom extensions (Functions, Collations, Aggregates)

**Connection Layer (`Connection.php`)**
- Extends native `SQLite3` with enhanced error handling
- Wraps all database operations with exception handling
- Manages transactions through `Transaction` class
- Provides consistent query exception handling

**Table Operations (`Table.php`)**
- Core table management with fluent API
- Implements `Countable` and `IteratorAggregate` for direct iteration
- Supports dynamic and fixed insert patterns
- Schema introspection and column management
- Index creation and management

### Key Traits

**QueryTrait**
- Provides comprehensive query methods across multiple classes
- Supports various fetch patterns: `fetchAll()`, `fetchOne()`, `fetchValue()`, `fetchPairs()`
- Object hydration with `fetchObject()` and `fetchObjects()`
- Generator-based yielding methods for memory-efficient operations
- Custom class instantiation with reflection support

**PragmaTrait**
- PRAGMA management functionality
- Database configuration and optimization
- Schema introspection utilities

### Column System

**Column Types and Schema**
- `Column.php`: Core column definition and manipulation
- `ColumnType.php`: Type system for SQLite columns
- `ColumnSchema.php`: Schema introspection capabilities
- `CreateColumnInterface`: Contract for column creation

### Helper Components

**PreparedStatement (`PreparedStatement.php`)**
- Enhanced prepared statement handling
- Parameter binding utilities
- Execution and result management

**Query Processing**
- `QueryParser.php`: SQL parsing and parameter binding
- Support for named parameters in queries

**Constraints and Indexes**
- `TableConstraint.php`: Table-level constraints
- `Index.php`: Index creation and management
- `OnConflict.php`: Conflict resolution strategies

## Development Patterns

### Table Creation Pattern
```php
$table = $sqlite->getTable('tablename');
$table
    ->addCreateColumn(Column::createIntegerColumn('id'))
    ->addCreateColumn(Column::createTextColumn('title')->disallowNull())
    ->createIfNotExists();
```

### Dynamic Data Insertion
```php
$dynamicTable = $table->getDynamicInsertTable();
$dynamicTable
    ->push(['id' => 1, 'title' => 'Test'])
    ->push(['id' => 2, 'title' => 'Another'])
    ->commit();
```

### Query Convenience Methods
The library emphasizes convenient querying with multiple fetch patterns:
- `fetchAll()` - Complete result sets
- `fetchPairs()` - Key-value pairs from first two columns
- `fetchValue()` - Single scalar value
- `yieldAll()` - Memory-efficient iteration

### Object Hydration
Support for custom class hydration with reflection:
- Constructor-based instantiation with `fetchInstances()`
- Property-based hydration with `fetchObjects()`
- Support for private constructors and properties

## Test Structure

**Test Organization:**
- `tests/SQLite/` - Core functionality tests
- `tests/Benchmark/` - Performance benchmarks
- Test classes extend `TestCase` which provides SQLite setup

**Key Testing Patterns:**
- In-memory databases for isolation (`:memory:`)
- Method-based table naming for test isolation
- Comprehensive exception testing for error conditions
- Multi-PHP version compatibility testing via Docker

## Build and CI Configuration

**Quality Tools Configuration:**
- `.php-cs-fixer.php` - Code style configuration
- `phpstan.neon.dist` - Static analysis rules
- `phpunit.xml` - Test configuration with random execution order
- `infection.json.dist` - Mutation testing configuration

**Docker Support:**
- Multi-version PHP testing (8.2-8.5)
- Automated SQLite extension installation
- Consistent testing environment across versions
