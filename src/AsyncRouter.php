<?php

namespace Thruster\Component\HttpRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\HttpRouter\Exception\RouteMethodNotAllowedException;
use Thruster\Component\HttpRouter\Exception\RouteNotFoundException;
use Thruster\Component\Promise\ExtendedPromiseInterface;
use Thruster\Component\Promise\FulfilledPromise;

/**
 * Class AsyncRouter
 *
 * @package Thruster\Component\HttpRouter
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class AsyncRouter extends Router
{
    /**
     * @var AsyncRouteHandlerInterface
     */
    protected $routeHandler;

    public function __construct(
        RouteProviderInterface $provider,
        AsyncRouteHandlerInterface $handler = null,
        array $options = []
    ) {
        parent::__construct($provider, null, $options);

        $this->routeHandler  = $handler;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ) : ExtendedPromiseInterface {
        try {
            list($callback, $request) = $this->internalRequestHandle($request);

            $promise = $this->routeHandler->handleRoute($request, $response, $callback);
        } catch (RouteMethodNotAllowedException $e) {
            $promise = $this->routeHandler->handleRouteMethodNotAllowed($request, $response, $e->getAllowedMethods());
        } catch (RouteNotFoundException $e) {
            $promise = $this->routeHandler->handleRouteNotFound($request, $response);
        }

        if (null !== $next) {
            return $promise->then(function (ResponseInterface $response) use ($request, $next) {
                $response = $next($request, $response);

                return new FulfilledPromise($response);
            });
        }

        return $promise;
    }

}
