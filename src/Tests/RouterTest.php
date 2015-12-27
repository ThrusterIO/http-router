<?php

namespace Thruster\Component\HttpRouter\Tests;

use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\HttpRouter\Exception\RouteMethodNotAllowedException;
use Thruster\Component\HttpRouter\Exception\RouteNotFoundException;
use Thruster\Component\HttpRouter\Router;

/**
 * Class RouterTest
 *
 * @package Thruster\Component\HttpRouter\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{

    public function testDefaultOptions()
    {
        $provider = $this->getMockForAbstractClass('\Thruster\Component\HttpRouter\RouteProviderInterface');

        $router = new Router($provider);

        $this->assertInstanceOf(
            'FastRoute\\Dispatcher\\GroupCountBased',
            $router->getDispatcher()
        );
        $this->assertInstanceOf(
            'FastRoute\\RouteCollector',
            $router->getRouteCollector()
        );
    }

    public function testBuildRoute()
    {
        $routeCollector = $this->getMockBuilder('\FastRoute\RouteCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $routeCollector->expects($this->at(0))
            ->method('addRoute')
            ->with(['GET'], '/', 'foo');

        $routeCollector->expects($this->at(1))
            ->method('addRoute')
            ->with(['PUT', 'POST'], '/', 'bar');

        $provider = $this->getMockForAbstractClass('\Thruster\Component\HttpRouter\RouteProviderInterface');

        $provider->expects($this->once())
            ->method('getRoutes')
            ->willReturn(
                [
                    'foo' => ['GET', '/', 'foo'],
                    'bar' => ['PUT', 'POST', '/', 'bar'],
                ]
            );

        $router = new Router($provider);
        $router->setRouteCollector($routeCollector);

        $router->buildRoutes();
    }

    /**
     * @expectedException \Thruster\Component\HttpRouter\Exception\InvalidRouteOptionsException
     * @expectedExceptionMessage Invalid options specified for route "foo"
     */
    public function testBuildRouteInvalidNumOptions()
    {
        $provider = $this->getMockForAbstractClass('\Thruster\Component\HttpRouter\RouteProviderInterface');

        $provider->expects($this->once())
            ->method('getRoutes')
            ->willReturn(
                [
                    'foo' => ['GET', '/']
                ]
            );

        $router = new Router($provider);

        $router->buildRoutes();
    }

    /**
     * @expectedException \Thruster\Component\HttpRouter\Exception\InvalidRouteOptionsException
     * @expectedExceptionMessage Error for route "bar": Cannot register two routes matching "/" for method "GET"
     */
    public function testBuildRouteInvalidOptions()
    {
        $provider = $this->getMockForAbstractClass('\Thruster\Component\HttpRouter\RouteProviderInterface');

        $provider->expects($this->once())
            ->method('getRoutes')
            ->willReturn(
                [
                    'foo' => ['GET', '/', 'foo'],
                    'bar' => ['GET', '/', 'bar']
                ]
            );

        $router = new Router($provider);

        $router->buildRoutes();
    }

    public function testHandleRequest()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                Dispatcher::FOUND, 'foo', []
            ]
        );

        $request->expects($this->at(2))->method('withAttribute')->with('route_name', 'foo')->willReturnSelf();
        $request->expects($this->at(3))->method('withAttribute')->with('route_params', [])->willReturnSelf();

        $provider->expects($this->once())->method('getRoutes')->willReturn(
            [
                'foo' => ['GET', '/', 'foo']
            ]
        );

        $provider->expects($this->once())->method('foo')->with($request)->willReturn(
            $response
        );

        $router = new Router($provider);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $this->assertEquals($response, $router->handleRequest($request));
    }

    public function testHandleRequestNotFound()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                Dispatcher::NOT_FOUND, 'foo', []
            ]
        );

        $router = new Router($provider);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        try {
            $router->handleRequest($request);
            $this->fail('Must throw exception');
        } catch (RouteNotFoundException $e) {
            $this->assertEquals($request, $e->getRequest());
        }
    }

    public function testHandleRequestMethodNotAllowed()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                Dispatcher::METHOD_NOT_ALLOWED, ['GET', 'POST']
            ]
        );

        $router = new Router($provider);
        $router->setDispatcher($dispatcher);

        try {
            $router->handleRequest($request);
            $this->fail('Must throw exception');
        } catch (RouteMethodNotAllowedException $e) {
            $this->assertEquals($request, $e->getRequest());
            $this->assertSame(['GET', 'POST'], $e->getAllowedMethods());
        }
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Should not reach this point
     */
    public function testHandleRequestMethodNotReachable()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                3
            ]
        );

        $router = new Router($provider);
        $router->setDispatcher($dispatcher);

        $router->handleRequest($request);
    }

    public function testMiddlewareHandler()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteHandlerInterface')
            ->setMethods(['handleRoute', 'handleRouteMethodNotAllowed', 'handleRouteNotFound'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                Dispatcher::FOUND, 'foo', []
            ]
        );

        $request->expects($this->at(2))->method('withAttribute')->with('route_name', 'foo')->willReturnSelf();
        $request->expects($this->at(3))->method('withAttribute')->with('route_params', [])->willReturnSelf();

        $provider->expects($this->once())->method('getRoutes')->willReturn(
            [
                'foo' => ['GET', '/', 'foo']
            ]
        );

        $handler->expects($this->once())
            ->method('handleRoute');

        $router = new Router($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $router($request, $response);
    }

    public function testMiddlewareHandlerCallback()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteHandlerInterface')
            ->setMethods(['handleRoute', 'handleRouteMethodNotAllowed', 'handleRouteNotFound'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                Dispatcher::FOUND, 'foo', []
            ]
        );

        $request->expects($this->at(2))->method('withAttribute')->with('route_name', 'foo')->willReturnSelf();
        $request->expects($this->at(3))->method('withAttribute')->with('route_params', [])->willReturnSelf();

        $provider->expects($this->once())->method('getRoutes')->willReturn(
            [
                'foo' => ['GET', '/', 'foo']
            ]
        );

        $handler->expects($this->once())
            ->method('handleRoute');

        $router = new Router($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $called = false;
        $router($request, $response, function() use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testMiddlewareHandlerMethodNotAllowed()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteHandlerInterface')
            ->setMethods(['handleRoute', 'handleRouteMethodNotAllowed', 'handleRouteNotFound'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                Dispatcher::METHOD_NOT_ALLOWED, ['GET', 'POST']
            ]
        );

        $provider->expects($this->once())->method('getRoutes')->willReturn(
            [
                'foo' => ['GET', '/', 'foo']
            ]
        );

        $handler->expects($this->once())
            ->method('handleRouteMethodNotAllowed')
            ->with($this->anything(), $this->anything(), ['GET', 'POST']);

        $router = new Router($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $router($request, $response);
    }

    public function testMiddlewareHandlerNotFound()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteHandlerInterface')
            ->setMethods(['handleRoute', 'handleRouteMethodNotAllowed', 'handleRouteNotFound'])
            ->getMockForAbstractClass();

        $request = $this->getMockForAbstractClass('\Psr\Http\Message\ServerRequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $uri = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $dispatcher = $this->getMockForAbstractClass('\FastRoute\Dispatcher');

        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $uri->expects($this->once())->method('getPath')->willReturn('/');

        $dispatcher->expects($this->once())->method('dispatch')->with('GET', '/')->willReturn(
            [
                Dispatcher::NOT_FOUND, 'foo', []
            ]
        );

        $provider->expects($this->once())->method('getRoutes')->willReturn(
            [
                'foo' => ['GET', '/', 'foo']
            ]
        );

        $handler->expects($this->once())
            ->method('handleRouteNotFound');

        $router = new Router($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $router($request, $response);
    }
}
