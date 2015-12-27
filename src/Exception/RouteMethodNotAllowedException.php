<?php

namespace Thruster\Component\HttpRouter\Exception;

use Psr\Http\Message\RequestInterface;

/**
 * Class RouteMethodNotAllowedException
 *
 * @package Thruster\Component\HttpRouter\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class RouteMethodNotAllowedException extends \Exception
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $allowedMethods;

    public function __construct(RequestInterface $request, array $allowedMethods = [])
    {
        $this->request = $request;
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest() : RequestInterface
    {
        return $this->request;
    }

    /**
     * @return array
     */
    public function getAllowedMethods() : array
    {
        return $this->allowedMethods;
    }
}
