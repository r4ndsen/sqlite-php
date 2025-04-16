.PHONY: all test csfix csdiff testdox bench infection coverage stan rector

all: csfix test coverage stan

test:
	vendor/bin/phpunit --color=always

testdox:
	vendor/bin/phpunit --testdox --color=always

csfix:
	vendor/bin/php-cs-fixer fix --verbose

csdiff:
	vendor/bin/php-cs-fixer fix --stop-on-violation --verbose --dry-run --diff

bench:
	vendor/bin/phpbench run --report=aggregate --output=build-artifact tests/Benchmark && open ./coverage/bench.html

infection:
	XDEBUG_MODE=coverage vendor/bin/infection --no-progress --threads=max && open ./coverage/infection.html

coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html ./coverage && open ./coverage/index.html

stan:
	vendor/bin/phpstan analyze --memory-limit 2G

test82:
	docker-compose up -d && docker-compose exec php-8.2 php /app/vendor/bin/phpunit

test83:
	docker-compose up -d && docker-compose exec php-8.3 php /app/vendor/bin/phpunit

test84:
	docker-compose up -d && docker-compose exec php-8.4 php /app/vendor/bin/phpunit

tests: test82 test83 test84

rector:
	vendor/bin/rector process
