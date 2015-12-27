<?php

namespace Thruster\Component\HttpRouter\Exception;

use Psr\Http\Message\RequestInterface;

/**
 * Class RouteNotFoundException
 *
 * @package Thruster\Component\HttpRouter\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class RouteNotFoundException extends \Exception
{
    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest() : RequestInterface
    {
        return $this->request;
    }
}
