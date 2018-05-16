<?php

namespace Luthier\Auth\Interfaces;

interface AdvancedControllerInterface
{
    public function preLogin();

    public function postLogin();

    public function onLoginSuccess();

    public function onLoginFail();

    public function onLogout();
}

