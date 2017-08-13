Slim framework integration for TYPO3
====================================

Introduction
------------

This extension provides a TYPO3 RequestHandler which runs a Slim App.
The Slim App will be executed when one of its routes match the request.
If no route matches, the lower prioritized default TYPO3
RequestHandler(s) will be executed.

This request handler basically works like a TYPO3 eID (executed with identically
environment), but with proper routing and nice looking URLs.

Note: The EIDRequestHandler has higher priority and will not
be influenced by this router. That means the slim app
can not accept a GET parameter `eID`.

Usage
-----

```sh
composer require bnf/slim-typo3:dev-master
```

ext_localconf.php:
```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['slim_typo3']['configureApp'][] = \Your\Namespace\TestApp::class;
```

TestApp.php
```php
<?php
namespace Your\Namespace;

use Bnf\SlimTypo3\Hook\ConfigureAppHookInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class TestApp implements ConfigureAppHookInterface
{
    public static function configure(App $app)
    {
        $app->add(function ($request, $response, $next) {
            $response->getBody()->write('I am middleware.. ');
            return $next($request, $response, $next);
        });

        $app->get('/bar[/{foo}]', static::class . ':bar');
    }

    public function bar(Request $request, Response $response)
    {
        $response->getBody()->write('baz');

        $foo = $request->getAttribute('foo');
        if ($foo) {
            $response->getBody()->write(' ' . htmlspecialchars($foo));
        }

        return $response;
    }
}

```

TODO
----

Support generating multiple RequestsHandlers
with multiple App configs? (and maybe basePaths).
(Well, maybe not. Con: that add's another abstraction on top
of Slim\App which already knows about route groups – BUT
it would support different Middleware configs.)

Example:

```
App1: /api
App2: /api2
App3: /getFooData.xml
```
