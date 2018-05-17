<?php

namespace Luthier\Auth;

use Luthier\Auth\AuthControllerInterface;

class AuthDispatcher
{
    public function run()
    {
        if(!ci() instanceof AuthControllerInterface)
        {
            return;
        }

        $authMiddleware = ci()->getMiddleware();

        if(!is_object($authMiddleware) || !$authMiddleware instanceof AuthMiddlewareInterface)
        {
            show_error('The User Provider must be an instance of AuthMiddlewareInterface');
        }

        $authUserClass        = ci()->getUserClass();
        $authUserProvderClass = ci()->getUserClass() . 'Provider';

        if(file_exists( APPPATH . '/security/auth/' . $authUserClass . '.php') )
        {
            require_once APPPATH . '/security/auth/' . $authUserClass . '.php';

            if(!class_exists($authUserClass))
            {
                show_error('Class "' . $authUserClass . '" not found');
            }

            if(file_exists( APPPATH . '/security/auth/' . $authUserProvderClass . '.php') )
            {
                require_once APPPATH . '/security/auth/' . $authUserProvderClass . '.php';

                if(!class_exists($authUserProvderClass))
                {
                    show_error('User provider class "' .$authUserProvderClass . '" not found');
                }
            }
            else
            {
                show_error('Unable to find "' . $authUserProvderClass . '" user provider class file');
            }
        }
        else
        {
            show_error('Unable to find "' . $authUserClass . '" user class file');
        }

        // Run attached auth middleware
        ci()->middleware->run($authMiddleware, $authUserClass, $authUserProvderClass);
    }
}