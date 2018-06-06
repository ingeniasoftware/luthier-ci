<?php

/**
 * SimpleAuthMiddleware class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth\Middleware;

use Luthier\Auth;
use Luthier\MiddlewareInterface;
use Luthier\Auth\ControllerInterface as AuthControllerInterface;
use Luthier\Auth\SimpleAuth\Middleware\RememberMeMiddleware;

class SimpleAuthMiddleware implements MiddlewareInterface
{
    public function run($args)
    {
        if(ci() instanceof AuthControllerInterface)
        {
            return;
        }

        if( config_item('simpleauth_enable_remember_me') === true )
        {
            ci()->middleware->run(new RememberMeMiddleware(), 'restore');
        }

        if( Auth::isGuest() )
        {
            if(ci()->route->getName() != config_item('auth_login_route'))
            {
                redirect( route(config_item('auth_login_route')) );
                exit;
            }
        }
    }
}