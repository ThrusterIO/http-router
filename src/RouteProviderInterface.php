<?php

namespace Thruster\Component\HttpRouter;

/**
 * Interface RouteableInterface
 *
 * @package Thruster\Component\HttpRouter
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface RouteProviderInterface
{
    /**
     * @return array
     */
    public function getRoutes() : array;
}
