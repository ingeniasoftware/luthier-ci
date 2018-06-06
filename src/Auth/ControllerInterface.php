<?php

/**
 * Authentication Controller Interface
 *
 * ALL Controller-Based authentication controller MUST implement this interface.
 * It provides the basic structure of a traditional login/signup form
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth;

interface ControllerInterface
{
    public function getUserProvider();

    public function getMiddleware();

    public function login();

    public function logout();

    public function signup();

    public function emailVerification($token);

    public function passwordReset();

    public function passwordResetForm($token);
}
