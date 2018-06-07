<?php

/**
 * RememberMeMiddleware class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth\Middleware;

use Luthier\MiddlewareInterface;
use Luthier\Auth;
use Luthier\Auth\UserInterface;

class RememberMeMiddleware implements MiddlewareInterface
{
    public function run($action = 'store')
    {
        if($action == 'store')
        {
            $this->storeAuthCookie();
        }
        elseif($action == 'restore')
        {
            $this->restoreAuthFromCookie();
        }
        elseif($action == 'destroy')
        {
            $this->destroyAuthCookie();
        }
        else
        {
            show_error('Unknown RememberMeMiddleware "' . $action . '" action');
        }
    }

    private function storeAuthCookie()
    {
        if(ci()->input->post(config_item('simpleauth_remember_me_field')) === null)
        {
            return;
        }

        ci()->load->library('encryption');

        $rememberToken = bin2hex(ci()->encryption->create_key(32));

        ci()->db->update(
            config_item('simpleauth_users_table'),
           [config_item('simpleauth_remember_me_col') => $rememberToken],
           ['id' => Auth::user()->id]
        );

        ci()->input->set_cookie(config_item('simpleauth_remember_me_cookie'), $rememberToken, 1296000); // 15 days
    }

    private function restoreAuthFromCookie()
    {
        if( !Auth::isGuest() || Auth::session('fully_authenticated') === true)
        {
            return;
        }

        ci()->load->database();
        ci()->load->helper('cookie');
        ci()->load->library('encryption');

        $rememberToken = get_cookie(config_item('simpleauth_remember_me_cookie'));

        if( empty($rememberToken) )
        {
            return;
        }

        $storedUserFromToken = ci()->db->get_where(
             config_item('simpleauth_users_table'),
            [config_item('simpleauth_remember_me_col') => $rememberToken]
        )->result();

        if(empty($storedUserFromToken))
        {
            delete_cookie(config_item('simpleauth_remember_me_cookie'));
            return;
        }

        $userProvider = Auth::loadUserProvider(config_item('simpleauth_user_provider'));

        try
        {
            $user = Auth::bypass($storedUserFromToken[0]->{config_item('simpleauth_username_col')}, $userProvider);
                    $userProvider->checkUserIsActive($user);

                    if(
                        config_item('simpleauth_enable_email_verification')  === TRUE &&
                        config_item('simpleauth_enforce_email_verification') === TRUE
                    )
                    {
                        $userProvider->checkUserIsVerified($user);
                    }
        }
        catch(\Exception $e)
        {
            delete_cookie(config_item('simpleauth_remember_me_cookie'));
            return;
        }

        Auth::store($user, ['fully_authenticated' => false]);

        /*

        // This, probably, is not necessary:

        $newRememberToken = bin2hex(ci()->encryption->create_key(32));

        set_cookie(config_item('simpleauth_remember_me_cookie'), $newRememberToken, 1296000); // 15 days

        ci()->db->update(
             config_item('simpleauth_users_table'),
            [config_item('simpleauth_remember_me_col') => $newRememberToken],
            ['id' => $user->getInstance()->id]
        );

        */
    }

    private function destroyAuthCookie()
    {
        ci()->load->helper('cookie');      
        delete_cookie(config_item('simpleauth_remember_me_cookie'));
    }
}