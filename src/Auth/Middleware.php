<?php

namespace Luthier\Auth;

class Middleware
{
    private $route;

    /**
     * Middleware entry point
     *
     * @return void
     */
    public function run()
    {
        echo '[Auth_middleware::run()]<br>';

        $this->route = ci()->route;
        $this->redirectIfAuthenticated();
        $this->interceptLogin();

    }

    private function redirectIfAuthenticated()
    {
        if(in_array($this->route->getName(), ['login', 'signup', 'password_reset', 'password_reset_form']))
        {
            echo '[Auth_middleware::redirectIfAuthenticated()]<br>';
        }
    }

    private function interceptLogin()
    {
        if($this->route->getName() == 'login' && $this->route->method == 'POST')
        {
            echo '[Auth_middleware::interceptLogin()]<br>';
        }
    }
}