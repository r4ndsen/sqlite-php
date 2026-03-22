# Repository Guidelines

## Project Structure & Module Organization
Core PHP sources live in `src/`, with domain objects grouped under `src/SQLite`. PHPUnit suites sit in `tests/SQLite`, while performance microbenchmarks live in `tests/Benchmark`. Shared fixtures (including `.sqlite` samples) remain under `tests/fixtures`. Generated assets such as coverage, mutation, and bench reports go to `build/`. The `docs/` directory holds end-user documentation, and `test/` contains simple manual scripts used during exploratory debugging.

## Build, Test, and Development Commands
Run `composer install` once to pull dependencies. Use `just test` for the default PHPUnit run, or `vendor/bin/phpunit` if you need custom flags. `just tests` executes the matrix against PHP 8.2–8.5 via Docker Compose. Static and correctness tooling include `just csdiff` (style check), `just csfix` (auto-fix), `just stan` (PHPStan analysis), `just rector` (automated refactors), and `just infection` (mutation testing, requires Xdebug). Generate local coverage with `just coverage` and view the HTML artefact in `build/coverage/index.html`. For profiling SQL-heavy changes, `just bench` renders aggregate benchmarks to `build/bench.html`.

## Coding Style & Naming Conventions
The project follows the Symfony rule set via `.php-cs-fixer.php`; use four-space indentation, braces on the next line for classes/methods, and import classes from the global namespace. Avoid Yoda conditions, keep docblocks concise, and align array arrows with single spaces. Classes remain PascalCase in `src/`, while tests mirror the subject class name plus `Test`. PHPUnit data providers and test methods stay in `snake_case` as enforced by the fixer.

## Testing Guidelines
Add new behavioural tests beside the feature in `tests/SQLite`, naming files `<Subject>Test.php`. Extend `tests/SQLite/TestCase.php` for shared helpers, and load fixtures from `tests/fixtures` to keep binaries out of Git history elsewhere. Ensure new code has PHPUnit coverage locally (`just test`) and, when changing SQL parsing or connection semantics, add mutation checks (`just infection`) to guard regressions. Benchmark critical query paths before and after performance-sensitive changes.

## Commit & Pull Request Guidelines
Prefer concise, imperative commit subjects (e.g., `improve pragma parsing`), followed by context in the body when needed. Squash fixups before opening a PR. Each PR should describe the motivation, outline test coverage (commands you ran), and link related issues. Attach screenshots or CLI excerpts when the change affects generated reports. Confirm CI passes and code style is clean before requesting review.
