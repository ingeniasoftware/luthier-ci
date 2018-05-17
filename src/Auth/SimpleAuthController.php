<?php

namespace Luthier\Auth;

use Luthier\Auth\AuthControllerInterface;
use Luthier\Auth\SimpleAuthMiddleware;
use Luthier\RouteBuilder as Route;

abstract class SimpleAuthController extends \CI_Controller implements AuthControllerInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getUserClass()
    {
        return 'User';
    }

    public function getMiddleware()
    {
        return new SimpleAuthMiddleware();
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

    public function passwordReset()
    {
        $this->load->view('Auth/password_reset');
    }

    public function passwordResetForm($token)
    {
        $this->load->view('Auth/password_reset_form');
    }
}