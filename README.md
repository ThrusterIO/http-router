# HttpRouter Component

[![Latest Version](https://img.shields.io/github/release/ThrusterIO/http-router.svg?style=flat-square)]
(https://github.com/ThrusterIO/http-router/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)]
(LICENSE)
[![Build Status](https://img.shields.io/travis/ThrusterIO/http-router.svg?style=flat-square)]
(https://travis-ci.org/ThrusterIO/http-router)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ThrusterIO/http-router.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/http-router)
[![Quality Score](https://img.shields.io/scrutinizer/g/ThrusterIO/http-router.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/http-router)
[![Total Downloads](https://img.shields.io/packagist/dt/thruster/http-router.svg?style=flat-square)]
(https://packagist.org/packages/thruster/http-router)

[![Email](https://img.shields.io/badge/email-team@thruster.io-blue.svg?style=flat-square)]
(mailto:team@thruster.io)

The Thruster HttpRoute Component. PSR-7 and [FastRoute] based simple router.


## Install

Via Composer

```bash
$ composer require thruster/http-router
```


## Usage

### Standalone usage

```php
<?php

use Psr\Http\Message\RequestInterface;
use Thruster\Component\HttpRouter\Router;
use Thruster\Component\HttpRouter\RouteProvider;

$application = new class implements RouteProvider {
    public function getRoutes() : array
    {
        return [
            'hello_world' => ['GET', '/', 'hello'],
            ['POST', '/', [$this, 'foo']]
        ];
    }

    public function hello(ServerRequestInterface $request)
    {
        // return new Response(200, [], 'Hello world');
    }

    public function foo(ServerRequestInterface $request)
    {
        // return new Response(404, [], 'Foo Bar');
    }
};


$router = new Router($application);
$response = $router->handleRequest(ServerRequest::fromGlobals()); // PSR-7 Response
```

### PSR-7 style middleware

```php
<?php

use Psr\Http\Message\RequestInterface;
use Thruster\Component\HttpRouter\Router;
use Thruster\Component\HttpRouter\RouteProvider;
use Thruster\Component\HttpRouter\RouteHandler;

$application = new class implements RouteProvider, RouteHandler {
    public function getRoutes() : array
    {
        return [
            'hello_world' => ['GET', '/', 'hello'],
            ['POST', '/', [$this, 'foo']]
        ];
    }

    public function handleRoute(
    	ServerRequestInterface $request,
    	ResponseInterface $response,
    	callable $actionHandler
    ) : ResponseInterface {
    	// ... call actionHandler and return ResponseInterface
    }

    public function handleRouteMethodNotAllowed(
    	ServerRequestInterface $request,
    	ResponseInterface $response,
    	array $allowedMethods
    ) : ResponseInterface {
    	// ... handle method not allowed error
    }

    public function handleRouteNotFound(
    	ServerRequestInterface $request,
    	ResponseInterface $response
    ) : ResponseInterface {
    	// ... handle route not found (404)
    }
};


$router = new Router($application, $application);
$response = $router(ServerRequest::fromGlobals(), new Response()); // PSR-7 Response
```


## Testing

```bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## License

Please see [License File](LICENSE) for more information.

[FastRoute]: https://github.com/nikic/FastRoute
