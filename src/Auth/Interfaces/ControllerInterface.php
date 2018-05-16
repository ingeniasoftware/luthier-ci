<?php

namespace Luthier\Auth\Interfaces;

interface ControllerInterface
{
    public function login();

    public function logout();

    public function signup();

    public function password_reset();

    public function password_reset_form($token);
}
