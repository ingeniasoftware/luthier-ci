<?php

namespace Luthier\Auth;

use Luthier\Auth\Interfaces\ControllerInterface;
use \Route;

abstract class Controller extends \CI_Controller implements ControllerInterface
{
    public function __construct()
    {
        parent::__construct();
        Route::middleware('Auth_middleware');
    }

    public function login()
    {
        $this->load->view('Auth/login');
    }

    public function logout()
    {

    }

    public function signup()
    {
        $this->load->view('Auth/signup');
    }

    public function password_reset()
    {
        $this->load->view('Auth/password_reset');
    }

    public function password_reset_form($token)
    {
        $this->load->view('Auth/password_reset_form');
    }
}