<?php

/**
 * SimpleAuth Middleware class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth;

use Luthier\Auth;
use Luthier\Auth\Middleware as AuthMiddleware;
use Luthier\Auth\UserInterface;
use Luthier\Auth\SimpleAuth\Middleware\RememberMeMiddleware;
use Luthier\Route;
use Luthier\Debug;

class Middleware extends AuthMiddleware
{
    public function preLogin(Route $route)
    {
        if(
            $route->getName()     == config_item('auth_login_route') &&
            $route->requestMethod == 'POST' &&
            config_item('simpleauth_enable_brute_force_protection') === true
        )
        {
            ci()->load->database();

            $loginAttemptCount = ci()->db->where('ip', $_SERVER['REMOTE_ADDR'])
                ->where('created_at >=', date('Y-m-d H:i:s', time() - (60 * 30)) ) // 30 minutes
                ->where('created_at <=', date('Y-m-d H:i:s', time()))
                ->count_all_results(config_item('simpleauth_login_attempts_table'));

            if($loginAttemptCount >= 4)
            {
                ci()->session->set_flashdata('_auth_messages', [ 'danger' =>  'ERR_LOGIN_ATTEMPT_BLOCKED' ]);

                return redirect(route(config_item('auth_login_route')));
            }
        }
    }

    public function onLoginSuccess(UserInterface $user)
    {
        if( config_item('simpleauth_enable_remember_me') === true )
        {
            ci()->middleware->run( new RememberMeMiddleware(), 'store');
        }

        return redirect(
            route_exists(config_item('auth_login_route_redirect'))
                ? route(config_item('auth_login_route_redirect'))
                : base_url()
        );
    }

    public function onLoginFailed($username)
    {
        ci()->load->database();

        if( config_item('simpleauth_enable_brute_force_protection') === true )
        {
            ci()->db->insert(
                config_item('simpleauth_login_attempts_table'),
                [
                    'username'   => $username,
                    'ip'         => $_SERVER['REMOTE_ADDR']
                ]
            );
        }
    }

    public function onLoginInactiveUser(UserInterface $user)
    {
        return;
    }

    public function onLoginUnverifiedUser(UserInterface $user)
    {
        return;
    }

    public function onLogout()
    {
        ci()->middleware->run( new RememberMeMiddleware(), 'destroy');
    }
}