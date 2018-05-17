<?php

namespace Luthier\Auth;

use Luthier\Auth\AuthMiddlewareInterface;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Route;

abstract class AuthMiddleware implements AuthMiddlewareInterface
{
    final public function run($args)
    {
        list($userClass, $userProviderClass) = $args;

        ci()->load->config('luthier_auth');

        $this->preLogin(ci()->route);

        if(ci()->route->getName() == config_item('login_route') && ci()->route->method == 'POST')
        {
            $username = ci()->input->post(config_item('login_username_field'));
            $password = ci()->input->post(config_item('login_password_field'));

            $userProvider = new $userProviderClass();

            try
            {
                $logedUser = $userProvider->loadByUsername($username, $userClass);
            }
            catch(UserNotFoundException $e)
            {
                $this->onLoginFailed();
                return;
            }

            $this->onLoginSuccess($logedUser);
        }
    }
}