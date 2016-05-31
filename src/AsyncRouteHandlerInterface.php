<?php

namespace Thruster\Component\HttpRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\Promise\ExtendedPromiseInterface;

/**
 * Interface AsyncRouteHandlerInterface
 *
 * @package Thruster\Component\HttpRouter
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface AsyncRouteHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $callback
     *
     * @return ExtendedPromiseInterface
     */
    public function handleRoute(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $callback
    ) : ExtendedPromiseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $allowedMethods
     *
     * @return ExtendedPromiseInterface
     */
    public function handleRouteMethodNotAllowed(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) : ExtendedPromiseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ExtendedPromiseInterface
     */
    public function handleRouteNotFound(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ExtendedPromiseInterface;
}
