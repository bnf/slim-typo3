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
declare(strict_types=1);
namespace Your\Namespace;

use Bnf\SlimTypo3\Hook\ConfigureAppHookInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class TestApp implements ConfigureAppHookInterface
{
    public static function configure(App $app)
    {
        $app->add(function (Request $request, Response $response, callable $next) {
            $response->getBody()->write('I am middleware.. ');
            return $next($request, $response, $next);
        });

        $app->get('/bar[/{foo}]', static::class . ':bar');
    }

    public function bar(Request $request, Response $response): Response
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


Suggestions
-----------

For this TYPO3 integration we suggest to add middleware's as string.
That has a slight overhead as Slim's `CallableResolver` will preg_match
the string, but has the advantage, that not all middleware's
need to be instantiated although they may never be called (as no route matches
and we do not process the request). As TYPO3 is the host
it's likely that most of the times the TYPO3 RequestHandlers will be executed
therefore the slim bootstrap should be as lightweight as possible.

You can supply middleware's as class (or container identifier) strings
and Slim's `CallableResolver` will retrieve the instance
from the container (if the container has a definition for that class)
or instantiate a new class: `new $class($container);`

Therefore use:

```php
// Will implicitly call `new \Your\Namespace\Middleware\Foo($container)`
// when the middleware is executed.
$app->add(\Your\Namespace\Middleware\Foo::class);
// or ('view' needs to be defined in the container)
$app->add('view');
```

instead of:

```php
// You SHOULD NOT do this
$app->add(new \Your\Namespace\Middleware\Foo::class);
// nor this
$app->add($container->get(\Your\Namespace\Middleware\Foo::class));
// nor this
$app->add($container->get('view'));
```