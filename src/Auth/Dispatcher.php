<?php

/**
 * Authentication Dispatcher
 *
 * (This is used by Luthier-CI internally)
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth;

use Luthier\Auth\ControllerInterface as AuthControllerInterface;
use Luthier\Auth\Middleware as AuthMiddlewareInterface;
use Luthier\Auth;
use Luthier\Middleware;
use Luthier\MiddlewareInterface;
use Luthier\Debug;

class Dispatcher implements MiddlewareInterface
{
    public function run($args)
    {
        if(!ci() instanceof AuthControllerInterface)
        {
            return;
        }

        $authMiddleware = ci()->getMiddleware();

        if(is_string($authMiddleware))
        {
            $authMiddleware = Middleware::load($authMiddleware);
        }

        if(!$authMiddleware instanceof AuthMiddlewareInterface)
        {
            show_error('The auth middleware must inherit the Luthier\Auth\Middleware class');
        }

        ci()->middleware->run($authMiddleware,  Auth::loadUserProvider(ci()->getUserProvider()));
    }
}