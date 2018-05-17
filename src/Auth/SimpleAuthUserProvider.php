<?php

namespace Luthier\Auth;

use Luthier\Auth\AuthUserInterface;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\AuthUserProviderInterface;

abstract class SimpleAuthUserProvider implements AuthUserProviderInterface
{
    public function loadByUsername($username, $userClass)
    {
        throw new UserNotFoundException();
        
        return new $userClass('username', 'password', 'roles');
    }

    public function refreshUser(AuthUserInterface $user)
    {

    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

}