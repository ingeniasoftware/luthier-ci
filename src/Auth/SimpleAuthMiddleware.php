<?php

namespace Luthier\Auth;

use Luthier\Auth;
use Luthier\Auth\AuthMiddleware;
use Luthier\Route;

class SimpleAuthMiddleware extends AuthMiddleware
{
    public function preLogin(Route $route)
    {
        if(in_array($route->getName(), ['login', 'signup', 'password_reset', 'password_reset_form']))
        {

        }
    }

    public function onLoginSuccess()
    {
        redirect(route('dashboard'), 'refresh');
    }

    public function onLoginFailed()
    {
        redirect(route('login'), 'refresh');
    }

    public function onLogout()
    {

    }
}