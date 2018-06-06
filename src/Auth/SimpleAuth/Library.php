<?php

/**
 * SimpleAuth Controller class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth;

use Luthier\Auth;

class Library
{
    public function __call($name, $args)
    {
        if(method_exists(Auth::class, $name))
        {
            return call_user_func_array([Auth::class, $name], $args);
        }

        show_error('Unknown "' . $name . '" method', 500, 'SimpleAuth error');
    }

    public function isFullyAuthenticated()
    {
        if(Auth::isGuest())
        {
            return false;
        }

        return Auth::session('fully_authenticated') === TRUE;
    }


    public function promptPassword()
    {
        if(Auth::isGuest() || !route_exists('confirm_password'))
        {
            return;
        }

        if( !$this->isFullyAuthenticated() )
        {
            $currentUrl = route();

            redirect( route('confirm_password') . '?redirect_to=' . $currentUrl );
            exit;
        }
    }
}
