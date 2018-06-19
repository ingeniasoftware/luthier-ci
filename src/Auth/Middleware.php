<?php

/**
 * Authentication Middleware
 *
 * All authentication-related middleware MUST inherit this abstract class. It provides
 * a coherent authentication logic that can be easily implemented.
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth;

use Luthier\Auth;
use Luthier\Middleware as LuthierMiddleware;
use Luthier\MiddlewareInterface;
use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;
use Luthier\Route;
use Luthier\RouteBuilder;
use Luthier\Debug;

abstract class Middleware implements MiddlewareInterface
{
    final public function run($userProvider)
    {
        Debug::log(
            '>>> USING CONTROLLER-BASED AUTH ['
            . get_class(ci()) . ', '
            . get_class($userProvider) . ', '
            . get_class( is_object(ci()->getMiddleware()) ? ci()->getMiddleware() : LuthierMiddleware::load(ci()->getMiddleware()) ) . ' ]'
        , 'info', 'auth');

        $authLoginRoute = config_item('auth_login_route') !== null
            ?
                config_item('auth_login_route')
            :
                'login';

        $authLoginRouteRedirect = config_item('auth_login_route_redirect') !== null
            ?
                config_item('auth_login_route_redirect')
            :
                null;

        $authLogoutRoute = config_item('auth_logout_route') !== null
            ?
                config_item('auth_logout_route')
            :
                'logout';

        $authLogoutRouteRedirect = config_item('auth_logout_route_redirect') !== null
            ?
                config_item('auth_logout_route_redirect')
            :
                null;

        $authRouteAutoRedirect = is_array( config_item('auth_route_auto_redirect')  )
            ?
                config_item('auth_route_auto_redirect')
            :
                [];

        if( !Auth::isGuest() && ( ci()->route->getName() == $authLoginRoute || in_array(ci()->route->getName(), $authRouteAutoRedirect)) )
        {
            return redirect(
                $authLoginRouteRedirect !== null && route_exists($authLoginRouteRedirect)
                ?
                    route($authLoginRouteRedirect)
                :
                     base_url()
            );
        }

        $this->preLogin(ci()->route);

        if(ci()->route->getName() == $authLoginRoute && ci()->route->requestMethod == 'POST')
        {
            $usernameField = config_item('auth_form_username_field') !== null
                ?
                    config_item('auth_form_username_field')
                :
                    'username';

            $passwordField = config_item('auth_form_password_field') !== null
                ?
                    config_item('auth_form_password_field')
                :
                    'password';

            $username = ci()->input->post($usernameField);
            $password = ci()->input->post($passwordField);

            Debug::logFlash('>>> LOGIN ATTEMPT INTERCEPTED', 'info', 'auth');
            Debug::logFlash('Username: ' . $username, 'info', 'auth');
            Debug::logFlash('Password: ' . $password . ' [hash: ' . $userProvider->hashPassword($password) . ']', 'info', 'auth');

            try
            {
                $user = $userProvider->loadUserByUsername($username, $password);
                        $userProvider->checkUserIsActive($user);
                        $userProvider->checkUserIsVerified($user);
            }
            catch(UserNotFoundException $e)
            {
                Debug::logFlash('FAILED: ' . UserNotFoundException::class, 'error', 'auth');
                ci()->session->set_flashdata('_auth_messages', [ 'danger' => 'ERR_LOGIN_INVALID_CREDENTIALS' ]);
                $this->onLoginFailed($username);

                return redirect(route($authLoginRoute));
            }
            catch(InactiveUserException $e)
            {
                Debug::logFlash('FAILED: ' . InactiveUserException::class, 'error', 'auth');
                ci()->session->set_flashdata('_auth_messages', [ 'danger' => 'ERR_LOGIN_INACTIVE_USER' ]);
                $this->onLoginInactiveUser($user);

                return redirect(route($authLoginRoute));
            }
            catch(UnverifiedUserException $e)
            {
                Debug::logFlash('FAILED: ' . UnverifiedUserException::class, 'error', 'auth');
                ci()->session->set_flashdata('_auth_messages', [ 'danger' => 'ERR_LOGIN_UNVERIFIED_USER' ]);
                $this->onLoginUnverifiedUser($user);

                return redirect(route($authLoginRoute));
            }

            Auth::store($user);
            $this->onLoginSuccess($user);

            return redirect( $authLoginRouteRedirect !== null ? route($authLoginRouteRedirect) : base_url() );
        }

        if(ci()->route->getName() == $authLogoutRoute)
        {
            Auth::destroy();
            $this->onLogout();

            return redirect(
                $authLogoutRouteRedirect !== null && route_exists($authLogoutRouteRedirect)
                ?
                    route($authLogoutRouteRedirect)
                :
                     base_url()
            );
        }
    }

    abstract public function preLogin(Route $route);

    abstract public function onLoginSuccess(UserInterface $user);

    abstract public function onLoginFailed($username);

    abstract public function onLoginInactiveUser(UserInterface $user);

    abstract public function onLoginUnverifiedUser(UserInterface $user);

    abstract public function onLogout();
}