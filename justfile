set shell := ["bash", "-eu", "-o", "pipefail", "-c"]
set quiet := true

phpunit := "vendor/bin/phpunit"
php_cs_fixer := "vendor/bin/php-cs-fixer"
phpbench := "vendor/bin/phpbench"
infection := "vendor/bin/infection"
phpstan := "vendor/bin/phpstan"
rector := "vendor/bin/rector"
docker_compose := "docker-compose"

default:
    just --list

all: csfix test coverage stan

test *args:
    {{phpunit}} --color=always --display-deprecations {{args}}

testdox *args:
    {{phpunit}} --testdox --color=always {{args}}

csfix:
    {{php_cs_fixer}} fix --verbose

csdiff:
    {{php_cs_fixer}} fix --stop-on-violation --verbose --dry-run --diff

bench:
    mkdir -p build
    {{phpbench}} run --report=aggregate --output=build-artifact tests/Benchmark
    printf 'Bench report: %s\n' ./build/bench.html

infection:
    mkdir -p build
    XDEBUG_MODE=coverage {{infection}} --no-progress --threads=max
    printf 'Infection report: %s\n' ./build/infection.html

coverage:
    mkdir -p ./build
    XDEBUG_MODE=coverage {{phpunit}} --coverage-html ./build/coverage
    printf 'Coverage report: %s\n' ./build/coverage/index.html

clover:
    mkdir -p ./build/logs
    XDEBUG_MODE=coverage {{phpunit}} --coverage-clover build/logs/clover.xml

stan:
    {{phpstan}} analyze --memory-limit 2G

rector:
    {{rector}} process

_test-docker version:
    {{docker_compose}} up -d php-{{version}}
    {{docker_compose}} exec php-{{version}} php /app/vendor/bin/phpunit

test82: (_test-docker "8.2")
test83: (_test-docker "8.3")
test84: (_test-docker "8.4")
test85: (_test-docker "8.5")

tests: test82 test83 test84 test85
