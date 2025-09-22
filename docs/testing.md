# Testing

## Unit tests
```shell
make test
```
Runs the PHPUnit suite against the current PHP version.

## Cross-version matrix
```shell
make tests
```
Boots Docker containers for PHP 8.2 through 8.5 and executes the test suite in
each environment. Shut the containers down afterwards with `docker-compose down`.

## Mutation testing
```shell
make infection
```
Requires Xdebug. Prints the generated HTML report path when it finishes.

## Code coverage
```shell
make coverage
```
Generates an HTML report under `build/coverage/` and echoes the index path.

## Static analysis
```shell
make stan
```
Runs PHPStan with the repository configuration.

## Security advisories
```shell
composer audit
```
Checks dependencies against the public advisory database.
