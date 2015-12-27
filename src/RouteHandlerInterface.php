<?php

namespace Thruster\Component\HttpRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface RouteHandlerInterface
 *
 * @package Thruster\Component\HttpRouter
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface RouteHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $callback
     *
     * @return ResponseInterface
     */
    public function handleRoute(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $callback
    ) : ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $allowedMethods
     *
     * @return ResponseInterface
     */
    public function handleRouteMethodNotAllowed(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) : ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handleRouteNotFound(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface;
}
