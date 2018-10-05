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


prepare-classic-extension:
	rm -rf Resources/Private/PHP
	mkdir -p Resources/Private/PHP
	cd Resources/Private/PHP && composer init -n
	cd Resources/Private/PHP && composer config vendor-dir .
	sed -i 's#    "config":#    "replace": { "typo3/cms-core": "*", "typo3/cms-frontend": "*", "typo3/cms-extbase": "*", "psr/http-message": "*", "psr/container": "*" },\n&#' Resources/Private/PHP/composer.json
	cd Resources/Private/PHP && composer require slim/slim:^3.0 pimple/pimple:^3.2 bnf/typo3-middleware:~0.3.0 bnf/slim3-psr15:^1.0
	cd Resources/Private/PHP && composer install --no-dev --no-autoloader --ansi && rm -rf composer.json composer.lock
	rm -rf Resources/Private/PHP/pimple/pimple/ext/
	find Resources/Private/PHP/ -type d \( -name Tests -o -name test -o -name tests -o -name docs \) -exec rm -rf '{}' +
	find Resources/Private/PHP/ -type f \( -name README.md -o -name README.rst -o -name CHANGELOG -o -name composer.json -o -name phpunit.xml.dist -o -name .gitignore -o -name .travis.yml \) -exec rm -f '{}' +

build-t3x-extension: prepare-classic-extension
	rm -rf Resources/Private/PHP/composer/
	rm -f "$${PWD##*/}_`git describe --tags`.zip"
	git archive -o "$${PWD##*/}_`git describe --tags`.zip" HEAD
	zip -r -g "$${PWD##*/}_`git describe --tags`.zip" Resources/Private/PHP/
	rm -rf Resources/Private/PHP
	@echo
	@echo "$${PWD##*/}_`git describe --tags`.zip has been created."


Resources/Private/PHP/composer/installed.json: Makefile
	$(MAKE) prepare-classic-extension
