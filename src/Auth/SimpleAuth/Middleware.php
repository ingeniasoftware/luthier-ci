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
                ->where('created_at >=', date('Y-m-d H:i:s'))
                ->where('created_at <=', date('Y-m-d H:i:s', time() + (60 * 30))) // 30 minutes
                ->count_all_results(config_item('simpleauth_login_attempts_table'));

            if($loginAttemptCount >= 4)
            {
                ci()->session->set_flashdata('_auth_messages', [ 'danger' =>  'Please try again later' ]);
                redirect(route(config_item('auth_login_route')));
                exit;
            }
        }

        if(in_array($route->getName(), config_item('auth_route_auto_redirect')) && !Auth::isGuest())
        {
            redirect(route(config_item('auth_login_route_redirect')));
        }
    }

    public function onLoginSuccess(UserInterface $user)
    {
        Auth::store($user);

        if( config_item('simpleauth_enable_remember_me') === true )
        {
            ci()->middleware->run( new RememberMeMiddleware(), 'store');
        }

        redirect(route(config_item('auth_login_route_redirect')));
        exit;
    }

    public function onLoginFailed($username)
    {
        ci()->load->database();

        // Register a failed login attempt in database

        if( config_item('simpleauth_enable_brute_force_protection') === true )
        {
            ci()->db->insert(
                config_item('simpleauth_login_attempts_table'),
                [
                    'username'   => $username,
                    'ip'         => $_SERVER['REMOTE_ADDR'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );
        }

        redirect(route(config_item('auth_login_route')));
    }

    public function onLoginInactiveUser(UserInterface $user)
    {
        Auth::destroy();
        redirect(route(config_item('auth_login_route')));
    }

    public function onLoginUnverifiedUser(UserInterface $user)
    {
        Auth::destroy();
        redirect(route(config_item('auth_login_route')));
    }

    public function onLogout()
    {
        Auth::destroy();
        redirect(route(config_item('auth_logout_route_redirect')));
    }
}