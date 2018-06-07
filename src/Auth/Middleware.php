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
        Debug::log('>>> USING CONTROLLER-BASED AUTH [' . get_class(ci()) . ',' . get_class($userProvider) . ',' . get_class(ci()->getMiddleware()) . ' ]' , 'info', 'auth');

        $this->preLogin(ci()->route);

        if(ci()->route->getName() == config_item('auth_login_route') && ci()->route->requestMethod == 'POST')
        {

            $username = ci()->input->post(config_item('auth_form_username_field'));
            $password = ci()->input->post(config_item('auth_form_password_field'));

            Debug::logFlash('>>> LOGIN ATTEMPT INTERCEPTED', 'info', 'auth');
            Debug::logFlash('Username: ' . $username, 'info', 'auth');
            Debug::logFlash('Password: ' . $password . ' [hash: ' . $userProvider->hashPassword($password) . ']', 'info', 'auth');

            try
            {
                $user = $userProvider->loadUserByUsername($username, $password);
                        $userProvider->checkUserIsActive($user);

                if(
                    config_item('simpleauth_enable_email_verification')  === TRUE &&
                    config_item('simpleauth_enforce_email_verification') === TRUE
                )
                {
                    $userProvider->checkUserIsVerified($user);
                }
            }
            catch(UserNotFoundException $e)
            {
                Debug::logFlash('FAILED: ' . UserNotFoundException::class, 'error', 'auth');

                ci()->session->set_flashdata('_auth_messages', [ 'danger' =>  'ERR_LOGIN_INVALID_CREDENTIALS' ]);

                $this->onLoginFailed($username);
                return;
            }
            catch(InactiveUserException $e)
            {
                Debug::logFlash('FAILED: ' . InactiveUserException::class, 'error', 'auth');

                ci()->session->set_flashdata('_auth_messages', [ 'danger' =>  'ERR_LOGIN_INACTIVE_USER' ]);

                $this->onLoginInactiveUser($user);
                return;
            }
            catch(UnverifiedUserException $e)
            {
                Debug::logFlash('FAILED: ' . UnverifiedUserException::class, 'error', 'auth');

                ci()->session->set_flashdata('_auth_messages', [ 'danger' =>  'ERR_LOGIN_UNVERIFIED_USER' ]);

                $this->onLoginUnverifiedUser($user);
                return;
            }

            if(!$user instanceof UserInterface)
            {
                show_error('Returned user MUST be an instance of UserInterface');
            }

            $this->onLoginSuccess($user);
        }

        if(ci()->route->getName() == config_item('auth_logout_route'))
        {
            $this->onLogout();
        }
    }

    abstract public function preLogin(Route $route);

    abstract public function onLoginSuccess(UserInterface $user);

    abstract public function onLoginFailed($username);

    abstract public function onLoginInactiveUser(UserInterface $user);

    abstract public function onLoginUnverifiedUser(UserInterface $user);

    abstract public function onLogout();
}