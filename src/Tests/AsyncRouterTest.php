<?php

namespace Thruster\Component\HttpRouter\Tests;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Thruster\Component\HttpRouter\AsyncRouter;
use Thruster\Component\Promise\FulfilledPromise;

/**
 * Class AsyncRouterTest
 *
 * @package Thruster\Component\HttpRouter\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class AsyncRouterTest extends \PHPUnit_Framework_TestCase
{

    public function testRouteReturnsPromise()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\AsyncRouteHandlerInterface')
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

        $router = new AsyncRouter($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $promise = new FulfilledPromise($response);

        $handler->expects($this->once())
            ->method('handleRoute')
            ->with($request, $response, [$provider, 'foo'])
            ->willReturn($promise);

        $receivedPromise = $router($request, $response);

        $receivedPromise->done(function (ResponseInterface $givenResponse) use ($response) {
            $this->assertEquals($response, $givenResponse);
        });
    }

    public function testRouteReturnsPromiseCallback()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\AsyncRouteHandlerInterface')
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

        $router = new AsyncRouter($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $promise = new FulfilledPromise($response);

        $handler->expects($this->once())
            ->method('handleRoute')
            ->with($request, $response, [$provider, 'foo'])
            ->willReturn($promise);

        $called = false;
        $receivedPromise = $router($request, $response, function($request, $response) use (&$called) {
            $called = true;

            return $response;
        });

        $this->assertTrue($called);

        $receivedPromise->done(function (ResponseInterface $givenResponse) use ($response) {
            $this->assertEquals($response, $givenResponse);
        });
    }

    public function testMiddlewareHandlerMethodNotAllowed()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\AsyncRouteHandlerInterface')
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

        $router = new AsyncRouter($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $router($request, $response);
    }

    public function testMiddlewareHandlerNotFound()
    {
        $provider = $this->getMockBuilder('\Thruster\Component\HttpRouter\RouteProviderInterface')
            ->setMethods(['getRoutes', 'foo'])
            ->getMockForAbstractClass();

        $handler = $this->getMockBuilder('\Thruster\Component\HttpRouter\AsyncRouteHandlerInterface')
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

        $router = new AsyncRouter($provider, $handler);
        $router->setDispatcher($dispatcher);
        $router->buildRoutes();

        $router($request, $response);
    }
}
