<?php

namespace Thruster\Component\HttpRouter;

use FastRoute\BadRouteException;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\HttpRouter\Exception\InvalidRouteOptionsException;
use Thruster\Component\HttpRouter\Exception\RouteNotFoundException;
use Thruster\Component\HttpRouter\Exception\RouteMethodNotAllowedException;

/**
 * Class Router
 *
 * @package Thruster\Component\HttpRouter
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Router
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $routes;

    /**
     * @var RouteProviderInterface
     */
    protected $routeProvider;

    /**
     * @var RouteHandlerInterface
     */
    protected $routeHandler;

    /**
     * @var RouteCollector
     */
    protected $routeCollector;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    public function __construct(
        RouteProviderInterface $provider,
        RouteHandlerInterface $handler = null,
        array $options = []
    ) {
        $options += [
            'route_parser'    => 'FastRoute\\RouteParser\\Std',
            'data_generator'  => 'FastRoute\\DataGenerator\\GroupCountBased',
            'dispatcher'      => 'FastRoute\\Dispatcher\\GroupCountBased',
            'route_collector' => 'FastRoute\\RouteCollector',
        ];

        $this->routes        = [];
        $this->routeProvider = $provider;
        $this->routeHandler  = $handler;
        $this->options       = $options;
    }

    public function buildRoutes()
    {
        $routeCollection = $this->getRouteCollector();

        foreach ($this->routeProvider->getRoutes() as $name => $options) {
            if (count($options) < 3) {
                throw new InvalidRouteOptionsException($name);
            }

            $handler = array_pop($options);
            if (is_string($handler)) {
                $handler = [$this->routeProvider, $handler];
            }

            $this->routes[$name] = $handler;

            $route = array_pop($options);

            try {
                $routeCollection->addRoute($options, $route, $name);
            } catch (BadRouteException $e) {
                throw new InvalidRouteOptionsException($name, $e);
            }
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws RouteMethodNotAllowedException
     * @throws RouteNotFoundException
     */
    public function handleRequest(ServerRequestInterface $request) : ResponseInterface
    {
        list($callback, $params) = $this->internalRequestHandle($request);

        return call_user_func($callback, $params);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        try {
            list($callback, $request) = $this->internalRequestHandle($request);

            $response = $this->routeHandler->handleRoute($request, $response, $callback);
        } catch (RouteMethodNotAllowedException $e) {
            $response = $this->routeHandler->handleRouteMethodNotAllowed($request, $response, $e->getAllowedMethods());
        } catch (RouteNotFoundException $e) {
            $response = $this->routeHandler->handleRouteNotFound($request, $response);
        }

        if (null !== $next) {
            return $next($request, $response);
        }

        return $response;
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher() : Dispatcher
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $this->dispatcher = new $this->options['dispatcher'](
            $this->getRouteData()
        );

        return $this->dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     *
     * @return Router
     */
    public function setDispatcher(Dispatcher $dispatcher) : self
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * @return RouteCollector
     */
    public function getRouteCollector() : RouteCollector
    {
        if ($this->routeCollector) {
            return $this->routeCollector;
        }

        $this->routeCollector = new $this->options['route_collector'](
            new $this->options['route_parser'](), new $this->options['data_generator']()
        );

        return $this->routeCollector;
    }

    /**
     * @param RouteCollector $routeCollector
     *
     * @return Router
     */
    public function setRouteCollector(RouteCollector $routeCollector) : self
    {
        $this->routeCollector = $routeCollector;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteData() : array
    {
        $this->buildRoutes();

        return $this->getRouteCollector()->getData();
    }

    protected function internalRequestHandle(ServerRequestInterface $request)
    {
        $dispatcher = $this->getDispatcher();
        $uri        = $request->getUri();

        $route = $dispatcher->dispatch($request->getMethod(), $uri->getPath());

        switch ($route[0]) {
            case Dispatcher::NOT_FOUND:
                throw new RouteNotFoundException($request);
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new RouteMethodNotAllowedException($request, $route[1]);
            case Dispatcher::FOUND:
                $request = $request
                    ->withAttribute('route_name', $route[1])
                    ->withAttribute('route_params', $route[2]);

                return [$this->routes[$route[1]], $request];
        }

        throw new \LogicException('Should not reach this point');
    }
}
