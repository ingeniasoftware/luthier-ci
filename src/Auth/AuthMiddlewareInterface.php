<?php

namespace Luthier\Auth;

use Luthier\Route;
use Luthier\AuthUserProviderInterface;

interface AuthMiddlewareInterface
{
    public function run($args);

    public function preLogin(Route $route);

    public function onLoginSuccess();

    public function onLoginFailed();

    public function onLogout();
}



