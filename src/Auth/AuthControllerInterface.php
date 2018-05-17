<?php

namespace Luthier\Auth;

interface AuthControllerInterface
{
    public function getUserClass();

    public function getMiddleware();

    public function login();

    public function logout();

    public function signup();

    public function passwordReset();

    public function passwordResetForm($token);
}
