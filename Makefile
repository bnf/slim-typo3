.PHONY: test

.build/vendor/autoload.php: composer.json
	rm -rf composer.lock .build/
	composer install

test: .build/vendor/autoload.php
	.build/vendor/bin/phpunit

test-coverage: .build/vendor/autoload.php
	php -dzend_extension=xdebug.so .build/vendor/bin/phpunit --coverage-text

lint:
	find . -name '*.php' '!' -path './.build/*' -exec php -l {} >/dev/null \;
