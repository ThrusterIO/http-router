<?php

namespace Thruster\Component\HttpRouter\Exception;

use FastRoute\BadRouteException;

/**
 * Class InvalidRouteOptionsException
 *
 * @package Thruster\Component\HttpRouter\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class InvalidRouteOptionsException extends \Exception
{
    public function __construct($routeName, BadRouteException $exception = null)
    {
        if (null === $exception) {
            $message = sprintf(
                'Invalid options specified for route "%s"',
                $routeName
            );
        } else {
            $message = sprintf(
                'Error for route "%s": %s',
                $routeName,
                $exception->getMessage()
            );
        }

        parent::__construct($message);
    }
}
