<?php

/*
 * Luthier CI
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of Luthier CI, a plugin for CodeIgniter 3. See the LICENSE
 * file for copyright information and license details
 */

namespace Luthier\Auth\SimpleAuth\Middleware;

use Luthier\Auth;
use Luthier\MiddlewareInterface;
use Luthier\Auth\ControllerInterface as AuthControllerInterface;

/**
 * Basic security layer for routing that requires user authentication.
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class SimpleAuthMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\MiddlewareInterface::run()
     */
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